<?php

namespace Partymeister\Frontend\Http\Resources\V2;

use Motor\Core\Http\Resources\V2\BaseResource;
use Partymeister\Competitions\Models\Vote;

/**
 * @mixin Vote
 */
class VoteResource extends BaseResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'competition_id' => (int) $this->competition_id,
            'entry_id' => (int) $this->entry_id,
            'visitor_id' => (int) $this->visitor_id,
            'vote_category_id' => (int) $this->vote_category_id,
            'special_vote' => (bool) $this->special_vote,
            'comment' => $this->comment,
            'points' => (int) $this->points,
            'ip_address' => $this->ip_address,
        ];
    }
}
