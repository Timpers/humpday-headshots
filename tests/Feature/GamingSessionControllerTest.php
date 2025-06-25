<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GamingSession;
use App\Models\GamingSessionParticipant;
use App\Models\GamingSessionInvitation;
use App\Models\UserConnection;
use App\Notifications\GamingSessionInvitation as GamingSessionInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamingSessionControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
        Notification::fake();
    }

    public function test_index_displays_gaming_sessions()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Session where user is host
        $hostSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'title' => 'Host Session',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        // Session where user is participant
        $participantSession = GamingSession::factory()->create([
            'host_user_id' => $otherUser->id,
            'title' => 'Participant Session',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $participantSession->id,
            'user_id' => $user->id,
        ]);

        // Session user is not involved in (should not appear by default)
        GamingSession::factory()->create([
            'host_user_id' => $otherUser->id,
            'title' => 'Other Session',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index'));

        $response->assertStatus(200);
        $response->assertViewIs('gaming-sessions.index');
        $response->assertViewHas(['sessions', 'type', 'status', 'search']);
        $response->assertSee('Host Session');
        $response->assertSee('Participant Session');
        $response->assertDontSee('Other Session');
    }

    public function test_index_filters_by_type_hosting()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $hostSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $participantSession = GamingSession::factory()->create([
            'host_user_id' => $otherUser->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $participantSession->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'type' => 'hosting'
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertTrue($sessions->contains('id', $hostSession->id));
        $this->assertFalse($sessions->contains('id', $participantSession->id));
    }

    public function test_index_filters_by_type_participating()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $hostSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $participantSession = GamingSession::factory()->create([
            'host_user_id' => $otherUser->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $participantSession->id,
            'user_id' => $user->id,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'type' => 'participating'
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertFalse($sessions->contains('id', $hostSession->id));
        $this->assertTrue($sessions->contains('id', $participantSession->id));
    }

    public function test_index_filters_by_type_invited()
    {
        $user = User::factory()->create();
        $host = User::factory()->create();

        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'type' => 'invited'
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertTrue($sessions->contains('id', $session->id));
    }

    public function test_index_filters_by_type_public()
    {
        $user = User::factory()->create();
        $host = User::factory()->create();

        $publicSession = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'privacy' => 'public',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $privateSession = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'privacy' => 'invite_only',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'type' => 'public'
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertTrue($sessions->contains('id', $publicSession->id));
        $this->assertFalse($sessions->contains('id', $privateSession->id));
    }

    public function test_index_filters_by_status()
    {
        $user = User::factory()->create();

        $scheduledSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $cancelledSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'status' => GamingSession::STATUS_CANCELLED,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'status' => GamingSession::STATUS_CANCELLED
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertFalse($sessions->contains('id', $scheduledSession->id));
        $this->assertTrue($sessions->contains('id', $cancelledSession->id));
    }

    public function test_index_searches_by_title_and_game()
    {
        $user = User::factory()->create();

        $searchableSession1 = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'title' => 'Searchable Title',
            'game_name' => 'Regular Game',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $searchableSession2 = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'title' => 'Regular Title',
            'game_name' => 'Searchable Game Name',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $otherSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'title' => 'Other Title',
            'game_name' => 'Other Game',
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index', [
            'search' => 'Searchable'
        ]));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');
        $this->assertTrue($sessions->contains('id', $searchableSession1->id));
        $this->assertTrue($sessions->contains('id', $searchableSession2->id));
        $this->assertFalse($sessions->contains('id', $otherSession->id));
    }

    public function test_create_displays_form_with_friends_and_groups()
    {
        $user = User::factory()->create();
        $friend = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        // Create friendship
        UserConnection::factory()->create([
            'requester_id' => $user->id,
            'recipient_id' => $friend->id,
            'status' => UserConnection::STATUS_ACCEPTED,
        ]);

        // Create group membership
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.create'));

        $response->assertStatus(200);
        $response->assertViewIs('gaming-sessions.create');
        $response->assertViewHas(['friends', 'groups']);
    }

    public function test_create_requires_authentication()
    {
        $response = $this->get(route('gaming-sessions.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_gaming_session_with_host_participant()
    {
        $user = User::factory()->create();

        $sessionData = [
            'title' => 'Epic Gaming Session',
            'description' => 'Join us for an epic session',
            'game_name' => 'Valorant',
            'platform' => 'PC',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 5,
            'privacy' => 'public',
            'requirements' => 'Be friendly!',
        ];

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), $sessionData);

        $response->assertRedirect(route('gaming-sessions.index'));
        $response->assertSessionHas('success', 'Gaming session created successfully!');

        $this->assertDatabaseHas('gaming_sessions', [
            'host_user_id' => $user->id,
            'title' => 'Epic Gaming Session',
            'description' => 'Join us for an epic session',
            'game_name' => 'Valorant',
            'platform' => 'PC',
            'max_participants' => 5,
            'privacy' => 'public',
            'requirements' => 'Be friendly!',
        ]);

        $session = GamingSession::where('title', 'Epic Gaming Session')->first();

        // Host should automatically be a participant
        $this->assertDatabaseHas('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_store_sends_friend_invitations()
    {
        $user = User::factory()->create();
        $friend1 = User::factory()->create();
        $friend2 = User::factory()->create();

        $sessionData = [
            'title' => 'Friend Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 5,
            'privacy' => 'public',
            'invite_friends' => [$friend1->id, $friend2->id],
        ];

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), $sessionData);

        $response->assertRedirect(route('gaming-sessions.index'));

        $session = GamingSession::where('title', 'Friend Session')->first();

        // Check invitations were created
        $this->assertDatabaseHas('gaming_session_invitations', [
            'gaming_session_id' => $session->id,
            'invited_user_id' => $friend1->id,
            'invited_by_user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('gaming_session_invitations', [
            'gaming_session_id' => $session->id,
            'invited_user_id' => $friend2->id,
            'invited_by_user_id' => $user->id,
        ]);

        // Check notifications were sent
        Notification::assertSentTo($friend1, GamingSessionInvitationNotification::class);
        Notification::assertSentTo($friend2, GamingSessionInvitationNotification::class);
    }

    public function test_store_sends_group_invitations()
    {
        $user = User::factory()->create();
        $member1 = User::factory()->create();
        $member2 = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        // Create group memberships
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member1->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member2->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $sessionData = [
            'title' => 'Group Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 10,
            'privacy' => 'public',
            'invite_groups' => [$group->id],
        ];

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), $sessionData);

        $response->assertRedirect(route('gaming-sessions.index'));

        $session = GamingSession::where('title', 'Group Session')->first();

        // Check group invitation was created
        $this->assertDatabaseHas('gaming_session_invitations', [
            'gaming_session_id' => $session->id,
            'invited_group_id' => $group->id,
            'invited_by_user_id' => $user->id,
        ]);

        // Check notifications were sent to members (but not the host)
        Notification::assertSentTo($member1, GamingSessionInvitationNotification::class);
        Notification::assertSentTo($member2, GamingSessionInvitationNotification::class);
        Notification::assertNotSentTo($user, GamingSessionInvitationNotification::class);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), []);

        $response->assertSessionHasErrors([
            'title',
            'game_name',
            'scheduled_at',
            'max_participants',
            'privacy'
        ]);
    }

    public function test_store_validates_scheduled_at_is_future()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), [
            'title' => 'Test Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->subHour()->format('Y-m-d H:i:s'), // Past date
            'max_participants' => 5,
            'privacy' => 'public',
        ]);

        $response->assertSessionHasErrors(['scheduled_at']);
    }

    public function test_store_validates_max_participants_range()
    {
        $user = User::factory()->create();

        // Test minimum
        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), [
            'title' => 'Test Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 1, // Too low
            'privacy' => 'public',
        ]);

        $response->assertSessionHasErrors(['max_participants']);

        // Test maximum
        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), [
            'title' => 'Test Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 51, // Too high
            'privacy' => 'public',
        ]);

        $response->assertSessionHasErrors(['max_participants']);
    }

    public function test_store_validates_privacy_values()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), [
            'title' => 'Test Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 5,
            'privacy' => 'invalid_privacy',
        ]);

        $response->assertSessionHasErrors(['privacy']);
    }

    public function test_store_validates_string_lengths()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('gaming-sessions.store'), [
            'title' => str_repeat('a', 256), // Too long
            'description' => str_repeat('b', 1001), // Too long
            'game_name' => str_repeat('c', 256), // Too long
            'requirements' => str_repeat('d', 1001), // Too long
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'max_participants' => 5,
            'privacy' => 'public',
        ]);

        $response->assertSessionHasErrors(['title', 'description', 'game_name', 'requirements']);
    }

    public function test_show_displays_session_details()
    {
        $host = User::factory()->create();
        $participant = User::factory()->create();
        $viewer = User::factory()->create();

        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'title' => 'Test Session',
        ]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $participant->id,
        ]);

        $response = $this->actingAs($viewer)->get(route('gaming-sessions.show', $session));

        $response->assertStatus(200);
        $response->assertViewIs('gaming-sessions.show');
        $response->assertViewHas([
            'gamingSession',
            'userInvitation',
            'isParticipant',
            'canJoin'
        ]);
    }

    public function test_edit_displays_form_for_host()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $response = $this->actingAs($host)->get(route('gaming-sessions.edit', $session));

        $response->assertStatus(200);
        $response->assertViewIs('gaming-sessions.edit');
        $response->assertViewHas(['gamingSession', 'friends', 'groups']);
    }

    public function test_edit_prevents_non_host_access()
    {
        $host = User::factory()->create();
        $nonHost = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $response = $this->actingAs($nonHost)->get(route('gaming-sessions.edit', $session));

        $response->assertStatus(403);
    }

    public function test_update_modifies_session()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'title' => 'Old Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'game_name' => 'Updated Game',
            'platform' => 'Updated Platform',
            'scheduled_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'max_participants' => 10,
            'privacy' => 'invite_only',
            'requirements' => 'Updated requirements',
        ];

        $response = $this->actingAs($host)
            ->put(route('gaming-sessions.update', $session), $updateData);

        $response->assertRedirect(route('gaming-sessions.show', $session));
        $response->assertSessionHas('success', 'Gaming session updated successfully!');

        $this->assertDatabaseHas('gaming_sessions', [
            'id' => $session->id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'game_name' => 'Updated Game',
            'platform' => 'Updated Platform',
            'max_participants' => 10,
            'privacy' => 'invite_only',
            'requirements' => 'Updated requirements',
        ]);
    }

    public function test_update_prevents_non_host_modification()
    {
        $host = User::factory()->create();
        $nonHost = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $response = $this->actingAs($nonHost)
            ->put(route('gaming-sessions.update', $session), [
                'title' => 'Hacked Title',
                'game_name' => 'Test Game',
                'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
                'max_participants' => 5,
                'privacy' => 'public',
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_cancels_session()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($host)
            ->delete(route('gaming-sessions.destroy', $session));

        $response->assertRedirect(route('gaming-sessions.index'));
        $response->assertSessionHas('success', 'Gaming session cancelled successfully!');

        $this->assertDatabaseHas('gaming_sessions', [
            'id' => $session->id,
            'status' => GamingSession::STATUS_CANCELLED,
        ]);
    }

    public function test_destroy_prevents_non_host_cancellation()
    {
        $host = User::factory()->create();
        $nonHost = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $response = $this->actingAs($nonHost)
            ->delete(route('gaming-sessions.destroy', $session));

        $response->assertStatus(403);
    }

    public function test_join_adds_user_to_session()
    {
        $host = User::factory()->create();
        $joiner = User::factory()->create();

        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'privacy' => 'public',
            'max_participants' => 5,
        ]);

        $response = $this->actingAs($joiner)
            ->post(route('gaming-sessions.join', $session));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'You have joined the gaming session!');

        $this->assertDatabaseHas('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $joiner->id,
        ]);
    }

    public function test_join_prevents_joining_when_not_allowed()
    {
        $host = User::factory()->create();
        $joiner = User::factory()->create();

        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'privacy' => 'invite_only', // Not public
        ]);

        $response = $this->actingAs($joiner)
            ->post(route('gaming-sessions.join', $session));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot join this gaming session.');
    }

    public function test_leave_removes_user_from_session()
    {
        $host = User::factory()->create();
        $participant = User::factory()->create();

        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $participation = GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $participant->id,
        ]);

        $response = $this->actingAs($participant)
            ->post(route('gaming-sessions.leave', $session));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'You have left the gaming session.');

        $this->assertDatabaseHas('gaming_session_participants', [
            'id' => $participation->id,
            'left_at' => now(),
        ]);
    }

    public function test_leave_prevents_host_from_leaving()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $host->id,
        ]);

        $response = $this->actingAs($host)
            ->post(route('gaming-sessions.leave', $session));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You cannot leave your own gaming session. You can cancel it instead.');
    }

    public function test_leave_prevents_non_participant_from_leaving()
    {
        $host = User::factory()->create();
        $nonParticipant = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $response = $this->actingAs($nonParticipant)
            ->post(route('gaming-sessions.leave', $session));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You are not a participant in this session.');
    }

    public function test_search_games_returns_json_results()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('gaming-sessions.search-games', ['query' => 'test']));

        $response->assertStatus(200);
        $response->assertJsonStructure([]);
    }

    public function test_search_games_validates_query_length()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson(route('gaming-sessions.search-games', ['query' => 'a'])); // Too short

        $response->assertStatus(200);
        $response->assertJson([]);
    }

    public function test_respond_to_invitation_accepts_invitation()
    {
        $host = User::factory()->create();
        $invitee = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_by_user_id' => $host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($invitee)
            ->post(route('gaming-sessions.respond-invitation', $invitation), [
                'action' => 'accept'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation accepted! You have joined the gaming session.');

        $this->assertDatabaseHas('gaming_session_invitations', [
            'id' => $invitation->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);
    }

    public function test_respond_to_invitation_declines_invitation()
    {
        $host = User::factory()->create();
        $invitee = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_by_user_id' => $host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($invitee)
            ->post(route('gaming-sessions.respond-invitation', $invitation), [
                'action' => 'decline'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation declined.');

        $this->assertDatabaseHas('gaming_session_invitations', [
            'id' => $invitation->id,
            'status' => GamingSessionInvitation::STATUS_DECLINED,
        ]);
    }

    public function test_respond_to_invitation_prevents_unauthorized_user()
    {
        $host = User::factory()->create();
        $invitee = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_by_user_id' => $host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('gaming-sessions.respond-invitation', $invitation), [
                'action' => 'accept'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This invitation is not for you.');
    }

    public function test_respond_to_invitation_prevents_responding_to_non_pending()
    {
        $host = User::factory()->create();
        $invitee = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_by_user_id' => $host->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $response = $this->actingAs($invitee)
            ->post(route('gaming-sessions.respond-invitation', $invitation), [
                'action' => 'accept'
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This invitation has already been responded to.');
    }

    public function test_all_routes_require_authentication_except_show()
    {
        $session = GamingSession::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create();

        $authRequiredRoutes = [
            ['get', 'gaming-sessions.index'],
            ['get', 'gaming-sessions.create'],
            ['post', 'gaming-sessions.store'],
            ['get', 'gaming-sessions.edit', $session],
            ['put', 'gaming-sessions.update', $session],
            ['delete', 'gaming-sessions.destroy', $session],
            ['post', 'gaming-sessions.join', $session],
            ['post', 'gaming-sessions.leave', $session],
            ['get', 'gaming-sessions.search-games'],
            ['post', 'gaming-sessions.respond-invitation', $invitation],
        ];

        foreach ($authRequiredRoutes as $routeData) {
            $method = $routeData[0];
            $routeName = $routeData[1];
            $parameter = $routeData[2] ?? null;

            $route = $parameter ? route($routeName, $parameter) : route($routeName);
            $response = $this->$method($route);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_session_ordering_is_by_scheduled_date()
    {
        $user = User::factory()->create();

        $laterSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'scheduled_at' => now()->addDays(2),
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $earlierSession = GamingSession::factory()->create([
            'host_user_id' => $user->id,
            'scheduled_at' => now()->addDay(),
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $response = $this->actingAs($user)->get(route('gaming-sessions.index'));

        $response->assertStatus(200);
        $sessions = $response->viewData('sessions');

        // Should be ordered by scheduled_at asc (earlier first)
        $this->assertEquals($earlierSession->id, $sessions->first()->id);
        $this->assertEquals($laterSession->id, $sessions->last()->id);
    }
}
