<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Motor\Core\Http\Controllers\Api\V2\ApiController;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Frontend\Http\Resources\V2\EntryCollection;

/**
 * @tags Visitor Profile
 */
class ProfileEntriesController extends ApiController
{
    /** @response EntryCollection */
    public function index(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');
        $entries = Entry::where('visitor_id', $visitor->id)
            ->with('competition.competition_type')
            ->get();

        return (new EntryCollection($entries))
            ->additional(['meta' => ['message' => 'Entries loaded']])
            ->response();
    }
}
