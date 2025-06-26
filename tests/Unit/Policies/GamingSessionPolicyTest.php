<?php

namespace Tests\Unit\Policies;

use App\Models\GamingSession;
use App\Models\User;
use App\Models\UserConnection;
use App\Models\GamingSessionInvitation;
use App\Policies\GamingSessionPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamingSessionPolicyTest extends TestCase
{
    use RefreshDatabase;

    private GamingSessionPolicy $policy;
    private User $host;
    private User $participant;
    private User $invitedUser;
    private User $friendUser;
    private User $strangerUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new GamingSessionPolicy();
        $this->host = User::factory()->create();
        $this->participant = User::factory()->create();
        $this->invitedUser = User::factory()->create();
        $this->friendUser = User::factory()->create();
        $this->strangerUser = User::factory()->create();
    }

    public function test_view_any_allows_all_authenticated_users()
    {
        $user = User::factory()->create();
        
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_create_allows_all_authenticated_users()
    {
        $user = User::factory()->create();
        
        $this->assertTrue($this->policy->create($user));
    }

    public function test_view_allows_host_to_view_own_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        $this->assertTrue($this->policy->view($this->host, $session));
    }

    public function test_view_allows_anyone_to_view_public_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'public'
        ]);

        $this->assertTrue($this->policy->view($this->strangerUser, $session));
    }

    public function test_view_allows_participants_to_view_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        // Add user as participant
        $session->participantUsers()->attach($this->participant->id, [
            'joined_at' => now(),
            'status' => 'joined'
        ]);

        $this->assertTrue($this->policy->view($this->participant, $session));
    }

    public function test_view_allows_invited_users_to_view_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        // Create invitation
        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $this->invitedUser->id,
            'status' => 'pending'
        ]);

        $this->assertTrue($this->policy->view($this->invitedUser, $session));
    }

    public function test_view_allows_friends_to_view_friends_only_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'friends_only'
        ]);

        // Mock friendship relationship
        UserConnection::factory()->create([
            'requester_id' => $this->host->id,
            'recipient_id' => $this->friendUser->id,
            'status' => 'accepted'
        ]);

        $this->assertTrue($this->policy->view($this->friendUser, $session));
    }

    public function test_view_denies_strangers_for_private_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        $this->assertFalse($this->policy->view($this->strangerUser, $session));
    }

    public function test_view_denies_non_friends_for_friends_only_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'friends_only'
        ]);

        $this->assertFalse($this->policy->view($this->strangerUser, $session));
    }

    public function test_update_allows_only_host()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id
        ]);

        $this->assertTrue($this->policy->update($this->host, $session));
        $this->assertFalse($this->policy->update($this->participant, $session));
        $this->assertFalse($this->policy->update($this->strangerUser, $session));
    }

    public function test_delete_allows_only_host()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id
        ]);

        $this->assertTrue($this->policy->delete($this->host, $session));
        $this->assertFalse($this->policy->delete($this->participant, $session));
        $this->assertFalse($this->policy->delete($this->strangerUser, $session));
    }

    public function test_restore_allows_only_host()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id
        ]);

        $this->assertTrue($this->policy->restore($this->host, $session));
        $this->assertFalse($this->policy->restore($this->participant, $session));
        $this->assertFalse($this->policy->restore($this->strangerUser, $session));
    }

    public function test_force_delete_allows_only_host()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id
        ]);

        $this->assertTrue($this->policy->forceDelete($this->host, $session));
        $this->assertFalse($this->policy->forceDelete($this->participant, $session));
        $this->assertFalse($this->policy->forceDelete($this->strangerUser, $session));
    }

    public function test_view_messages_same_as_view_permissions()
    {
        $publicSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'public'
        ]);

        $privateSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        // Public session - everyone can view messages
        $this->assertTrue($this->policy->viewMessages($this->strangerUser, $publicSession));
        
        // Private session - only host can view messages
        $this->assertTrue($this->policy->viewMessages($this->host, $privateSession));
        $this->assertFalse($this->policy->viewMessages($this->strangerUser, $privateSession));
    }

    public function test_post_message_allows_host()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        $this->assertTrue($this->policy->postMessage($this->host, $session));
    }

    public function test_post_message_allows_participants()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        $session->participantUsers()->attach($this->participant->id, [
            'joined_at' => now(),
            'status' => 'joined'
        ]);

        $this->assertTrue($this->policy->postMessage($this->participant, $session));
    }

    public function test_post_message_allows_anyone_for_public_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'public'
        ]);

        $this->assertTrue($this->policy->postMessage($this->strangerUser, $session));
    }

    public function test_post_message_denies_strangers_for_private_session()
    {
        $session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'invite_only'
        ]);

        $this->assertFalse($this->policy->postMessage($this->strangerUser, $session));
    }
}
