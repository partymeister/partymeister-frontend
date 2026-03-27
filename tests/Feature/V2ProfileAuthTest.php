<?php

use Illuminate\Support\Str;
use Illuminate\Testing\TestResponse;
use Motor\Admin\Models\User;
use Partymeister\Competitions\Models\AccessKey;
use Partymeister\Core\Models\Visitor;
use Spatie\Permission\Models\Role;

pest()->group('V2', 'ProfileAuth');

function makeVisitor(string $name = 'TestUser', string $password = 'secret'): Visitor
{
    $visitor = Visitor::create([
        'name' => $name,
        'password' => bcrypt($password),
        'api_token' => Str::random(60),
        'group' => '',
        'country_iso_3166_1' => 'DE',
        'additional_data' => [],
    ]);

    return $visitor;
}

function asVisitor(Visitor $visitor): TestResponse
{
    // Just return the token — callers will use withHeader themselves.
    // This helper creates a Sanctum token for the visitor.
    return $visitor->createToken('test')->plainTextToken; // @phpstan-ignore-line
}

function visitorToken(Visitor $visitor): string
{
    return $visitor->createToken('test')->plainTextToken;
}

beforeEach(function () {
    config()->set('partymeister-core.visitor_login_enabled', true);
    config()->set('partymeister-core.visitor_registration_enabled', true);

    // Ensure a SuperAdmin user exists (required by BlameableTrait on some models)
    if (! User::where('email', 'admin@motor-cms.com')->exists()) {
        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'email' => 'admin@motor-cms.com',
            'name' => 'Admin',
        ]);
        $user->assignRole($role);
    }
});

// ─────────────────────────────────────────────
// Register
// ─────────────────────────────────────────────

describe('V2 Profile Register', function () {

    it('can register with valid access key', function () {
        $accessKey = AccessKey::create([
            'access_key' => 'TEST-KEY1',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'NewUser',
            'password' => 'secret123',
            'access_key' => 'TEST-KEY1',
            'country_iso_3166_1' => 'DE',
        ]);

        $response->assertStatus(201);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('meta.token', fn ($token) => ! empty($token));
        $response->assertJsonPath('data.name', 'NewUser');
    });

    it('rejects registration with invalid access key', function () {
        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'NewUser',
            'password' => 'secret123',
            'access_key' => 'INVALID-KEY',
        ]);

        $response->assertStatus(422);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    });

    it('rejects registration with already used access key', function () {
        $visitor = makeVisitor('ExistingUser');
        $accessKey = AccessKey::create([
            'access_key' => 'USED-KEY1',
            'ip_address' => '127.0.0.1',
            'visitor_id' => $visitor->id,
            'registered_at' => now(),
        ]);

        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'NewUser',
            'password' => 'secret123',
            'access_key' => 'USED-KEY1',
            'country_iso_3166_1' => 'DE',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error.code', 'VALIDATION_ERROR');
    });

    it('rejects registration with duplicate name', function () {
        makeVisitor('TakenUser');

        $accessKey = AccessKey::create([
            'access_key' => 'FREE-KEY1',
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'TakenUser',
            'password' => 'secret123',
            'access_key' => 'FREE-KEY1',
            'country_iso_3166_1' => 'DE',
        ]);

        $response->assertStatus(409);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'CONFLICT');
    });

    it('returns 503 when registration is disabled', function () {
        config()->set('partymeister-core.visitor_registration_enabled', false);

        $response = $this->postJson('/api/v2/profile/register', [
            'name' => 'NewUser',
            'password' => 'secret123',
            'access_key' => 'SOME-KEY',
        ]);

        $response->assertStatus(503);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'SERVICE_UNAVAILABLE');
    });

    it('validates required fields on register', function () {
        $response = $this->postJson('/api/v2/profile/register', []);

        $response->assertStatus(422);
    });

});

// ─────────────────────────────────────────────
// Login
// ─────────────────────────────────────────────

describe('V2 Profile Login', function () {

    it('can login with valid credentials', function () {
        makeVisitor('LoginUser', 'mypassword');

        $response = $this->postJson('/api/v2/profile/login', [
            'name' => 'LoginUser',
            'password' => 'mypassword',
        ]);

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('data.name', 'LoginUser');
        $response->assertJsonStructure(['meta' => ['token']]);
        expect($response->json('meta.token'))->not->toBeEmpty();
    });

    it('rejects login with wrong password', function () {
        makeVisitor('LoginUser2', 'correctpassword');

        $response = $this->postJson('/api/v2/profile/login', [
            'name' => 'LoginUser2',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(401);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'UNAUTHORIZED');
    });

    it('returns 503 when login is disabled', function () {
        config()->set('partymeister-core.visitor_login_enabled', false);

        $response = $this->postJson('/api/v2/profile/login', [
            'name' => 'Anyone',
            'password' => 'anything',
        ]);

        $response->assertStatus(503);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'SERVICE_UNAVAILABLE');
    });

    it('validates required fields on login', function () {
        $response = $this->postJson('/api/v2/profile/login', []);

        $response->assertStatus(422);
    });

});

// ─────────────────────────────────────────────
// Profile Show / Logout / Destroy
// ─────────────────────────────────────────────

describe('V2 Profile Authenticated', function () {

    it('can show profile when authenticated', function () {
        $visitor = makeVisitor('ProfileUser');
        $token = visitorToken($visitor);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/v2/profile');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('data.name', 'ProfileUser');
    });

    it('can logout', function () {
        $visitor = makeVisitor('LogoutUser');
        $token = visitorToken($visitor);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/v2/profile/logout');

        $response->assertNoContent();
    });

    it('can delete profile', function () {
        $visitor = makeVisitor('DeleteMe');
        $token = visitorToken($visitor);
        $visitorId = $visitor->id;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/v2/profile');

        $response->assertNoContent();
        expect(Visitor::find($visitorId))->toBeNull();
    });

    it('returns 401 when not authenticated on profile show', function () {
        $response = $this->getJson('/api/v2/profile');

        $response->assertStatus(401);
    });

    it('returns 401 when not authenticated on logout', function () {
        $response = $this->postJson('/api/v2/profile/logout');

        $response->assertStatus(401);
    });

    it('returns 401 when not authenticated on profile delete', function () {
        $response = $this->deleteJson('/api/v2/profile');

        $response->assertStatus(401);
    });

});
