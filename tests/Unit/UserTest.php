<?php

namespace Tests\Unit;

use App\Models\Game;
use App\Models\Gamertag;
use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\GamingSessionParticipant;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'email',
            'password',
            'push_subscription',
        ];

        $user = new User();
        $this->assertEquals($fillable, $user->getFillable());
    }

    public function test_user_has_hidden_attributes()
    {
        $hidden = [
            'password',
            'remember_token',
        ];

        $user = new User();
        $this->assertEquals($hidden, $user->getHidden());
    }

    public function test_user_casts_attributes()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->email_verified_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $user->updated_at);
    }

    // GAMERTAG RELATIONSHIPS

    public function test_user_has_many_gamertags()
    {
        $user = User::factory()->create();
        $gamertag = Gamertag::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->gamertags->contains($gamertag));
        $this->assertEquals(1, $user->gamertags->count());
    }

    public function test_user_has_many_games()
    {
        $user = User::factory()->create();
        $game = Game::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->games->contains($game));
        $this->assertEquals(1, $user->games->count());
    }

    public function test_user_public_gamertags_relationship()
    {
        $user = User::factory()->create();
        $publicGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'steam',
            'is_public' => true,
        ]);
        $privateGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'platform' => 'xbox_live',
            'is_public' => false,
        ]);

        $publicGamertags = $user->publicGamertags;

        $this->assertEquals(1, $publicGamertags->count());
        $this->assertTrue($publicGamertags->contains($publicGamertag));
        $this->assertFalse($publicGamertags->contains($privateGamertag));
    }

    public function test_user_primary_gamertags_relationship()
    {
        $user = User::factory()->create();
        $primaryGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'is_primary' => true,
        ]);
        $secondaryGamertag = Gamertag::factory()->create([
            'user_id' => $user->id,
            'is_primary' => false,
        ]);

        $primaryGamertags = $user->primaryGamertags;

        $this->assertEquals(1, $primaryGamertags->count());
        $this->assertTrue($primaryGamertags->contains($primaryGamertag));
        $this->assertFalse($primaryGamertags->contains($secondaryGamertag));
    }

    // USER CONNECTION RELATIONSHIPS

    public function test_user_has_many_sent_connection_requests()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertTrue($requester->sentConnectionRequests->contains($connection));
        $this->assertEquals(1, $requester->sentConnectionRequests->count());
    }

    public function test_user_has_many_received_connection_requests()
    {
        $requester = User::factory()->create();
        $recipient = User::factory()->create();
        $connection = UserConnection::factory()->create([
            'requester_id' => $requester->id,
            'recipient_id' => $recipient->id,
        ]);

        $this->assertTrue($recipient->receivedConnectionRequests->contains($connection));
        $this->assertEquals(1, $recipient->receivedConnectionRequests->count());
    }

    public function test_user_connections_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $connection1 = UserConnection::factory()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        $connection2 = UserConnection::factory()->create([
            'requester_id' => $user3->id,
            'recipient_id' => $user1->id,
        ]);

        $userConnections = $user1->connections()->get();

        $this->assertEquals(2, $userConnections->count());
        $this->assertTrue($userConnections->contains($connection1));
        $this->assertTrue($userConnections->contains($connection2));
    }

    public function test_user_friends_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $acceptedConnection = UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        $pendingConnection = UserConnection::factory()->pending()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user3->id,
        ]);

        $friends = $user1->friends()->get();

        $this->assertEquals(1, $friends->count());
        $this->assertTrue($friends->contains($acceptedConnection));
        $this->assertFalse($friends->contains($pendingConnection));
    }

    public function test_user_friend_users_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user3->id,
            'recipient_id' => $user1->id,
        ]);

        $friendUsers = $user1->friendUsers();

        $this->assertEquals(2, $friendUsers->count());
        $this->assertTrue($friendUsers->contains($user2));
        $this->assertTrue($friendUsers->contains($user3));
        $this->assertFalse($friendUsers->contains($user1));
    }

    public function test_user_pending_received_requests_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $pendingConnection = UserConnection::factory()->pending()->create([
            'requester_id' => $user2->id,
            'recipient_id' => $user1->id,
        ]);

        $acceptedConnection = UserConnection::factory()->accepted()->create([
            'requester_id' => $user3->id,
            'recipient_id' => $user1->id,
        ]);

        $pendingReceived = $user1->pendingReceivedRequests()->get();

        $this->assertEquals(1, $pendingReceived->count());
        $this->assertTrue($pendingReceived->contains($pendingConnection));
        $this->assertFalse($pendingReceived->contains($acceptedConnection));
    }

    public function test_user_pending_sent_requests_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $pendingConnection = UserConnection::factory()->pending()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        $acceptedConnection = UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user3->id,
        ]);

        $pendingSent = $user1->pendingSentRequests()->get();

        $this->assertEquals(1, $pendingSent->count());
        $this->assertTrue($pendingSent->contains($pendingConnection));
        $this->assertFalse($pendingSent->contains($acceptedConnection));
    }

    public function test_user_is_connected_to_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        UserConnection::factory()->pending()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user3->id,
        ]);

        $this->assertTrue($user1->isConnectedTo($user2->id));
        $this->assertFalse($user1->isConnectedTo($user3->id));
    }

    public function test_user_has_pending_request_with_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        UserConnection::factory()->pending()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user3->id,
        ]);

        $this->assertTrue($user1->hasPendingRequestWith($user2->id));
        $this->assertFalse($user1->hasPendingRequestWith($user3->id));
    }

    public function test_user_get_connection_status_with_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        $connection = UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        $status = $user1->getConnectionStatusWith($user2->id);
        $noStatus = $user1->getConnectionStatusWith($user3->id);

        $this->assertNotNull($status);
        $this->assertEquals(UserConnection::STATUS_ACCEPTED, $status['status']);
        $this->assertTrue($status['is_requester']);
        $this->assertEquals($connection->id, $status['connection']->id);

        $this->assertNull($noStatus);
    }

    public function test_user_get_friend_ids_method()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $user3 = User::factory()->create();

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user1->id,
            'recipient_id' => $user2->id,
        ]);

        UserConnection::factory()->accepted()->create([
            'requester_id' => $user3->id,
            'recipient_id' => $user1->id,
        ]);

        $friendIds = $user1->getFriendIds();

        $this->assertIsArray($friendIds);
        $this->assertCount(2, $friendIds);
        $this->assertContains($user2->id, $friendIds);
        $this->assertContains($user3->id, $friendIds);
    }

    // GROUP RELATIONSHIPS

    public function test_user_has_many_owned_groups()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $user->id]);

        $this->assertTrue($user->ownedGroups->contains($group));
        $this->assertEquals(1, $user->ownedGroups->count());
    }

    public function test_user_has_many_group_memberships()
    {
        $user = User::factory()->create();
        $group = Group::factory()->create();
        $membership = GroupMembership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group->id,
        ]);

        $this->assertTrue($user->groupMemberships->contains($membership));
        $this->assertEquals(1, $user->groupMemberships->count());
    }

    public function test_user_has_many_sent_group_invitations()
    {
        $user = User::factory()->create();
        $invitation = GroupInvitation::factory()->create(['invited_by_user_id' => $user->id]);

        $this->assertTrue($user->sentGroupInvitations->contains($invitation));
        $this->assertEquals(1, $user->sentGroupInvitations->count());
    }

    public function test_user_has_many_received_group_invitations()
    {
        $user = User::factory()->create();
        $invitation = GroupInvitation::factory()->create(['invited_user_id' => $user->id]);

        $this->assertTrue($user->receivedGroupInvitations->contains($invitation));
        $this->assertEquals(1, $user->receivedGroupInvitations->count());
    }

    public function test_user_pending_group_invitations_relationship()
    {
        $user = User::factory()->create();
        $pendingInvitation = GroupInvitation::factory()->pending()->create(['invited_user_id' => $user->id]);
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create(['invited_user_id' => $user->id]);

        $pendingInvitations = $user->pendingGroupInvitations;

        $this->assertEquals(1, $pendingInvitations->count());
        $this->assertTrue($pendingInvitations->contains($pendingInvitation));
        $this->assertFalse($pendingInvitations->contains($acceptedInvitation));
    }

    public function test_user_is_member_of_method()
    {
        $user = User::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        GroupMembership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group1->id,
        ]);

        $this->assertTrue($user->isMemberOf($group1));
        $this->assertFalse($user->isMemberOf($group2));
    }

    public function test_user_is_admin_of_method()
    {
        $user = User::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        GroupMembership::factory()->admin()->create([
            'user_id' => $user->id,
            'group_id' => $group1->id,
        ]);

        GroupMembership::factory()->member()->create([
            'user_id' => $user->id,
            'group_id' => $group2->id,
        ]);

        $this->assertTrue($user->isAdminOf($group1));
        $this->assertFalse($user->isAdminOf($group2));
    }

    public function test_user_owns_group_method()
    {
        $user = User::factory()->create();
        $ownedGroup = Group::factory()->create(['owner_id' => $user->id]);
        $otherGroup = Group::factory()->create();

        $this->assertTrue($user->ownsGroup($ownedGroup));
        $this->assertFalse($user->ownsGroup($otherGroup));
    }

    public function test_user_get_role_in_group_method()
    {
        $user = User::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        GroupMembership::factory()->admin()->create([
            'user_id' => $user->id,
            'group_id' => $group1->id,
        ]);

        $this->assertEquals(GroupMembership::ROLE_ADMIN, $user->getRoleInGroup($group1));
        $this->assertNull($user->getRoleInGroup($group2));
    }

    // GAMING SESSION RELATIONSHIPS

    public function test_user_has_many_hosted_gaming_sessions()
    {
        $user = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $user->id]);

        $this->assertTrue($user->hostedGamingSessions->contains($session));
        $this->assertEquals(1, $user->hostedGamingSessions->count());
    }

    public function test_user_has_many_gaming_session_participations()
    {
        $user = User::factory()->create();
        $participation = GamingSessionParticipant::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->gamingSessionParticipations->contains($participation));
        $this->assertEquals(1, $user->gamingSessionParticipations->count());
    }

    public function test_user_has_many_sent_gaming_session_invitations()
    {
        $user = User::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create(['invited_by_user_id' => $user->id]);

        $this->assertTrue($user->sentGamingSessionInvitations->contains($invitation));
        $this->assertEquals(1, $user->sentGamingSessionInvitations->count());
    }

    public function test_user_has_many_received_gaming_session_invitations()
    {
        $user = User::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create(['invited_user_id' => $user->id]);

        $this->assertTrue($user->receivedGamingSessionInvitations->contains($invitation));
        $this->assertEquals(1, $user->receivedGamingSessionInvitations->count());
    }

    public function test_user_pending_gaming_session_invitations_relationship()
    {
        $user = User::factory()->create();
        $pendingInvitation = GamingSessionInvitation::factory()->pending()->create(['invited_user_id' => $user->id]);
        $acceptedInvitation = GamingSessionInvitation::factory()->accepted()->create(['invited_user_id' => $user->id]);

        $pendingInvitations = $user->pendingGamingSessionInvitations;

        $this->assertEquals(1, $pendingInvitations->count());
        $this->assertTrue($pendingInvitations->contains($pendingInvitation));
        $this->assertFalse($pendingInvitations->contains($acceptedInvitation));
    }

    public function test_user_factory_creates_valid_user()
    {
        $user = User::factory()->create();

        $this->assertInstanceOf(User::class, $user);
        $this->assertNotNull($user->name);
        $this->assertNotNull($user->email);
        $this->assertTrue(filter_var($user->email, FILTER_VALIDATE_EMAIL) !== false);
        $this->assertNotNull($user->password);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_user_can_be_created_with_complete_data()
    {
        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => bcrypt('password'),
        ];

        $user = User::create($userData);

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('John Doe', $user->name);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertNotNull($user->password);
    }

    public function test_user_password_is_hidden_in_array()
    {
        $user = User::factory()->create();
        $userArray = $user->toArray();

        $this->assertArrayNotHasKey('password', $userArray);
        $this->assertArrayNotHasKey('remember_token', $userArray);
        $this->assertArrayHasKey('name', $userArray);
        $this->assertArrayHasKey('email', $userArray);
    }
}
