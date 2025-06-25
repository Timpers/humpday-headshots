<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupInvitationTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_invitation_belongs_to_group()
    {
        $group = Group::factory()->create();
        $invitation = GroupInvitation::factory()->create(['group_id' => $group->id]);

        $this->assertEquals($group->id, $invitation->group->id);
        $this->assertEquals($group->name, $invitation->group->name);
    }

    public function test_group_invitation_belongs_to_invited_user()
    {
        $user = User::factory()->create();
        $invitation = GroupInvitation::factory()->create(['invited_user_id' => $user->id]);

        $this->assertEquals($user->id, $invitation->invitedUser->id);
        $this->assertEquals($user->name, $invitation->invitedUser->name);
    }

    public function test_group_invitation_belongs_to_invited_by_user()
    {
        $user = User::factory()->create();
        $invitation = GroupInvitation::factory()->create(['invited_by_user_id' => $user->id]);

        $this->assertEquals($user->id, $invitation->invitedBy->id);
        $this->assertEquals($user->name, $invitation->invitedBy->name);
    }

    public function test_group_invitation_has_fillable_attributes()
    {
        $fillable = [
            'group_id',
            'invited_user_id',
            'invited_by_user_id',
            'status',
            'message',
            'responded_at',
        ];

        $invitation = new GroupInvitation();
        $this->assertEquals($fillable, $invitation->getFillable());
    }

    public function test_group_invitation_casts_attributes()
    {
        $invitation = GroupInvitation::factory()->create([
            'responded_at' => '2023-01-15 10:30:00',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->responded_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->created_at);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $invitation->updated_at);
    }

    public function test_status_constants_are_defined()
    {
        $expectedStatuses = [
            'pending' => 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
        ];

        $this->assertEquals('pending', GroupInvitation::STATUS_PENDING);
        $this->assertEquals('accepted', GroupInvitation::STATUS_ACCEPTED);
        $this->assertEquals('declined', GroupInvitation::STATUS_DECLINED);
        $this->assertEquals('cancelled', GroupInvitation::STATUS_CANCELLED);
        $this->assertEquals($expectedStatuses, GroupInvitation::STATUSES);
    }

    public function test_by_status_scope()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();
        $declinedInvitation = GroupInvitation::factory()->declined()->create();

        $pendingInvitations = GroupInvitation::byStatus(GroupInvitation::STATUS_PENDING)->get();
        $acceptedInvitations = GroupInvitation::byStatus(GroupInvitation::STATUS_ACCEPTED)->get();

        $this->assertEquals(1, $pendingInvitations->count());
        $this->assertTrue($pendingInvitations->contains('id', $pendingInvitation->id));
        $this->assertFalse($pendingInvitations->contains('id', $acceptedInvitation->id));

        $this->assertEquals(1, $acceptedInvitations->count());
        $this->assertTrue($acceptedInvitations->contains('id', $acceptedInvitation->id));
        $this->assertFalse($acceptedInvitations->contains('id', $declinedInvitation->id));
    }

    public function test_pending_scope()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();

        $pendingInvitations = GroupInvitation::pending()->get();

        $this->assertEquals(1, $pendingInvitations->count());
        $this->assertTrue($pendingInvitations->contains('id', $pendingInvitation->id));
        $this->assertFalse($pendingInvitations->contains('id', $acceptedInvitation->id));
    }

    public function test_accepted_scope()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();

        $acceptedInvitations = GroupInvitation::accepted()->get();

        $this->assertEquals(1, $acceptedInvitations->count());
        $this->assertTrue($acceptedInvitations->contains('id', $acceptedInvitation->id));
        $this->assertFalse($acceptedInvitations->contains('id', $pendingInvitation->id));
    }

    public function test_is_pending_method()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();

        $this->assertTrue($pendingInvitation->isPending());
        $this->assertFalse($acceptedInvitation->isPending());
    }

    public function test_is_accepted_method()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();

        $this->assertFalse($pendingInvitation->isAccepted());
        $this->assertTrue($acceptedInvitation->isAccepted());
    }

    public function test_is_declined_method()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $declinedInvitation = GroupInvitation::factory()->declined()->create();

        $this->assertFalse($pendingInvitation->isDeclined());
        $this->assertTrue($declinedInvitation->isDeclined());
    }

    public function test_accept_method()
    {
        $invitation = GroupInvitation::factory()->pending()->create([
            'responded_at' => null,
        ]);

        $result = $invitation->accept();

        $this->assertTrue($result);
        $invitation->refresh();
        $this->assertEquals(GroupInvitation::STATUS_ACCEPTED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);
    }

    public function test_decline_method()
    {
        $invitation = GroupInvitation::factory()->pending()->create([
            'responded_at' => null,
        ]);

        $result = $invitation->decline();

        $this->assertTrue($result);
        $invitation->refresh();
        $this->assertEquals(GroupInvitation::STATUS_DECLINED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);
    }

    public function test_cancel_method()
    {
        $invitation = GroupInvitation::factory()->pending()->create([
            'responded_at' => null,
        ]);

        $result = $invitation->cancel();

        $this->assertTrue($result);
        $invitation->refresh();
        $this->assertEquals(GroupInvitation::STATUS_CANCELLED, $invitation->status);
        $this->assertNotNull($invitation->responded_at);
    }

    public function test_status_name_attribute()
    {
        $pendingInvitation = GroupInvitation::factory()->pending()->create();
        $acceptedInvitation = GroupInvitation::factory()->accepted()->create();
        $declinedInvitation = GroupInvitation::factory()->declined()->create();
        $cancelledInvitation = GroupInvitation::factory()->cancelled()->create();

        $this->assertEquals('Pending', $pendingInvitation->status_name);
        $this->assertEquals('Accepted', $acceptedInvitation->status_name);
        $this->assertEquals('Declined', $declinedInvitation->status_name);
        $this->assertEquals('Cancelled', $cancelledInvitation->status_name);
    }

    public function test_group_invitation_factory_creates_valid_invitation()
    {
        $invitation = GroupInvitation::factory()->create();

        $this->assertInstanceOf(GroupInvitation::class, $invitation);
        $this->assertNotNull($invitation->group_id);
        $this->assertNotNull($invitation->invited_user_id);
        $this->assertNotNull($invitation->invited_by_user_id);
        $this->assertEquals(GroupInvitation::STATUS_PENDING, $invitation->status);
    }

    public function test_group_invitation_can_be_created_with_complete_data()
    {
        $group = Group::factory()->create();
        $invitedUser = User::factory()->create();
        $invitedByUser = User::factory()->create();

        $invitationData = [
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $invitedByUser->id,
            'status' => GroupInvitation::STATUS_PENDING,
            'message' => 'Join our awesome gaming group!',
            'responded_at' => null,
        ];

        $invitation = GroupInvitation::create($invitationData);

        $this->assertInstanceOf(GroupInvitation::class, $invitation);
        $this->assertEquals($group->id, $invitation->group_id);
        $this->assertEquals($invitedUser->id, $invitation->invited_user_id);
        $this->assertEquals($invitedByUser->id, $invitation->invited_by_user_id);
        $this->assertEquals(GroupInvitation::STATUS_PENDING, $invitation->status);
        $this->assertEquals('Join our awesome gaming group!', $invitation->message);
        $this->assertNull($invitation->responded_at);
    }

    public function test_multiple_invitations_can_exist_for_same_group()
    {
        $group = Group::factory()->create();
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $inviter = User::factory()->create();

        $invitation1 = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user1->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        $invitation2 = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user2->id,
            'invited_by_user_id' => $inviter->id,
        ]);

        $this->assertNotEquals($invitation1->id, $invitation2->id);
        $this->assertEquals($group->id, $invitation1->group_id);
        $this->assertEquals($group->id, $invitation2->group_id);
    }

    public function test_invitation_workflow()
    {
        $invitation = GroupInvitation::factory()->pending()->create();

        // Initially pending
        $this->assertTrue($invitation->isPending());
        $this->assertFalse($invitation->isAccepted());
        $this->assertFalse($invitation->isDeclined());

        // Accept invitation
        $invitation->accept();
        $this->assertFalse($invitation->isPending());
        $this->assertTrue($invitation->isAccepted());
        $this->assertFalse($invitation->isDeclined());

        // Reset and decline
        $invitation->update(['status' => GroupInvitation::STATUS_PENDING, 'responded_at' => null]);
        $invitation->decline();
        $this->assertFalse($invitation->isPending());
        $this->assertFalse($invitation->isAccepted());
        $this->assertTrue($invitation->isDeclined());
    }
}
