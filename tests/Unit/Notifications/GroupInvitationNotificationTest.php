<?php

namespace Tests\Unit\Notifications;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class GroupInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $inviter;
    private User $invitee;
    private Group $group;
    private GroupInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->inviter = User::factory()->create(['name' => 'John Inviter']);
        $this->invitee = User::factory()->create(['name' => 'Jane Invitee']);
        $this->group = Group::factory()->create([
            'name' => 'Test Gaming Group',
            'owner_id' => $this->inviter->id
        ]);
        
        $this->invitation = GroupInvitation::factory()->create([
            'group_id' => $this->group->id,
            'invited_by_user_id' => $this->inviter->id,
            'invited_user_id' => $this->invitee->id,
            'message' => 'Join our awesome gaming group!'
        ]);
    }

    public function test_constructor_loads_relationships()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'sent');

        $this->assertEquals($this->invitation->id, $notification->invitationId);
        $this->assertEquals('sent', $notification->action);
        
        // Test that the getInvitation method works and loads relationships
        $invitation = $notification->getInvitation();
        $this->assertEquals($this->invitation->id, $invitation->id);
        $this->assertTrue($invitation->relationLoaded('group'));
        $this->assertTrue($invitation->relationLoaded('invitedUser'));
        $this->assertTrue($invitation->relationLoaded('invitedBy'));
    }

    public function test_via_returns_correct_channels()
    {
        $notification = new GroupInvitationNotification($this->invitation);
        $channels = $notification->via($this->invitee);

        $this->assertEquals(['database', 'broadcast'], $channels);
    }

    public function test_to_mail_for_sent_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'sent');
        $mailMessage = $notification->toMail($this->invitee);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Group Invitation Update', $mailMessage->subject);
        $this->assertStringContainsString('John Inviter invited you to join', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Test Gaming Group', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Join our awesome gaming group!', $mailMessage->introLines[1]);
        $this->assertEquals('View Group', $mailMessage->actionText);
        $this->assertStringContainsString('groups/' . $this->group->id, $mailMessage->actionUrl);
    }

    public function test_to_mail_for_accepted_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'accepted');
        $mailMessage = $notification->toMail($this->inviter);

        $this->assertStringContainsString('accepted your invitation to', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Test Gaming Group', $mailMessage->introLines[0]);
    }

    public function test_to_mail_for_declined_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'declined');
        $mailMessage = $notification->toMail($this->inviter);

        $this->assertStringContainsString('declined your invitation to', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Test Gaming Group', $mailMessage->introLines[0]);
    }

    public function test_to_mail_without_message()
    {
        $this->invitation->update(['message' => null]);
        $this->invitation->refresh();

        $notification = new GroupInvitationNotification($this->invitation, 'sent');
        $mailMessage = $notification->toMail($this->invitee);

        // Should only have one intro line when no message
        $this->assertCount(1, $mailMessage->introLines);
    }

    public function test_to_array_returns_correct_structure()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'sent');
        $array = $notification->toArray($this->invitee);

        $this->assertEquals([
            'type' => 'group_invitation',
            'invitation_id' => $this->invitation->id,
            'action' => 'sent',
            'group_name' => 'Test Gaming Group',
            'group_id' => $this->group->id,
            'inviter_name' => 'John Inviter',
            'inviter_id' => $this->inviter->id,
            'message' => 'Join our awesome gaming group!',
            'url' => route('groups.show', $this->group),
        ], $array);
    }

    public function test_to_broadcast_for_sent_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'sent');
        $broadcastMessage = $notification->toBroadcast($this->invitee);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastMessage);
        
        $data = $broadcastMessage->data;
        $this->assertEquals('group_invitation', $data['type']);
        $this->assertEquals('Group Invitation', $data['title']);
        $this->assertStringContainsString('John Inviter invited you to join Test Gaming Group', $data['body']);
        $this->assertEquals('/images/group-icon.png', $data['icon']);
        $this->assertStringContainsString('groups/' . $this->group->id, $data['url']);
    }

    public function test_to_broadcast_for_accepted_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'accepted');
        $broadcastMessage = $notification->toBroadcast($this->inviter);

        $data = $broadcastMessage->data;
        $this->assertEquals('Group Invitation Accepted', $data['title']);
        $this->assertStringContainsString('Jane Invitee joined Test Gaming Group', $data['body']);
    }

    public function test_to_broadcast_for_declined_invitation()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'declined');
        $broadcastMessage = $notification->toBroadcast($this->inviter);

        $data = $broadcastMessage->data;
        $this->assertEquals('Group Invitation Declined', $data['title']);
        $this->assertStringContainsString('Jane Invitee declined to join Test Gaming Group', $data['body']);
    }

    public function test_implements_should_queue()
    {
        $notification = new GroupInvitationNotification($this->invitation);
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification));
    }

    public function test_action_match_statement_handles_unknown_action()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'unknown_action');
        $mailMessage = $notification->toMail($this->invitee);

        $this->assertStringContainsString('updated your invitation to', $mailMessage->introLines[0]);
    }

    public function test_broadcast_match_statement_handles_unknown_action()
    {
        $notification = new GroupInvitationNotification($this->invitation, 'unknown_action');
        $broadcastMessage = $notification->toBroadcast($this->invitee);

        $data = $broadcastMessage->data;
        $this->assertEquals('Group Invitation Update', $data['title']);
        $this->assertEquals('Group invitation updated', $data['body']);
    }
}
