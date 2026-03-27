# Partymeister Frontend V2 API Standardization Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Standardize the existing partymeister-frontend V2 API controllers to use the V2 response envelope, V2ErrorHandler middleware, and FormRequest validation — while preserving the exact same `data` payload shapes the mobile app already consumes.

**Architecture:** The 3 existing V2 controllers are refactored in-place. Response wrapping changes from hand-built `{status, message, data}` to the standard V2 envelope `{data, meta: {api_version, message}}`. Error responses go through V2ErrorHandler middleware. Inline validation moves to FormRequest classes. The inner `data` field contents (VisitorResource, Profile\EntryResource, Vote\EntryResource) remain unchanged. Auth flow (Sanctum tokens, EnsureVisitorAuthenticated middleware) stays as-is.

**Tech Stack:** Laravel 12, Pest 4, Sanctum, motor-core V2 base classes

---

## What changes vs. what stays the same

### Stays the same
- All route paths (`/api/v2/profile/*`)
- Auth middleware stack (EnsureFrontendRequestsAreStateful, auth:sanctum, EnsureVisitorAuthenticated)
- Throttle on login/register (5 per minute)
- `data` payload shapes from VisitorResource, Profile\EntryResource, Vote\EntryResource
- VoteService::submitVote() business logic
- Live voting 5-minute expiration logic
- Token creation/revocation flow

### Changes
- Response envelope: `{status, message, data}` → `{data, meta: {api_version: 'v2', message}}`
- Login/register: `token` moves inside `meta` (was top-level)
- Error responses: hand-built `{status, message}` → V2ErrorHandler produces `{error: {code, message}, meta: {api_version}}`
- EnsureVisitorAuthenticated: error response updated to match V2 envelope
- V2ErrorHandler middleware added to route groups
- Inline validation → FormRequest classes
- Controllers extend `Motor\Core\Http\Controllers\Api\V2\ApiController` (gets HandlesApiErrors trait)
- Profile\EntryResource bug fix: `author_city` maps to `author_zip` → fixed
- Tests added

## File Inventory

### New Files (8)
- `src/Http/Requests/Api/V2/LoginPostRequest.php`
- `src/Http/Requests/Api/V2/RegisterPostRequest.php`
- `src/Http/Requests/Api/V2/VotePostRequest.php`
- `tests/Feature/V2ProfileAuthTest.php`
- `tests/Feature/V2ProfileEntriesTest.php`
- `tests/Feature/V2ProfileVotesTest.php`

### Modified Files (5)
- `src/Http/Controllers/Api/V2/ProfileAuthController.php`
- `src/Http/Controllers/Api/V2/ProfileEntriesController.php`
- `src/Http/Controllers/Api/V2/ProfileVotesController.php`
- `src/Http/Middleware/EnsureVisitorAuthenticated.php`
- `routes/api.php`

### Modified Files in other packages (1)
- `packages/partymeister-competitions/src/Http/Resources/Profile/EntryResource.php` (bug fix line 180)

---

## Phase 1: Request Classes + Bug Fix

### Task 1.1: Create FormRequest classes

**Files:**
- Create: `src/Http/Requests/Api/V2/LoginPostRequest.php`
- Create: `src/Http/Requests/Api/V2/RegisterPostRequest.php`
- Create: `src/Http/Requests/Api/V2/VotePostRequest.php`

- [ ] **Step 1: Create LoginPostRequest**

```php
<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class LoginPostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string',
        ];
    }
}
```

- [ ] **Step 2: Create RegisterPostRequest**

```php
<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class RegisterPostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:4',
            'access_key' => 'required|string',
            'group' => 'nullable|string|max:255',
            'country_iso_3166_1' => 'nullable|string|max:2',
        ];
    }
}
```

- [ ] **Step 3: Create VotePostRequest**

```php
<?php

namespace Partymeister\Frontend\Http\Requests\Api\V2;

use Illuminate\Foundation\Http\FormRequest;

class VotePostRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'vote_category_id' => 'required|integer|exists:vote_categories,id',
            'points' => 'integer',
            'comment' => 'nullable|string',
            'special_vote' => 'nullable|boolean',
            'live' => 'nullable|boolean',
        ];
    }
}
```

- [ ] **Step 4: Commit**

### Task 1.2: Fix Profile\EntryResource bug

**Files:**
- Modify: `packages/partymeister-competitions/src/Http/Resources/Profile/EntryResource.php:180`

- [ ] **Step 1: Fix the bug**

Line 180 currently reads:
```php
'author_city' => $this->author_zip,
```

Change to:
```php
'author_city' => $this->author_city,
```

- [ ] **Step 2: Commit in competitions submodule**

---

## Phase 2: Controller Refactoring

### Task 2.1: Refactor ProfileAuthController

**Files:**
- Modify: `src/Http/Controllers/Api/V2/ProfileAuthController.php`

- [ ] **Step 1: Refactor the controller**

Key changes:
1. Extend `Motor\Core\Http\Controllers\Api\V2\ApiController` instead of `Motor\Admin\Http\Controllers\Controller`
2. Don't set `$model`/`$modelResource` (auth controller, no resource authorization)
3. Use `LoginPostRequest` and `RegisterPostRequest` type hints
4. Use V2 response envelope for all success responses
5. Use `$this->errorResponse()` from HandlesApiErrors trait for business logic errors
6. Move `token` into `meta` on login/register responses

Refactored controller:

```php
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
```

**Response format changes:**
- Login: was `{status:200, message, data, token}` → now `{data:{...visitor}, meta:{api_version:'v2', message, token}}`
- Register: same shape, now returns 201
- Logout: was `{status:200, message}` → now 204 No Content
- Show: was `{status:200, message, data}` → now `{data:{...visitor}, meta:{api_version:'v2', message}}`
- Destroy: was `{status:200, message}` → now 204 No Content
- Errors: was `{status:403, message}` → now `{error:{code, message}, meta:{api_version:'v2'}}`
- Login failure: 403 → 401 (correct HTTP semantics)

- [ ] **Step 2: Commit**

### Task 2.2: Refactor ProfileEntriesController

**Files:**
- Modify: `src/Http/Controllers/Api/V2/ProfileEntriesController.php`

- [ ] **Step 1: Refactor**

```php
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
```

Note: We eager-load `competition.competition_type` because the Profile\EntryResource accesses `$this->competition->name` and `$this->competition->competition_type->has_composer`. This prevents N+1 queries.

- [ ] **Step 2: Commit**

### Task 2.3: Refactor ProfileVotesController

**Files:**
- Modify: `src/Http/Controllers/Api/V2/ProfileVotesController.php`

- [ ] **Step 1: Refactor**

```php
<?php

namespace Partymeister\Frontend\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Motor\Core\Http\Controllers\Api\V2\ApiController;
use Partymeister\Competitions\Http\Resources\Vote\EntryResource as VoteEntryResource;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Competitions\Models\LiveVote;
use Partymeister\Competitions\Services\VoteService;
use Partymeister\Frontend\Http\Requests\Api\V2\VotePostRequest;

class ProfileVotesController extends ApiController
{
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

        return response()->json([
            'data' => VoteEntryResource::collection($entries),
            'meta' => [
                'api_version' => 'v2',
                'message' => 'Live votes loaded',
            ],
        ]);
    }

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

        return response()->json([
            'data' => VoteEntryResource::collection($entries),
            'meta' => [
                'api_version' => 'v2',
                'message' => 'Voteable entries loaded',
            ],
        ]);
    }

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
}
```

Key changes:
- `live()`: eager-loads `competition.vote_categories` and `competition.competition_type` to prevent N+1 in VoteEntryResource
- `entries()`: same eager loading
- `vote()`: uses VotePostRequest, `$request->validated()`, V2 error envelope for failures
- All responses use V2 envelope

- [ ] **Step 2: Commit**

### Task 2.4: Update middleware and routes

**Files:**
- Modify: `src/Http/Middleware/EnsureVisitorAuthenticated.php`
- Modify: `routes/api.php`

- [ ] **Step 1: Update EnsureVisitorAuthenticated to use V2 error envelope**

```php
// Change the error response from:
return response()->json([
    'status'  => 401,
    'message' => 'Visitor authentication required',
], 401);

// To V2 envelope:
return response()->json([
    'error' => [
        'code' => 'UNAUTHORIZED',
        'message' => 'Visitor authentication required',
    ],
    'meta' => [
        'api_version' => 'v2',
    ],
], 401);
```

- [ ] **Step 2: Add V2ErrorHandler middleware to route groups**

In `routes/api.php`, add `\Motor\Core\Http\Middleware\V2\V2ErrorHandler::class` to both route groups' middleware arrays. This ensures FormRequest validation errors automatically get the V2 error envelope.

The authenticated group becomes:
```php
Route::group([
    'middleware' => [
        \Motor\Core\Http\Middleware\V2\V2ErrorHandler::class,
        EnsureFrontendRequestsAreStateful::class,
        'auth:sanctum',
        'bindings',
        EnsureVisitorAuthenticated::class,
    ],
    // ...
```

The public group becomes:
```php
Route::group([
    'middleware' => [
        \Motor\Core\Http\Middleware\V2\V2ErrorHandler::class,
        EnsureFrontendRequestsAreStateful::class,
    ],
    // ...
```

- [ ] **Step 3: Commit**

---

## Phase 3: Tests

### Task 3.1: Create V2ProfileAuthTest

**Files:**
- Create: `tests/Feature/V2ProfileAuthTest.php`

- [ ] **Step 1: Write tests**

```php
<?php

use Partymeister\Competitions\Models\AccessKey;
use Partymeister\Core\Models\Visitor;

pest()->group('V2', 'ProfileAuth');

beforeEach(function () {
    config()->set('partymeister-core.visitor_login_enabled', true);
    config()->set('partymeister-core.visitor_registration_enabled', true);
});

describe('V2 Profile Auth API', function () {

    it('can register with valid access key', function () {
        $accessKey = AccessKey::create([
            'access_key' => 'TEST-1234',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'TestVisitor',
            'password' => 'secret',
            'access_key' => 'TEST-1234',
            'group' => 'TestGroup',
            'country_iso_3166_1' => 'DE',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonStructure(['data' => ['id', 'name', 'group'], 'meta' => ['token']]);

        expect(Visitor::where('name', 'TestVisitor')->exists())->toBeTrue();
    });

    it('rejects registration with invalid access key', function () {
        $this->postJson('/api/v2/profile/register', [
            'name' => 'TestVisitor',
            'password' => 'secret',
            'access_key' => 'INVALID',
        ])->assertStatus(422)
            ->assertJsonPath('error.code', 'VALIDATION_ERROR');
    });

    it('rejects registration with duplicate name', function () {
        AccessKey::create(['access_key' => 'KEY1', 'ip_address' => '']);
        Visitor::create(['name' => 'Taken', 'password' => bcrypt('pw'), 'api_token' => 'x']);

        $this->postJson('/api/v2/profile/register', [
            'name' => 'Taken',
            'password' => 'secret',
            'access_key' => 'KEY1',
        ])->assertStatus(409)
            ->assertJsonPath('error.code', 'CONFLICT');
    });

    it('returns 503 when registration disabled', function () {
        config()->set('partymeister-core.visitor_registration_enabled', false);

        $this->postJson('/api/v2/profile/register', [
            'name' => 'Test',
            'password' => 'pw',
            'access_key' => 'KEY',
        ])->assertStatus(503);
    });

    it('can login with valid credentials', function () {
        Visitor::create(['name' => 'User1', 'password' => bcrypt('pass'), 'api_token' => 'x']);

        $response = $this->postJson('/api/v2/profile/login', [
            'name' => 'User1',
            'password' => 'pass',
        ]);

        $response->assertOk()
            ->assertJsonPath('meta.api_version', 'v2')
            ->assertJsonStructure(['data' => ['id', 'name'], 'meta' => ['token']]);
    });

    it('rejects login with wrong password', function () {
        Visitor::create(['name' => 'User1', 'password' => bcrypt('pass'), 'api_token' => 'x']);

        $this->postJson('/api/v2/profile/login', [
            'name' => 'User1',
            'password' => 'wrong',
        ])->assertStatus(401)
            ->assertJsonPath('error.code', 'UNAUTHORIZED');
    });

    it('returns 503 when login disabled', function () {
        config()->set('partymeister-core.visitor_login_enabled', false);

        $this->postJson('/api/v2/profile/login', [
            'name' => 'User1',
            'password' => 'pass',
        ])->assertStatus(503);
    });

    it('validates required fields on login', function () {
        $this->postJson('/api/v2/profile/login', [])
            ->assertStatus(422);
    });

    it('can show profile when authenticated', function () {
        $visitor = Visitor::create(['name' => 'Me', 'password' => bcrypt('pw'), 'api_token' => 'x']);
        $token = $visitor->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/profile')
            ->assertOk()
            ->assertJsonPath('data.name', 'Me')
            ->assertJsonPath('meta.api_version', 'v2');
    });

    it('can logout', function () {
        $visitor = Visitor::create(['name' => 'Me', 'password' => bcrypt('pw'), 'api_token' => 'x']);
        $token = $visitor->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/profile/logout')
            ->assertNoContent();
    });

    it('can delete profile', function () {
        $visitor = Visitor::create(['name' => 'Me', 'password' => bcrypt('pw'), 'api_token' => 'x']);
        $token = $visitor->createToken('test')->plainTextToken;

        $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v2/profile')
            ->assertNoContent();

        expect(Visitor::where('name', 'Me')->exists())->toBeFalse();
    });
});
```

- [ ] **Step 2: Commit**

### Task 3.2: Create V2ProfileEntriesTest

**Files:**
- Create: `tests/Feature/V2ProfileEntriesTest.php`

- [ ] **Step 1: Write tests**

Test that authenticated visitor can list their entries and see entry data with competition name.

- [ ] **Step 2: Commit**

### Task 3.3: Create V2ProfileVotesTest

**Files:**
- Create: `tests/Feature/V2ProfileVotesTest.php`

- [ ] **Step 1: Write tests**

Test:
- `entries` endpoint returns voteable entries with V2 envelope
- `vote` endpoint submits a vote successfully
- `vote` endpoint validates required fields
- `live` endpoint returns 204 when no live vote active

Note: Voting tests need config setup:
```php
config()->set('partymeister-competitions-voting.deadline', date('Y-m-d H:i:s', strtotime('+1 hour')));
```

- [ ] **Step 2: Commit**

---

## Phase 4: Quality Verification

### Task 4.1: Register test path and run full suite

- [ ] **Step 1: Add frontend test path to root Pest.php**

Add `'../packages/partymeister-frontend/tests/Feature'` to `uses()->in()`.

- [ ] **Step 2: Run all V2 tests**

```bash
vendor/bin/pest packages/partymeister-frontend/tests/Feature/ --group=V2
```

- [ ] **Step 3: Run full V2 suite (all packages) to verify no regressions**

```bash
vendor/bin/pest --group=V2
```

### Task 4.2: Pint + push

- [ ] **Step 1: Run Pint**

```bash
vendor/bin/pint packages/partymeister-frontend/
```

- [ ] **Step 2: Run tests again after formatting**

- [ ] **Step 3: Commit and push submodule**

- [ ] **Step 4: Update parent repo and push**

---

## Response Format Migration Reference

### Before → After

**Login (success):**
```json
// Before
{"status": 200, "message": "Login successful", "data": {...visitor}, "token": "abc123"}

// After
{"data": {...visitor}, "meta": {"api_version": "v2", "message": "Login successful", "token": "abc123"}}
```

**Login (error):**
```json
// Before
{"status": 403, "message": "Login unsuccessful"}

// After
{"error": {"code": "UNAUTHORIZED", "message": "Login unsuccessful"}, "meta": {"api_version": "v2"}}
```

**Profile show:**
```json
// Before
{"status": 200, "message": "Profile loaded", "data": {...visitor}}

// After
{"data": {...visitor}, "meta": {"api_version": "v2", "message": "Profile loaded"}}
```

**Entries list:**
```json
// Before
[{...entry}, {...entry}]

// After
{"data": [{...entry}, {...entry}], "meta": {"api_version": "v2", "message": "Entries loaded"}}
```

**Vote (success):**
```json
// Before
{"success": true, "message": "You voted..."}

// After
{"data": {"success": true, "message": "You voted..."}, "meta": {"api_version": "v2", "message": "You voted..."}}
```

**Vote (error):**
```json
// Before
{"success": false, "message": "Voting deadline...", "error": true}

// After
{"error": {"code": "VOTE_FAILED", "message": "Voting deadline..."}, "meta": {"api_version": "v2"}}
```

**Logout / Destroy:**
```json
// Before
{"status": 200, "message": "Logged out"}

// After
204 No Content
```
