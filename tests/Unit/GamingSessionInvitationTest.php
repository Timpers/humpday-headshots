<?php

namespace Tests\Unit;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamingSessionInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_gaming_session_invitation()
    {
        $session = GamingSession::factory()->create();
        $inviter = User::factory()->create();
        $invitee = User::factory()->create();

        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_by_user_id' => $inviter->id,
            'invited_user_id' => $invitee->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('gaming_session_invitations', [
            'id' => $invitation->id,
            'gaming_session_id' => $session->id,
            'invited_by_user_id' => $inviter->id,
            'invited_user_id' => $invitee->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);
    }

    public function test_invitation_belongs_to_gaming_session()
    {
        $session = GamingSession::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
        ]);

        $this->assertEquals($session->id, $invitation->gamingSession->id);
        $this->assertEquals($session->title, $invitation->gamingSession->title);
    }

    public function test_invitation_belongs_to_inviter()
    {
        $inviter = User::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create([
            'invited_by_user_id' => $inviter->id,
        ]);

        $this->assertEquals($inviter->id, $invitation->invitedBy->id);
        $this->assertEquals($inviter->name, $invitation->invitedBy->name);
    }

    public function test_invitation_belongs_to_invitee()
    {
        $invitee = User::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => $invitee->id,
        ]);

        $this->assertEquals($invitee->id, $invitation->invitedUser->id);
        $this->assertEquals($invitee->name, $invitation->invitedUser->name);
    }

    public function test_can_accept_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->pending()->create();

        $result = $invitation->accept();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertDatabaseHas('gaming_session_invitations', [
            'id' => $invitation->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);
    }

    public function test_can_decline_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->pending()->create();

        $result = $invitation->decline();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->status);
        $this->assertDatabaseHas('gaming_session_invitations', [
            'id' => $invitation->id,
            'status' => GamingSessionInvitation::STATUS_DECLINED,
        ]);
    }

    public function test_cannot_accept_already_accepted_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->accepted()->create();

        $result = $invitation->accept();

        $this->assertFalse($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
    }

    public function test_cannot_decline_already_declined_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->declined()->create();

        $result = $invitation->decline();

        $this->assertFalse($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->status);
    }

    public function test_cannot_accept_declined_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->declined()->create();

        $result = $invitation->accept();

        $this->assertFalse($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->status);
    }

    public function test_cannot_decline_accepted_invitation()
    {
        $invitation = GamingSessionInvitation::factory()->accepted()->create();

        $result = $invitation->decline();

        $this->assertFalse($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
    }

    public function test_is_pending_method()
    {
        $pendingInvitation = GamingSessionInvitation::factory()->pending()->create();
        $acceptedInvitation = GamingSessionInvitation::factory()->accepted()->create();
        $declinedInvitation = GamingSessionInvitation::factory()->declined()->create();

        $this->assertTrue($pendingInvitation->isPending());
        $this->assertFalse($acceptedInvitation->isPending());
        $this->assertFalse($declinedInvitation->isPending());
    }

    public function test_is_accepted_method()
    {
        $pendingInvitation = GamingSessionInvitation::factory()->pending()->create();
        $acceptedInvitation = GamingSessionInvitation::factory()->accepted()->create();
        $declinedInvitation = GamingSessionInvitation::factory()->declined()->create();

        $this->assertFalse($pendingInvitation->isAccepted());
        $this->assertTrue($acceptedInvitation->isAccepted());
        $this->assertFalse($declinedInvitation->isAccepted());
    }

    public function test_is_declined_method()
    {
        $pendingInvitation = GamingSessionInvitation::factory()->pending()->create();
        $acceptedInvitation = GamingSessionInvitation::factory()->accepted()->create();
        $declinedInvitation = GamingSessionInvitation::factory()->declined()->create();

        $this->assertFalse($pendingInvitation->isDeclined());
        $this->assertFalse($acceptedInvitation->isDeclined());
        $this->assertTrue($declinedInvitation->isDeclined());
    }

    public function test_pending_scope()
    {
        GamingSessionInvitation::factory()->pending()->count(2)->create();
        GamingSessionInvitation::factory()->accepted()->count(1)->create();
        GamingSessionInvitation::factory()->declined()->count(1)->create();

        $pendingInvitations = GamingSessionInvitation::pending()->get();

        $this->assertEquals(2, $pendingInvitations->count());
        $this->assertTrue($pendingInvitations->every(fn($invitation) => $invitation->isPending()));
    }

    public function test_accepted_scope()
    {
        GamingSessionInvitation::factory()->pending()->count(2)->create();
        GamingSessionInvitation::factory()->accepted()->count(3)->create();
        GamingSessionInvitation::factory()->declined()->count(1)->create();

        $acceptedInvitations = GamingSessionInvitation::accepted()->get();

        $this->assertEquals(3, $acceptedInvitations->count());
        $this->assertTrue($acceptedInvitations->every(fn($invitation) => $invitation->isAccepted()));
    }

    public function test_declined_scope()
    {
        GamingSessionInvitation::factory()->pending()->count(2)->create();
        GamingSessionInvitation::factory()->accepted()->count(1)->create();
        GamingSessionInvitation::factory()->declined()->count(2)->create();

        $declinedInvitations = GamingSessionInvitation::declined()->get();

        $this->assertEquals(2, $declinedInvitations->count());
        $this->assertTrue($declinedInvitations->every(fn($invitation) => $invitation->isDeclined()));
    }

    public function test_for_user_scope()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create invitations for the user
        GamingSessionInvitation::factory()->count(2)->create([
            'invited_user_id' => $user->id,
        ]);

        // Create invitations for other user
        GamingSessionInvitation::factory()->count(1)->create([
            'invited_user_id' => $otherUser->id,
        ]);

        $userInvitations = GamingSessionInvitation::forUser($user)->get();

        $this->assertEquals(2, $userInvitations->count());
        $this->assertTrue($userInvitations->every(fn($invitation) => $invitation->invited_user_id === $user->id));
    }

    public function test_can_create_multiple_invitations_to_different_users()
    {
        $session = GamingSession::factory()->create();
        $inviter = User::factory()->create();
        $invitee1 = User::factory()->create();
        $invitee2 = User::factory()->create();

        // Create invitations to different users - should be allowed
        $invitation1 = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_by_user_id' => $inviter->id,
            'invited_user_id' => $invitee1->id,
        ]);

        $invitation2 = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_by_user_id' => $inviter->id,
            'invited_user_id' => $invitee2->id,
        ]);

        $this->assertNotEquals($invitation1->id, $invitation2->id);
        $this->assertEquals($invitee1->id, $invitation1->invited_user_id);
        $this->assertEquals($invitee2->id, $invitation2->invited_user_id);
    }

    public function test_invitation_fillable_attributes()
    {
        $data = [
            'gaming_session_id' => 1,
            'invited_by_user_id' => 1,
            'invited_user_id' => 2,
            'status' => GamingSessionInvitation::STATUS_PENDING,
            'message' => 'Join our gaming session!',
        ];

        $invitation = new GamingSessionInvitation();
        $invitation->fill($data);

        $this->assertEquals(1, $invitation->gaming_session_id);
        $this->assertEquals(1, $invitation->invited_by_user_id);
        $this->assertEquals(2, $invitation->invited_user_id);
        $this->assertEquals(GamingSessionInvitation::STATUS_PENDING, $invitation->status);
        $this->assertEquals('Join our gaming session!', $invitation->message);
    }

    public function test_status_constants_are_defined()
    {
        $this->assertEquals('pending', GamingSessionInvitation::STATUS_PENDING);
        $this->assertEquals('accepted', GamingSessionInvitation::STATUS_ACCEPTED);
        $this->assertEquals('declined', GamingSessionInvitation::STATUS_DECLINED);
    }

    public function test_invitation_has_fillable_attributes()
    {
        $fillable = [
            'gaming_session_id',
            'invited_user_id',
            'invited_group_id',
            'invited_by_user_id',
            'status',
            'message',
            'responded_at',
        ];

        $invitation = new GamingSessionInvitation();
        $this->assertEquals($fillable, $invitation->getFillable());
    }

    public function test_invitation_casts_attributes()
    {
        $invitation = GamingSessionInvitation::factory()->create([
            'responded_at' => '2023-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->updated_at);
    }

    public function test_invitation_belongs_to_invited_group()
    {
        $group = \App\Models\Group::factory()->create();
        $invitation = GamingSessionInvitation::factory()->create([
            'invited_group_id' => $group->id,
            'invited_user_id' => null, // Group invitation, not user
        ]);

        $this->assertEquals($group->id, $invitation->invitedGroup->id);
        $this->assertEquals($group->name, $invitation->invitedGroup->name);
    }

    public function test_is_user_invitation_method()
    {
        $userInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => User::factory()->create()->id,
            'invited_group_id' => null,
        ]);

        $groupInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => null,
            'invited_group_id' => \App\Models\Group::factory()->create()->id,
        ]);

        $this->assertTrue($userInvitation->isUserInvitation());
        $this->assertFalse($groupInvitation->isUserInvitation());
    }

    public function test_is_group_invitation_method()
    {
        $userInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => User::factory()->create()->id,
            'invited_group_id' => null,
        ]);

        $groupInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => null,
            'invited_group_id' => \App\Models\Group::factory()->create()->id,
        ]);

        $this->assertFalse($userInvitation->isGroupInvitation());
        $this->assertTrue($groupInvitation->isGroupInvitation());
    }

    public function test_user_invitations_scope()
    {
        $user = User::factory()->create();
        $group = \App\Models\Group::factory()->create();

        $userInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => $user->id,
            'invited_group_id' => null,
        ]);

        $groupInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => null,
            'invited_group_id' => $group->id,
        ]);

        $userInvitations = GamingSessionInvitation::userInvitations()->get();

        $this->assertEquals(1, $userInvitations->count());
        $this->assertTrue($userInvitations->contains('id', $userInvitation->id));
        $this->assertFalse($userInvitations->contains('id', $groupInvitation->id));
        $this->assertTrue($userInvitations->every(fn($invitation) => $invitation->isUserInvitation()));
    }

    public function test_group_invitations_scope()
    {
        $user = User::factory()->create();
        $group = \App\Models\Group::factory()->create();

        $userInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => $user->id,
            'invited_group_id' => null,
        ]);

        $groupInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => null,
            'invited_group_id' => $group->id,
        ]);

        $groupInvitations = GamingSessionInvitation::groupInvitations()->get();

        $this->assertEquals(1, $groupInvitations->count());
        $this->assertTrue($groupInvitations->contains('id', $groupInvitation->id));
        $this->assertFalse($groupInvitations->contains('id', $userInvitation->id));
        $this->assertTrue($groupInvitations->every(fn($invitation) => $invitation->isGroupInvitation()));
    }

    public function test_accept_user_invitation_creates_participant()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();
        
        $invitation = GamingSessionInvitation::factory()->pending()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $user->id,
            'invited_group_id' => null,
        ]);

        // Ensure no participant exists initially
        $this->assertDatabaseMissing('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ]);

        $result = $invitation->accept();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);

        // Check that participant was created
        $this->assertDatabaseHas('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_accept_group_invitation_does_not_create_participant()
    {
        $session = GamingSession::factory()->create();
        $group = \App\Models\Group::factory()->create();
        
        $invitation = GamingSessionInvitation::factory()->pending()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => null,
            'invited_group_id' => $group->id,
        ]);

        $participantCountBefore = \App\Models\GamingSessionParticipant::count();

        $result = $invitation->accept();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);

        // Check that no new participants were created
        $participantCountAfter = \App\Models\GamingSessionParticipant::count();
        $this->assertEquals($participantCountBefore, $participantCountAfter);
    }

    public function test_decline_sets_responded_at()
    {
        $invitation = GamingSessionInvitation::factory()->pending()->create([
            'responded_at' => null,
        ]);

        $result = $invitation->decline();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->responded_at);
    }

    public function test_accept_sets_responded_at()
    {
        $invitation = GamingSessionInvitation::factory()->pending()->create([
            'responded_at' => null,
        ]);

        $result = $invitation->accept();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->responded_at);
    }

    public function test_gaming_session_invitation_can_be_created_with_complete_data()
    {
        $session = GamingSession::factory()->create();
        $inviter = User::factory()->create();
        $invitee = User::factory()->create();
        
        $invitationData = [
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitee->id,
            'invited_group_id' => null,
            'invited_by_user_id' => $inviter->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
            'message' => 'Join our awesome gaming session!',
            'responded_at' => null,
        ];

        $invitation = GamingSessionInvitation::create($invitationData);

        $this->assertInstanceOf(GamingSessionInvitation::class, $invitation);
        $this->assertEquals($session->id, $invitation->gaming_session_id);
        $this->assertEquals($invitee->id, $invitation->invited_user_id);
        $this->assertNull($invitation->invited_group_id);
        $this->assertEquals($inviter->id, $invitation->invited_by_user_id);
        $this->assertEquals(GamingSessionInvitation::STATUS_PENDING, $invitation->status);
        $this->assertEquals('Join our awesome gaming session!', $invitation->message);
        $this->assertNull($invitation->responded_at);
        $this->assertTrue($invitation->isUserInvitation());
        $this->assertFalse($invitation->isGroupInvitation());
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $targetInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => $user->id,
            'invited_group_id' => null,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $otherInvitation = GamingSessionInvitation::factory()->create([
            'invited_user_id' => $otherUser->id,
            'invited_group_id' => null,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $results = GamingSessionInvitation::userInvitations()
            ->pending()
            ->forUser($user)
            ->get();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->contains('id', $targetInvitation->id));
        $this->assertFalse($results->contains('id', $otherInvitation->id));
    }
}
