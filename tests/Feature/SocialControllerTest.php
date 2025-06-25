<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Gamertag;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class SocialControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
    }

    public function test_index_displays_social_hub_with_stats()
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();
        $requester = User::factory()->create();

        // Create accepted connection (friend)
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $friend1->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        UserConnection::factory()->create([
            'requester_id' => $friend2->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // Create pending received request
        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        // Create pending sent request
        $recipient = User::factory()->create();
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('social.index'));

        $response->assertStatus(200);
        $response->assertViewIs('social.index');
        $response->assertViewHas(['stats', 'pendingRequests', 'recentFriends']);

        // Check stats are calculated correctly
        $stats = $response->viewData('stats');
        $this->assertEquals(2, $stats['friends']);
        $this->assertEquals(1, $stats['pending_received']);
        $this->assertEquals(1, $stats['pending_sent']);
    }

    public function test_index_shows_pending_requests()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();

        // Create gamertags for context
        Gamertag::factory()->create(['user_id' => $requester->id]);

        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('social.index'));

        $response->assertStatus(200);
        $pendingRequests = $response->viewData('pendingRequests');
        $this->assertCount(1, $pendingRequests);
        $this->assertEquals($requester->id, $pendingRequests->first()->requester_id);
    }

    public function test_index_shows_recent_friends()
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        // Create gamertags for context
        Gamertag::factory()->create(['user_id' => $friend1->id]);
        Gamertag::factory()->create(['user_id' => $friend2->id]);

        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $friend1->id,
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => now()->subHour(),
        ]);

        UserConnection::factory()->create([
            'requester_id' => $friend2->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => now()->subMinutes(30),
        ]);

        $response = $this->actingAs($user)->get(route('social.index'));

        $response->assertStatus(200);
        $recentFriends = $response->viewData('recentFriends');
        $this->assertCount(2, $recentFriends);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get(route('social.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_search_returns_users_and_gamertags()
    {
        $currentUser = User::factory()->create();
        $searchUser = User::factory()->create(['name' => 'SearchableUser']);
        $otherUser = User::factory()->create(['name' => 'OtherUser']);

        // Create public gamertags
        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'gamertag' => 'SearchableTag',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $otherUser->id,
            'gamertag' => 'OtherTag',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => 'Searchable',
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertViewIs('social.search');
        $response->assertViewHas(['results', 'platforms', 'query', 'type', 'platform']);

        $results = $response->viewData('results');
        $this->assertArrayHasKey('users', $results);
        $this->assertArrayHasKey('gamertags', $results);
    }

    public function test_search_filters_by_type_users_only()
    {
        $currentUser = User::factory()->create();
        $searchUser = User::factory()->create(['name' => 'SearchableUser']);

        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'gamertag' => 'PublicTag',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => 'Searchable',
            'type' => 'users',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('users', $results);
        $this->assertArrayNotHasKey('gamertags', $results);
    }

    public function test_search_filters_by_type_gamertags_only()
    {
        $currentUser = User::factory()->create();
        $searchUser = User::factory()->create(['name' => 'SearchableUser']);

        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'gamertag' => 'SearchableTag',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => 'Searchable',
            'type' => 'gamertags',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $this->assertArrayHasKey('gamertags', $results);
        $this->assertArrayNotHasKey('users', $results);
    }

    public function test_search_filters_by_platform()
    {
        $currentUser = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        // Create gamertags for different platforms
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'gamertag' => 'SteamTag',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user2->id,
            'gamertag' => 'XboxTag',
            'platform' => 'xbox_live',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'platform' => 'steam',
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');

        // Should only return Steam users and gamertags
        $users = collect($results['users'] ?? []);
        $gamertags = collect($results['gamertags'] ?? []);

        $this->assertTrue($users->contains(fn($user) => $user['id'] === $user1->id));
        $this->assertFalse($users->contains(fn($user) => $user['id'] === $user2->id));

        $this->assertTrue($gamertags->contains(fn($gamertag) => $gamertag['platform'] === 'steam'));
        $this->assertFalse($gamertags->contains(fn($gamertag) => $gamertag['platform'] === 'xbox_live'));
    }

    public function test_search_excludes_current_user()
    {
        $currentUser = User::factory()->create(['name' => 'CurrentUser']);

        Gamertag::factory()->create([
            'user_id' => $currentUser->id,
            'gamertag' => 'CurrentUserTag',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => 'Current',
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');

        $users = collect($results['users'] ?? []);
        $gamertags = collect($results['gamertags'] ?? []);

        $this->assertFalse($users->contains(fn($user) => $user['id'] === $currentUser->id));
        $this->assertFalse($gamertags->contains(fn($gamertag) => $gamertag['user']['id'] === $currentUser->id));
    }

    public function test_search_excludes_private_gamertags()
    {
        $currentUser = User::factory()->create();
        $searchUser = User::factory()->create();

        // Create private gamertag
        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'gamertag' => 'PrivateTag',
            'is_public' => false,
        ]);

        // Create public gamertag
        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'gamertag' => 'PublicTag',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => 'Tag',
            'type' => 'gamertags',
        ]));

        $response->assertStatus(200);
        $results = $response->viewData('results');
        $gamertags = collect($results['gamertags'] ?? []);

        $this->assertTrue($gamertags->contains(fn($gamertag) => $gamertag['gamertag'] === 'PublicTag'));
        $this->assertFalse($gamertags->contains(fn($gamertag) => $gamertag['gamertag'] === 'PrivateTag'));
    }

    public function test_search_returns_json_for_ajax_request()
    {
        $currentUser = User::factory()->create();
        $searchUser = User::factory()->create(['name' => 'SearchableUser']);

        Gamertag::factory()->create([
            'user_id' => $searchUser->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->getJson(route('social.search', [
            'query' => 'Searchable',
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'results',
            'platforms',
            'query',
            'type',
            'platform',
        ]);
    }

    public function test_search_validates_input()
    {
        $currentUser = User::factory()->create();

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => str_repeat('a', 51), // Too long
            'type' => 'invalid_type',
            'platform' => 'invalid_platform',
        ]));

        $response->assertSessionHasErrors(['query', 'type', 'platform']);
    }

    public function test_search_requires_authentication()
    {
        $response = $this->get(route('social.search'));

        $response->assertRedirect(route('login'));
    }

    public function test_browse_displays_users_with_public_gamertags()
    {
        $currentUser = User::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $userWithPrivateOnly = User::factory()->create();

        // Create public gamertags
        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $user2->id,
            'is_public' => true,
        ]);

        // Create user with only private gamertags (should not appear)
        Gamertag::factory()->create([
            'user_id' => $userWithPrivateOnly->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.browse'));

        $response->assertStatus(200);
        $response->assertViewIs('social.browse');
        $response->assertViewHas(['users', 'platforms', 'platform']);

        $users = $response->viewData('users');
        $userIds = $users->pluck('id')->toArray();

        $this->assertContains($user1->id, $userIds);
        $this->assertContains($user2->id, $userIds);
        $this->assertNotContains($userWithPrivateOnly->id, $userIds);
        $this->assertNotContains($currentUser->id, $userIds); // Current user excluded
    }

    public function test_browse_filters_by_platform()
    {
        $currentUser = User::factory()->create();
        $steamUser = User::factory()->create();
        $xboxUser = User::factory()->create();

        Gamertag::factory()->create([
            'user_id' => $steamUser->id,
            'platform' => 'steam',
            'is_public' => true,
        ]);

        Gamertag::factory()->create([
            'user_id' => $xboxUser->id,
            'platform' => 'xbox_live',
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.browse', [
            'platform' => 'steam',
        ]));

        $response->assertStatus(200);
        $users = $response->viewData('users');
        $userIds = $users->pluck('id')->toArray();

        $this->assertContains($steamUser->id, $userIds);
        $this->assertNotContains($xboxUser->id, $userIds);
    }

    public function test_browse_requires_authentication()
    {
        $response = $this->get(route('social.browse'));

        $response->assertRedirect(route('login'));
    }

    public function test_friends_displays_user_connections()
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        // Create gamertags for context
        Gamertag::factory()->create(['user_id' => $friend1->id]);
        Gamertag::factory()->create(['user_id' => $friend2->id]);

        // Create accepted connections
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $friend1->id,
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => now()->subHour(),
        ]);

        UserConnection::factory()->create([
            'requester_id' => $friend2->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => now()->subMinutes(30),
        ]);

        // Create pending connection (should not appear)
        $pendingUser = User::factory()->create();
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $pendingUser->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('social.friends'));

        $response->assertStatus(200);
        $response->assertViewIs('social.friends');
        $response->assertViewHas(['friends', 'friendUsers']);

        $friends = $response->viewData('friends');
        $this->assertCount(2, $friends);
    }

    public function test_friends_requires_authentication()
    {
        $response = $this->get(route('social.friends'));

        $response->assertRedirect(route('login'));
    }

    public function test_requests_shows_received_and_sent_requests()
    {
        $user = User::factory()->create();
        $requester = User::factory()->create();
        $recipient = User::factory()->create();

        // Create gamertags for context
        Gamertag::factory()->create(['user_id' => $requester->id]);
        Gamertag::factory()->create(['user_id' => $recipient->id]);

        // Create received request
        UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $user->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        // Create sent request
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $recipient->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        // Create accepted connection (should not appear)
        $friend = User::factory()->create();
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $friend->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($user)->get(route('social.requests'));

        $response->assertStatus(200);
        $response->assertViewIs('social.requests');
        $response->assertViewHas(['receivedRequests', 'sentRequests']);

        $receivedRequests = $response->viewData('receivedRequests');
        $sentRequests = $response->viewData('sentRequests');

        $this->assertCount(1, $receivedRequests);
        $this->assertCount(1, $sentRequests);
        $this->assertEquals($requester->id, $receivedRequests->first()->requester_id);
        $this->assertEquals($recipient->id, $sentRequests->first()->recipient_id);
    }

    public function test_requests_requires_authentication()
    {
        $response = $this->get(route('social.requests'));

        $response->assertRedirect(route('login'));
    }

    public function test_search_includes_connection_status_in_results()
    {
        $currentUser = User::factory()->create();
        $connectedUser = User::factory()->create(['name' => 'ConnectedUser']);
        $pendingUser = User::factory()->create(['name' => 'PendingUser']);
        $unconnectedUser = User::factory()->create(['name' => 'UnconnectedUser']);

        // Create public gamertags
        Gamertag::factory()->create(['user_id' => $connectedUser->id, 'is_public' => true]);
        Gamertag::factory()->create(['user_id' => $pendingUser->id, 'is_public' => true]);
        Gamertag::factory()->create(['user_id' => $unconnectedUser->id, 'is_public' => true]);

        // Create accepted connection
        UserConnection::factory()->create([
            'requester_id' => $currentUser->id,
            'recipient_id' => $connectedUser->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // Create pending connection
        UserConnection::factory()->create([
            'requester_id' => $currentUser->id,
            'recipient_id' => $pendingUser->id,
            'status' => UserConnection::STATUS_PENDING,
        ]);

        $response = $this->actingAs($currentUser)->getJson(route('social.search', [
            'query' => 'User',
            'type' => 'users',
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('results', $data);
        $this->assertArrayHasKey('users', $data['results']);

        $users = collect($data['results']['users']);

        // Check connection statuses are included
        $connectedUserData = $users->firstWhere('id', $connectedUser->id);
        $pendingUserData = $users->firstWhere('id', $pendingUser->id);
        $unconnectedUserData = $users->firstWhere('id', $unconnectedUser->id);

        $this->assertNotNull($connectedUserData['connection_status']);
        $this->assertEquals(UserConnection::STATUS_ACCEPTED, $connectedUserData['connection_status']['status']);

        $this->assertNotNull($pendingUserData['connection_status']);
        $this->assertEquals(UserConnection::STATUS_PENDING, $pendingUserData['connection_status']['status']);

        $this->assertNull($unconnectedUserData['connection_status']);
    }

    public function test_search_handles_empty_query()
    {
        $currentUser = User::factory()->create();
        $user1 = User::factory()->create();

        Gamertag::factory()->create([
            'user_id' => $user1->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($currentUser)->get(route('social.search', [
            'query' => '',
            'type' => 'all',
        ]));

        $response->assertStatus(200);
        // Should still return results even with empty query
        $results = $response->viewData('results');
        $this->assertArrayHasKey('users', $results);
        $this->assertArrayHasKey('gamertags', $results);
    }

    public function test_search_limits_results()
    {
        $currentUser = User::factory()->create();

        // Create more users than the limit
        $users = User::factory()->count(20)->create();
        foreach ($users as $user) {
            Gamertag::factory()->create([
                'user_id' => $user->id,
                'is_public' => true,
            ]);
        }

        $response = $this->actingAs($currentUser)->getJson(route('social.search', [
            'type' => 'users',
        ]));

        $response->assertStatus(200);
        $data = $response->json();

        // Should be limited to 15 users
        $this->assertLessThanOrEqual(15, count($data['results']['users']));
    }
}
