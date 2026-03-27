<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Admin\Http\Resources\MediaResource;
use Motor\Core\Http\Resources\V2\BaseResource;
use Partymeister\Competitions\Models\Entry;

/**
 * @mixin Entry
 */
class VoteEntryResource extends BaseResource
{
    /**
     * Pre-loaded votes keyed by entry_id, injected from the controller.
     */
    private static array $visitorVotes = [];

    /**
     * Set the pre-loaded visitor votes to avoid N+1 queries.
     */
    public static function setVisitorVotes(array $votes): void
    {
        static::$visitorVotes = $votes;
    }

    public function toArray($request): array
    {
        $vote = static::$visitorVotes[$this->id] ?? null;

        $votingDeadlineOver = false;
        if (strtotime(config('partymeister-competitions-voting.deadline')) < time()) {
            $votingDeadlineOver = true;
        }

        $screenshot = new MediaResource($this->getFirstMedia('screenshot'));

        if (! $this->competition->competition_type->has_screenshot) {
            $screenshot = ['url' => false];
        }

        $audio = new MediaResource($this->getFirstMedia('audio'));

        if (! $audio) {
            $audio = ['url' => false];
        }

        return [
            'id' => (int) $this->id,
            'sort_position_prefixed' => (string) (strlen($this->sort_position) == 1 ? '0'.$this->sort_position : $this->sort_position),
            'competition_id' => $this->competition_id,
            'competition_name' => $this->competition->name,
            'title' => $this->title,
            'author' => $this->author,
            'description' => $this->description,
            'has_screenshot' => (bool) $this->competition->competition_type->has_screenshot,
            'screenshot' => $screenshot,
            'has_audio' => (bool) $this->competition->competition_type->has_audio,
            'audio' => $audio,
            'vote_category_has_comment' => (bool) (! is_null($this->competition->vote_categories) ? $this->competition->vote_categories[0]->has_comment : false),
            'vote_category_has_special_vote' => (bool) (! is_null($this->competition->vote_categories) ? $this->competition->vote_categories[0]->has_special_vote : false),
            'vote_category_has_negative' => (bool) (! is_null($this->competition->vote_categories) ? $this->competition->vote_categories[0]->has_negative : false),
            'vote_category_points' => (int) (! is_null($this->competition->vote_categories) ? $this->competition->vote_categories[0]->points : 0),
            'vote_category_id' => (int) (! is_null($this->competition->vote_categories) ? $this->competition->vote_categories[0]->id : 1),
            'vote' => $vote ? new VoteResource($vote) : null,
            'deadline_reached' => $votingDeadlineOver,
        ];
    }
}
