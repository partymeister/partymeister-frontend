<?php

namespace Partymeister\Frontend\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use League\Fractal\Manager;
use League\Fractal\Resource\Collection;
use Motor\Backend\Http\Controllers\Controller;
use Partymeister\Competitions\Models\AccessKey;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Competitions\Models\LiveVote;
use Partymeister\Competitions\Models\Vote;
use Partymeister\Competitions\Models\VoteCategory;
use Partymeister\Competitions\Transformers\Entry\OldApiTransformer;
use Partymeister\Competitions\Transformers\Entry\SimpleTransformer;
use Partymeister\Core\Models\Visitor;
use Partymeister\Core\Transformers\VisitorTransformer;
use Illuminate\Support\Facades\Log;

/**
 * Class ProfileController
 * @package Partymeister\Frontend\Http\Controllers\Api
 */
class ProfileController extends Controller
{

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function login(Request $request)
    {
        // Get login from payload
        $login = $request->get('login');

        // Get password from payload
        $password = $request->get('password');

        // Return error if any data is missing
        if (is_null($login) || is_null($password)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login or password not supplied'
            ], 403);
        }

        if (! Auth::guard('visitor')->attempt([
            'name'     => $login,
            'password' => $password
        ])) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login unsuccessful'
            ], 403);
        }

        $visitor = Visitor::where('name', $login)->first();

        $data = fractal($visitor, new VisitorTransformer())->toArray();

        return response()->json([
                'status'  => 200,
                'message' => 'Login successful'
            ] + $data, 200);
    }


    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request)
    {
        // Get handle from payload
        $login = $request->get('login');

        // Get groups from payload
        $groups = $request->get('groups');

        // Get country from payload
        $country = $request->get('country');

        // Get password from payload
        $password = $request->get('password');

        // Get access-key from payload
        $access_key = $request->get('access_key');

        // Return error if any data is missing
        if (is_null($login) || is_null($password) || is_null($access_key)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login, password or access key missing'
            ], 403);
        }

        // Find visitor
        $visitor = Visitor::where('name', $login)->first();

        // Check if user is already registered
        if (! is_null($visitor)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Profile already registered'
            ], 403);
        }

        // Check if the access key is valid
        $accessKey = AccessKey::where('access_key', $access_key)->where('visitor_id', null)->first();

        if (is_null($accessKey)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Access key invalid'
            ], 403);
        }

        // Create user
        $visitor = new Visitor();
        $visitor->name = $login;
        $visitor->password = bcrypt($password);
        $visitor->group = $groups;
        $visitor->country_iso_3166_1 = $country;
        $visitor->api_token = Str::random(60);
        $visitor->save();

        $accessKey->visitor_id = $visitor->id;
        $accessKey->registered_at = date('Y-m-d H:i:s');
        $accessKey->ip_address = $request->ip();
        $accessKey->save();

        $data = fractal($visitor, new VisitorTransformer())->toArray();

        return response()->json([
                'status'  => 200,
                'message' => 'Profile registered'
            ] + $data, 200);
    }


    /**
     * @param $api_token
     * @return JsonResponse
     */
    public function entries($api_token)
    {
        // Check if token exists and load visitor
        $visitor = Visitor::where('api_token', $api_token)->first();

        if (is_null($visitor)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Profile not found'
            ], 404);
        }

        $data = fractal($visitor->entries, new OldApiTransformer())->toArray();

        return response()->json([
                'status'  => 200,
                'message' => 'Entries loaded'
            ] + $data);
    }


    /**
     * @param $api_token
     * @return JsonResponse
     */
    public function vote_live($api_token)
    {
        // Check if token exists and load visitor
        $visitor = Visitor::where('api_token', $api_token)->first();

        if (is_null($visitor)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Profile not found'
            ], 404);
        }

        $live_voting = LiveVote::first();

        if (is_null($live_voting)) {
            return response()->json([
                'status'  => 204,
                'message' => 'No entries found'
            ], 204);
        }

        $competition = $live_voting->competition;
        if ($competition->voting_enabled == true && strtotime($competition->updated_at) <= time() - 300) { // allow live voting to stay open for 5 minutes
            return response()->json([
                'status'  => 204,
                'message' => 'No entries found'
            ], 204);
        }
        $entries = $live_voting->competition->entries()
            ->where('status', '=', 1)
            ->where('sort_position', '<=', $live_voting->sort_position)
            ->orderBy('sort_position', 'DESC')
            ->get();

        $fractal = new Manager();
        $fractal->parseIncludes('vote:visitor_id('.$visitor->id.')');
        $resource = new Collection($entries, new SimpleTransformer());

        return response()->json([
                'status'  => 200,
                'message' => 'Livevotes loaded'
            ] + $fractal->createData($resource)->toArray());
    }


    /**
     * @param Request $request
     * @param         $api_token
     * @return JsonResponse
     */
    public function vote_entries(Request $request, $api_token)
    {
        // Check if token exists and load visitor
        $visitor = Visitor::where('api_token', $api_token)->first();

        if (is_null($visitor)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Profile not found'
            ], 404);
	}
	Log::info('VisitorID', [$visitor->id]);
        $query = DB::table('entries')
            ->select('entries.id')
            ->join('competitions', 'entries.competition_id', '=', 'competitions.id')
            ->where('competitions.voting_enabled', true)
            ->where('entries.status', 1);

        if (! is_null($request->get('competition_id'))) {
            $query->where('competition_id', $request->get('competition_id'));
        }

        $query->orderBy('entries.competition_id', 'ASC')->orderBy('entries.sort_position', 'ASC');

        $entryIds = $query->get()->pluck('id');

        $entries = Entry::whereIn('id', $entryIds)->get();

	$fractal = new Manager();

	Log::info('VisitorIdAgain', [$visitor->id]);



        $fractal->parseIncludes('vote:visitorid('.$visitor->id.')');
        $resource = new Collection($entries, new SimpleTransformer($visitor->id));

        return response()->json([
                'status'  => 200,
                'message' => 'Votes loaded'
            ] + $fractal->createData($resource)->toArray());
    }


    /**
     * @param Request $request
     * @param         $api_token
     * @param Entry   $entry
     * @return JsonResponse
     */
    public function vote_save(Request $request, $api_token, Entry $entry)
    {
        // Check if token exists and load visitor
        $visitor = Visitor::where('api_token', $api_token)->first();

        if (is_null($visitor)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Profile not found'
            ], 404);
        }

        // Get points from payload
        $points = $request->get('points');

        // Get vote category from payload
        $vote_category_id = $request->get('vote_category_id');

        if (time() > strtotime(config('partymeister-competitions-voting.deadline'))) {
            return response()->json([
                'status'  => 403,
                'message' => 'Voting deadline over'
            ], 403);
        }

        // Check if the points are valid
        $voteCategory = VoteCategory::find($vote_category_id);
        if (is_null($voteCategory)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Invalid vote category'
            ], 404);
        }

        $points = min($points, $voteCategory->points);

        if (is_null($entry)) {
            return response()->json([
                'status'  => 404,
                'message' => 'Entry not found'
            ], 404);
        }

        if ($request->get('favourite')) {
            foreach ($visitor->votes()->where('special_vote', true)->get() as $vote) {
                $vote->special_vote = false;
                $vote->save();
            }
        }

        // Create new vote item if this one doesn't exist yet
        $vote = $visitor->votes()->where('vote_category_id', $voteCategory->id)->where('entry_id', $entry->id)->first();
        if (is_null($vote)) {
            $vote = new Vote();
            $vote->visitor_id = $visitor->id;
            $vote->competition_id = $entry->competition_id;
            $vote->entry_id = $entry->id;
            $vote->ip_address = $request->ip();
        }
        $vote->points = $points;
        $vote->vote_category_id = $voteCategory->id;
        $vote->comment = $request->get('comment', '');
        $vote->special_vote = $request->get('favourite', 0);

        $vote->save();

        return response()->json([ 'status' => 200, 'message' => 'Vote saved' ]);
    }
}
