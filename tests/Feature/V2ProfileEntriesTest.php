<?php

use Motor\Admin\Models\User;
use Partymeister\Competitions\Models\Competition;
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

    $visitor = Visitor::factory()->create([
        'name' => 'EntriesUser',
        'password' => bcrypt('secret'),
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
        $competition = Competition::factory()->create(['name' => 'Test Compo']);

        Entry::factory()->create([
            'competition_id' => $competition->id,
            'visitor_id' => $this->visitor->id,
            'title' => 'My Demo',
            'author' => 'EntriesUser',
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonCount(1, 'data');
    });

    it('does not return entries belonging to other visitors', function () {
        $otherVisitor = Visitor::factory()->create(['name' => 'OtherUser']);

        $competition = Competition::factory()->create(['name' => 'Other Compo']);

        Entry::factory()->create([
            'competition_id' => $competition->id,
            'visitor_id' => $otherVisitor->id,
            'title' => 'Other Entry',
            'author' => 'OtherUser',
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
