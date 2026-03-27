<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Motor\Core\Http\Controllers\Api\V2\ApiController;
use Partymeister\Competitions\Http\Resources\Profile\EntryResource;
use Partymeister\Competitions\Models\Entry;

class ProfileEntriesController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');
        $entries = Entry::where('visitor_id', $visitor->id)
            ->with('competition.competition_type')
            ->get();

        return response()->json([
            'data' => EntryResource::collection($entries),
            'meta' => [
                'api_version' => 'v2',
                'message' => 'Entries loaded',
            ],
        ]);
    }
}
