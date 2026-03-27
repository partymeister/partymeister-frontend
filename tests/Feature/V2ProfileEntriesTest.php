<?php

use Illuminate\Support\Str;
use Motor\Admin\Models\User;
use Partymeister\Competitions\Models\Competition;
use Partymeister\Competitions\Models\CompetitionType;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Core\Models\Visitor;
use Spatie\Permission\Models\Role;

pest()->group('V2', 'ProfileEntries');

beforeEach(function () {
    // Ensure a SuperAdmin user exists (required by BlameableTrait)
    if (! User::where('email', 'admin@motor-cms.com')->exists()) {
        $role = Role::firstOrCreate(['name' => 'SuperAdmin', 'guard_name' => 'web']);
        $user = User::factory()->create([
            'email' => 'admin@motor-cms.com',
            'name' => 'Admin',
        ]);
        $user->assignRole($role);
    }

    $visitor = Visitor::create([
        'name' => 'EntriesUser',
        'password' => bcrypt('secret'),
        'api_token' => Str::random(60),
        'group' => '',
        'country_iso_3166_1' => 'DE',
        'additional_data' => [],
    ]);

    $this->visitor = $visitor;
    $this->token = $visitor->createToken('test')->plainTextToken;
});

describe('V2 Profile Entries', function () {

    it('can list visitor entries (empty)', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonCount(0, 'data');
    });

    it('can list visitor entries when entries exist', function () {
        $competitionType = CompetitionType::create(['name' => 'Demo']);
        $competition = Competition::create([
            'name' => 'Test Compo',
            'competition_type_id' => $competitionType->id,
            'sort_position' => 1,
            'prizegiving_sort_position' => 1,
            'has_prizegiving' => false,
            'upload_enabled' => false,
            'voting_enabled' => false,
        ]);

        Entry::create([
            'competition_id' => $competition->id,
            'visitor_id' => $this->visitor->id,
            'title' => 'My Demo',
            'author' => 'EntriesUser',
            'description' => '',
            'organizer_description' => '',
            'custom_option' => '',
            'sort_position' => 1,
            'status' => 0,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonCount(1, 'data');
    });

    it('does not return entries belonging to other visitors', function () {
        $otherVisitor = Visitor::create([
            'name' => 'OtherUser',
            'password' => bcrypt('secret'),
            'api_token' => Str::random(60),
            'group' => '',
            'country_iso_3166_1' => 'DE',
            'additional_data' => [],
        ]);

        $competitionType = CompetitionType::create(['name' => 'Demo2']);
        $competition = Competition::create([
            'name' => 'Other Compo',
            'competition_type_id' => $competitionType->id,
            'sort_position' => 2,
            'prizegiving_sort_position' => 2,
            'has_prizegiving' => false,
            'upload_enabled' => false,
            'voting_enabled' => false,
        ]);

        Entry::create([
            'competition_id' => $competition->id,
            'visitor_id' => $otherVisitor->id,
            'title' => 'Other Entry',
            'author' => 'OtherUser',
            'description' => '',
            'organizer_description' => '',
            'custom_option' => '',
            'sort_position' => 1,
            'status' => 0,
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonCount(0, 'data');
    });

    it('returns 401 when not authenticated', function () {
        $response = $this->getJson('/api/v2/profile/entries');

        $response->assertStatus(401);
    });

});
