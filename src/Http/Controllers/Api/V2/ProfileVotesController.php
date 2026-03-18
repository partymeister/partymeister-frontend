<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Motor\Backend\Http\Controllers\Controller;
use Partymeister\Competitions\Http\Resources\Vote\EntryResource as VoteEntryResource;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Competitions\Models\LiveVote;
use Partymeister\Competitions\Services\VoteService;

class ProfileVotesController extends Controller
{
    /**
     * Get live voting entries for the authenticated visitor.
     */
    public function live(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');

        $liveVote = LiveVote::first();
        if (is_null($liveVote)) {
            return response()->json([], 204);
        }

        $competition = $liveVote->competition;
        if (is_null($competition)) {
            return response()->json([], 204);
        }

        // Allow live voting to stay open for 5 minutes after competition update
        if ($competition->voting_enabled && strtotime($competition->updated_at) <= time() - 300) {
            return response()->json([], 204);
        }

        $entries = $liveVote->competition->entries()
            ->where('status', 1)
            ->where('sort_position', '<=', $liveVote->sort_position)
            ->orderBy('sort_position', 'DESC')
            ->get();

        return response()->json([
            'data'    => VoteEntryResource::collection($entries->load('competition'))
                                          ->toArrayRecursive(),
            'status'  => 200,
            'message' => 'Livevotes loaded',
        ]);
    }

    /**
     * Get voteable entries for the authenticated visitor.
     */
    public function entries(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');

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
            ->orderBy('competition_id', 'ASC')
            ->orderBy('sort_position', 'ASC')
            ->get();

        return response()->json([
            'status'  => 200,
            'message' => 'Votes loaded',
            'data'    => VoteEntryResource::collection($entries),
        ]);
    }

    /**
     * Submit a vote for an entry.
     */
    public function vote(Request $request, Entry $entry): JsonResponse
    {
        $visitor = $request->user('visitor');

        $request->validate([
            'vote_category_id' => 'required|integer',
            'points' => 'integer',
        ]);

        $result = VoteService::submitVote(
            visitor: $visitor,
            entryId: $entry->id,
            voteCategoryId: (int) $request->get('vote_category_id'),
            points: (int) $request->get('points', 0),
            comment: $request->get('comment', ''),
            specialVote: $request->has('special_vote') ? (bool) $request->get('special_vote') : null,
            isLive: (bool) $request->get('live', false),
            ipAddress: $request->ip(),
        );

        $status = $result['status'] ?? ($result['success'] ? 200 : 400);
        unset($result['status']);

        // Include 'error' key for frontend JS compatibility
        if (! $result['success']) {
            $result['error'] = true;
        }

        return response()->json($result, $status);
    }
}
