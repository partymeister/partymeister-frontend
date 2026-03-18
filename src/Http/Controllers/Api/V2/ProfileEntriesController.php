<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Motor\Backend\Http\Controllers\Controller;
use Partymeister\Competitions\Http\Resources\Profile\EntryResource;
use Partymeister\Competitions\Models\Entry;

class ProfileEntriesController extends Controller
{
    /**
     * Get the authenticated visitor's competition entries.
     */
    public function index(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');
        $entries = Entry::where('visitor_id', $visitor->id)->get();

        return response()->json(EntryResource::collection($entries));
    }
}
