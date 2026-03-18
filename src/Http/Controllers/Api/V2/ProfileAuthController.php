<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Motor\Backend\Http\Controllers\Controller;
use Partymeister\Competitions\Models\AccessKey;
use Partymeister\Core\Http\Resources\Profile\VisitorResource;
use Partymeister\Core\Models\Visitor;

class ProfileAuthController extends Controller
{
    /**
     * Login and return a Sanctum personal access token.
     */
    public function login(Request $request): JsonResponse
    {
        if (! config('partymeister-core.visitor_login_enabled', false)) {
            return response()->json([
                'status'  => 503,
                'message' => 'Login is currently disabled',
            ], 503);
        }

        $name = $request->get('name');
        $password = $request->get('password');

        if (is_null($name) || is_null($password)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login or password not supplied',
            ], 403);
        }

        if (! Auth::guard('visitor')->attempt(['name' => $name, 'password' => $password])) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login unsuccessful',
            ], 403);
        }

        $visitor = Visitor::where('name', $name)->first();

        // Revoke any existing tokens for this visitor
        $visitor->tokens()->delete();

        // Create a new Sanctum personal access token
        $token = $visitor->createToken('mobile-app')->plainTextToken;

        $data = (new VisitorResource($visitor))->toArrayRecursive();

        return response()->json([
            'status'  => 200,
            'message' => 'Login successful',
            'data'    => $data,
            'token'   => $token,
        ], 200);
    }

    /**
     * Register a new visitor and return a Sanctum personal access token.
     */
    public function register(Request $request): JsonResponse
    {
        if (! config('partymeister-core.visitor_registration_enabled', false)) {
            return response()->json([
                'status'  => 503,
                'message' => 'Registration is currently disabled',
            ], 503);
        }

        $name = $request->get('name');
        $group = $request->get('group', '');
        $country = $request->get('country_iso_3166_1');
        $password = $request->get('password');
        $access_key = $request->get('access_key');

        if (is_null($name) || is_null($password) || is_null($access_key)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Login, password or access key missing',
            ], 403);
        }

        $visitor = Visitor::where('name', $name)->first();

        if (! is_null($visitor)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Profile already registered',
            ], 403);
        }

        $accessKey = AccessKey::where('access_key', $access_key)
            ->where('visitor_id', null)
            ->first();

        if (is_null($accessKey)) {
            return response()->json([
                'status'  => 403,
                'message' => 'Access key invalid',
            ], 403);
        }

        $visitor = new Visitor();
        $visitor->name = $name;
        $visitor->password = bcrypt($password);
        $visitor->group = $group;
        $visitor->country_iso_3166_1 = $country;
        $visitor->api_token = Str::random(60);
        $visitor->save();

        $accessKey->visitor_id = $visitor->id;
        $accessKey->registered_at = date('Y-m-d H:i:s');
        $accessKey->ip_address = $request->ip();
        $accessKey->save();

        // Create a Sanctum personal access token
        $token = $visitor->createToken('mobile-app')->plainTextToken;

        $data = (new VisitorResource($visitor))->toArrayRecursive();

        return response()->json([
            'status'  => 200,
            'message' => 'Registration successful',
            'data'    => $data,
            'token'   => $token,
        ], 200);
    }

    /**
     * Revoke the current token (logout).
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user('visitor')->currentAccessToken()->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Logged out',
        ], 200);
    }

    /**
     * Get the authenticated visitor's profile.
     */
    public function show(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');
        $data = (new VisitorResource($visitor))->toArrayRecursive();

        return response()->json([
            'status'  => 200,
            'message' => 'Profile loaded',
            'data'    => $data,
        ], 200);
    }

    /**
     * Delete the authenticated visitor's profile.
     */
    public function destroy(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');
        $visitor->tokens()->delete();
        $visitor->delete();

        return response()->json([
            'status'  => 200,
            'message' => 'Profile deleted',
        ], 200);
    }
}
