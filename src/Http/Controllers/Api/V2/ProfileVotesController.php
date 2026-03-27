<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Motor\Core\Http\Controllers\Api\V2\ApiController;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Competitions\Models\LiveVote;
use Partymeister\Competitions\Models\Vote;
use Partymeister\Competitions\Services\VoteService;
use Partymeister\Frontend\Http\Requests\Api\V2\VotePostRequest;
use Partymeister\Frontend\Http\Resources\V2\VoteEntryCollection;
use Partymeister\Frontend\Http\Resources\V2\VoteEntryResource;

/**
 * @tags Visitor Voting
 */
class ProfileVotesController extends ApiController
{
    /** @response VoteEntryCollection */
    public function live(Request $request): JsonResponse|Response
    {
        $liveVote = LiveVote::first();
        if (is_null($liveVote)) {
            return response()->noContent();
        }

        $competition = $liveVote->competition;
        if (is_null($competition)) {
            return response()->noContent();
        }

        if ($competition->voting_enabled && strtotime($competition->updated_at) <= time() - 300) {
            return response()->noContent();
        }

        $entries = $competition->entries()
            ->where('status', 1)
            ->where('sort_position', '<=', $liveVote->sort_position)
            ->orderBy('sort_position', 'DESC')
            ->with('competition.vote_categories', 'competition.competition_type')
            ->get();

        $this->preloadVisitorVotes($entries, Auth::guard('visitor')->id());

        return (new VoteEntryCollection($entries))
            ->additional(['meta' => ['message' => 'Live votes loaded']])
            ->response();
    }

    /** @response VoteEntryCollection */
    public function entries(Request $request): JsonResponse
    {
        $query = DB::table('entries')
            ->select('entries.id')
            ->join('competitions', 'entries.competition_id', '=', 'competitions.id')
            ->where('competitions.voting_enabled', true)
            ->where('entries.status', 1);

        if (! is_null($request->get('competition_id'))) {
            $query->where('competition_id', $request->get('competition_id'));
        }

        $query->orderBy('entries.competition_id', 'ASC')
            ->orderBy('entries.sort_position', 'ASC');

        $entryIds = $query->get()->pluck('id');

        $entries = Entry::whereIn('id', $entryIds)
            ->with('competition.vote_categories', 'competition.competition_type')
            ->orderBy('competition_id', 'ASC')
            ->orderBy('sort_position', 'ASC')
            ->get();

        $this->preloadVisitorVotes($entries, Auth::guard('visitor')->id());

        return (new VoteEntryCollection($entries))
            ->additional(['meta' => ['message' => 'Voteable entries loaded']])
            ->response();
    }

    /**
     * @response array{data: array{success: bool, message: string}, meta: array{api_version: string, message: string}}
     */
    public function vote(VotePostRequest $request, Entry $entry): JsonResponse
    {
        $visitor = $request->user('visitor');

        $result = VoteService::submitVote(
            visitor: $visitor,
            entryId: $entry->id,
            voteCategoryId: $request->validated('vote_category_id'),
            points: (int) $request->validated('points', 0),
            comment: $request->validated('comment', ''),
            specialVote: $request->has('special_vote') ? (bool) $request->validated('special_vote') : null,
            isLive: (bool) $request->validated('live', false),
            ipAddress: $request->ip(),
        );

        if (! $result['success']) {
            $status = $result['status'] ?? 400;

            return $this->errorResponse('VOTE_FAILED', $result['message'], $status);
        }

        return response()->json([
            'data' => [
                'success' => true,
                'message' => $result['message'],
            ],
            'meta' => [
                'api_version' => 'v2',
                'message' => $result['message'],
            ],
        ]);
    }

    /**
     * Pre-load the visitor's votes for a collection of entries to avoid N+1 queries.
     */
    private function preloadVisitorVotes($entries, $visitorId): void
    {
        if ($entries->isEmpty() || is_null($visitorId)) {
            VoteEntryResource::setVisitorVotes([]);

            return;
        }

        // Collect the first vote_category_id per entry from its competition
        $entryVoteCategoryMap = [];
        foreach ($entries as $entry) {
            if ($entry->competition && $entry->competition->vote_categories && $entry->competition->vote_categories->isNotEmpty()) {
                $entryVoteCategoryMap[$entry->id] = $entry->competition->vote_categories[0]->id;
            }
        }

        if (empty($entryVoteCategoryMap)) {
            VoteEntryResource::setVisitorVotes([]);

            return;
        }

        $votes = Vote::where('visitor_id', $visitorId)
            ->whereIn('entry_id', array_keys($entryVoteCategoryMap))
            ->get()
            ->keyBy('entry_id');

        // Filter to only include votes matching the expected vote_category_id
        $filteredVotes = [];
        foreach ($entryVoteCategoryMap as $entryId => $voteCategoryId) {
            $vote = $votes->get($entryId);
            if ($vote && $vote->vote_category_id == $voteCategoryId) {
                $filteredVotes[$entryId] = $vote;
            }
        }

        VoteEntryResource::setVisitorVotes($filteredVotes);
    }
}
