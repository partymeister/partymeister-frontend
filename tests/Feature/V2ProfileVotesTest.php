<?php

use Motor\Admin\Models\User;
use Partymeister\Competitions\Models\Competition;
use Partymeister\Competitions\Models\Entry;
use Partymeister\Competitions\Models\LiveVote;
use Partymeister\Competitions\Models\Vote;
use Partymeister\Competitions\Models\VoteCategory;
use Partymeister\Core\Models\Visitor;
use Spatie\Permission\Models\Role;

pest()->group('V2', 'ProfileVotes');

beforeEach(function () {
    config()->set('partymeister-competitions-voting.deadline', date('Y-m-d H:i:s', strtotime('+1 hour')));

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
        'name' => 'VoteUser',
        'password' => bcrypt('secret'),
    ]);

    $this->visitor = $visitor;
    $this->token = $visitor->createToken('test')->plainTextToken;

    // Set up competition with voting enabled
    $competition = Competition::factory()->create([
        'name' => 'Vote Compo',
        'voting_enabled' => true,
    ]);

    $voteCategory = VoteCategory::factory()->create([
        'name' => 'Overall',
        'points' => 10,
    ]);

    // Attach vote category to competition
    $competition->vote_categories()->attach($voteCategory->id);

    $entry = Entry::factory()->create([
        'competition_id' => $competition->id,
        'title' => 'Awesome Demo',
        'author' => 'Demo Author',
        'status' => 1, // qualified
    ]);

    $this->competition = $competition;
    $this->entry = $entry;
    $this->voteCategory = $voteCategory;
});

// ─────────────────────────────────────────────
// Live vote
// ─────────────────────────────────────────────

describe('V2 Profile Votes - Live', function () {

    it('returns 204 when no live vote is active', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/votes/live');

        $response->assertNoContent();
    });

    it('returns 204 when live vote exists but competition is null', function () {
        // Create a LiveVote with no valid competition_id
        $liveVote = new LiveVote;
        $liveVote->competition_id = 999999;
        $liveVote->entry_id = $this->entry->id;
        $liveVote->sort_position = 1;
        $liveVote->title = 'Test';
        $liveVote->author = 'Author';
        $liveVote->save();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/votes/live');

        $response->assertNoContent();
    });

    it('returns 401 when not authenticated for live vote', function () {
        $response = $this->getJson('/api/v2/profile/votes/live');

        $response->assertStatus(401);
    });

});

// ─────────────────────────────────────────────
// Voteable entries
// ─────────────────────────────────────────────

describe('V2 Profile Votes - Entries', function () {

    it('can get voteable entries', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/votes/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        expect($response->json('data'))->toBeArray();
        $response->assertJsonCount(1, 'data');
    });

    it('returns empty array when no voting-enabled entries exist', function () {
        // Mark the entry as not qualified
        $this->entry->status = 0;
        $this->entry->save();

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v2/profile/votes/entries');

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonCount(0, 'data');
    });

    it('returns 401 when not authenticated for vote entries', function () {
        $response = $this->getJson('/api/v2/profile/votes/entries');

        $response->assertStatus(401);
    });

});

// ─────────────────────────────────────────────
// Submit vote
// ─────────────────────────────────────────────

describe('V2 Profile Votes - Submit', function () {

    it('can submit a vote', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v2/profile/votes/{$this->entry->id}", [
                'vote_category_id' => $this->voteCategory->id,
                'points' => 8,
            ]);

        $response->assertOk();
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('data.success', true);

        expect(Vote::where('visitor_id', $this->visitor->id)
            ->where('entry_id', $this->entry->id)
            ->count())->toBe(1);
    });

    it('validates required vote_category_id on vote', function () {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v2/profile/votes/{$this->entry->id}", [
                'points' => 5,
            ]);

        $response->assertStatus(422);
    });

    it('returns 401 when not authenticated for vote submit', function () {
        $response = $this->postJson("/api/v2/profile/votes/{$this->entry->id}", [
            'vote_category_id' => $this->voteCategory->id,
            'points' => 5,
        ]);

        $response->assertStatus(401);
    });

    it('rejects vote when deadline has passed', function () {
        config()->set('partymeister-competitions-voting.deadline', date('Y-m-d H:i:s', strtotime('-1 hour')));

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v2/profile/votes/{$this->entry->id}", [
                'vote_category_id' => $this->voteCategory->id,
                'points' => 5,
            ]);

        $response->assertStatus(403);
        assertV2ResponseEnvelope($response);
        $response->assertJsonPath('error.code', 'VOTE_FAILED');
    });

    it('can update an existing vote', function () {
        // First vote
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v2/profile/votes/{$this->entry->id}", [
                'vote_category_id' => $this->voteCategory->id,
                'points' => 3,
            ]);

        // Update vote
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson("/api/v2/profile/votes/{$this->entry->id}", [
                'vote_category_id' => $this->voteCategory->id,
                'points' => 9,
            ]);

        $response->assertOk();
        assertV2ResponseEnvelope($response);

        // Should still only be one vote record
        expect(Vote::where('visitor_id', $this->visitor->id)
            ->where('entry_id', $this->entry->id)
            ->count())->toBe(1);

        expect((int) Vote::where('visitor_id', $this->visitor->id)
            ->where('entry_id', $this->entry->id)
            ->value('points'))->toBe(9);
    });

});
