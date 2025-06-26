<?php

namespace Tests\Unit\Notifications;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\User;
use App\Notifications\GamingSessionInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class GamingSessionInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $host;
    private User $invitee;
    private GamingSession $session;
    private GamingSessionInvitation $invitation;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['name' => 'John Host']);
        $this->invitee = User::factory()->create(['name' => 'Jane Player']);
        
        $this->session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'title' => 'Epic Gaming Night',
            'game_name' => 'Call of Duty: Modern Warfare',
            'scheduled_at' => Carbon::parse('2025-07-01 20:00:00')
        ]);
        
        $this->invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $this->session->id,
            'invited_user_id' => $this->invitee->id,
            'message' => 'Join us for some fun!',
            'status' => 'pending'
        ]);
    }

    public function test_constructor_loads_relationships()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');

        $this->assertEquals($this->invitation->id, $notification->invitation->id);
        $this->assertEquals('sent', $notification->action);
        $this->assertTrue($notification->invitation->relationLoaded('gamingSession'));
        $this->assertTrue($notification->invitation->relationLoaded('invitedUser'));
    }

    public function test_via_returns_correct_channels()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation);
        $channels = $notification->via($this->invitee);

        $this->assertEquals(['database', 'broadcast'], $channels);
    }

    public function test_to_mail_for_sent_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $mailMessage = $notification->toMail($this->invitee);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Gaming Session Invitation', $mailMessage->subject);
        $this->assertStringContainsString('John Host invited you to join', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Epic Gaming Night', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Call of Duty: Modern Warfare', $mailMessage->introLines[1]);
        $this->assertStringContainsString('Jul 1, 2025 at 8:00 PM', $mailMessage->introLines[2]);
        $this->assertStringContainsString('Join us for some fun!', $mailMessage->introLines[3]);
        $this->assertEquals('View Session', $mailMessage->actionText);
    }

    public function test_to_mail_for_accepted_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'accepted');
        $mailMessage = $notification->toMail($this->host);

        $this->assertStringContainsString('accepted your invitation to', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Epic Gaming Night', $mailMessage->introLines[0]);
    }

    public function test_to_mail_for_declined_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'declined');
        $mailMessage = $notification->toMail($this->host);

        $this->assertStringContainsString('declined your invitation to', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Epic Gaming Night', $mailMessage->introLines[0]);
    }

    public function test_to_mail_without_message()
    {
        $this->invitation->update(['message' => null]);
        $this->invitation->refresh();

        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $mailMessage = $notification->toMail($this->invitee);

        // Should have 3 intro lines when no message (host, game, scheduled time)
        $this->assertCount(3, $mailMessage->introLines);
    }

    public function test_to_array_returns_correct_structure()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $array = $notification->toArray($this->invitee);

        $expectedArray = [
            'type' => 'gaming_session_invitation',
            'invitation_id' => $this->invitation->id,
            'action' => 'sent',
            'session_title' => 'Epic Gaming Night',
            'session_id' => $this->session->id,
            'game_name' => 'Call of Duty: Modern Warfare',
            'host_name' => 'John Host',
            'host_id' => $this->host->id,
            'scheduled_at' => $this->session->scheduled_at->toISOString(),
            'message' => 'Join us for some fun!',
            'url' => route('gaming-sessions.show', $this->session),
        ];

        $this->assertEquals($expectedArray, $array);
    }

    public function test_to_broadcast_for_sent_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $broadcastMessage = $notification->toBroadcast($this->invitee);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastMessage);
        
        $data = $broadcastMessage->data;
        $this->assertEquals('gaming_session_invitation', $data['type']);
        $this->assertEquals('Gaming Session Invitation', $data['title']);
        $this->assertStringContainsString('John Host invited you to play Call of Duty: Modern Warfare', $data['body']);
        $this->assertEquals('/images/gaming-icon.png', $data['icon']);
        $this->assertStringContainsString('gaming-sessions/' . $this->session->id, $data['url']);
    }

    public function test_to_broadcast_for_accepted_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'accepted');
        $broadcastMessage = $notification->toBroadcast($this->host);

        $data = $broadcastMessage->data;
        $this->assertEquals('Session Invitation Accepted', $data['title']);
        $this->assertStringContainsString('Jane Player will join your Call of Duty: Modern Warfare session', $data['body']);
    }

    public function test_to_broadcast_for_declined_invitation()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'declined');
        $broadcastMessage = $notification->toBroadcast($this->host);

        $data = $broadcastMessage->data;
        $this->assertEquals('Session Invitation Declined', $data['title']);
        $this->assertStringContainsString('Jane Player declined your Call of Duty: Modern Warfare session', $data['body']);
    }

    public function test_implements_should_queue()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation);
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification));
    }

    public function test_action_match_statement_handles_unknown_action()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'unknown_action');
        $mailMessage = $notification->toMail($this->invitee);

        $this->assertStringContainsString('updated your invitation to', $mailMessage->introLines[0]);
    }

    public function test_broadcast_match_statement_handles_unknown_action()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'unknown_action');
        $broadcastMessage = $notification->toBroadcast($this->invitee);

        $data = $broadcastMessage->data;
        $this->assertEquals('Session Invitation Update', $data['title']);
        $this->assertEquals('Gaming session invitation updated', $data['body']);
    }

    public function test_date_formatting_in_mail()
    {
        // Test with different date formats
        $this->session->update(['scheduled_at' => Carbon::parse('2025-12-25 15:30:00')]);
        $this->session->refresh();

        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $mailMessage = $notification->toMail($this->invitee);

        $this->assertStringContainsString('Dec 25, 2025 at 3:30 PM', $mailMessage->introLines[2]);
    }

    public function test_iso_string_in_array_output()
    {
        $notification = new GamingSessionInvitationNotification($this->invitation, 'sent');
        $array = $notification->toArray($this->invitee);

        $this->assertStringContainsString('T', $array['scheduled_at']); // ISO format contains 'T'
        $this->assertStringContainsString('Z', $array['scheduled_at']); // ISO format ends with 'Z'
    }
}
