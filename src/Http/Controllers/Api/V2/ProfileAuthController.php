<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Motor\Core\Http\Controllers\Api\V2\ApiController;
use Partymeister\Competitions\Models\AccessKey;
use Partymeister\Core\Http\Resources\Profile\VisitorResource;
use Partymeister\Core\Models\Visitor;
use Partymeister\Frontend\Http\Requests\Api\V2\LoginPostRequest;
use Partymeister\Frontend\Http\Requests\Api\V2\RegisterPostRequest;

class ProfileAuthController extends ApiController
{
    public function login(LoginPostRequest $request): JsonResponse
    {
        if (! config('partymeister-core.visitor_login_enabled', false)) {
            return $this->errorResponse('SERVICE_UNAVAILABLE', 'Login is currently disabled', 503);
        }

        if (! Auth::guard('visitor')->attempt($request->only('name', 'password'))) {
            return $this->errorResponse('UNAUTHORIZED', 'Login unsuccessful', 401);
        }

        $visitor = Visitor::where('name', $request->get('name'))->first();
        $visitor->tokens()->delete();
        $token = $visitor->createToken('mobile-app')->plainTextToken;

        return (new VisitorResource($visitor))
            ->additional(['meta' => ['message' => 'Login successful', 'token' => $token]])
            ->response();
    }

    public function register(RegisterPostRequest $request): JsonResponse
    {
        if (! config('partymeister-core.visitor_registration_enabled', false)) {
            return $this->errorResponse('SERVICE_UNAVAILABLE', 'Registration is currently disabled', 503);
        }

        if (Visitor::where('name', $request->get('name'))->exists()) {
            return $this->errorResponse('CONFLICT', 'Profile already registered', 409);
        }

        $accessKey = AccessKey::where('access_key', $request->get('access_key'))
            ->whereNull('visitor_id')
            ->first();

        if (is_null($accessKey)) {
            return $this->errorResponse('VALIDATION_ERROR', 'Access key invalid', 422);
        }

        $visitor = new Visitor();
        $visitor->name = $request->get('name');
        $visitor->password = bcrypt($request->get('password'));
        $visitor->group = $request->get('group', '');
        $visitor->country_iso_3166_1 = $request->get('country_iso_3166_1');
        $visitor->api_token = Str::random(60);
        $visitor->save();

        $accessKey->visitor_id = $visitor->id;
        $accessKey->registered_at = date('Y-m-d H:i:s');
        $accessKey->ip_address = $request->ip();
        $accessKey->save();

        $token = $visitor->createToken('mobile-app')->plainTextToken;

        return (new VisitorResource($visitor))
            ->additional(['meta' => ['message' => 'Registration successful', 'token' => $token]])
            ->response()
            ->setStatusCode(201);
    }

    public function logout(Request $request): Response
    {
        $request->user('visitor')->currentAccessToken()->delete();

        return response()->noContent();
    }

    public function show(Request $request): JsonResponse
    {
        $visitor = $request->user('visitor');

        return (new VisitorResource($visitor))
            ->additional(['meta' => ['message' => 'Profile loaded']])
            ->response();
    }

    public function destroy(Request $request): Response
    {
        $visitor = $request->user('visitor');
        $visitor->tokens()->delete();
        $visitor->delete();

        return response()->noContent();
    }
}
