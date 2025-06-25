<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Notifications\GamingSessionInvitation as GamingSessionInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamingSessionInvitationNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $host;
    protected $invitee;
    protected $gamingSession;
    protected $invitation;
    protected $notification;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['name' => 'John Host']);
        $this->invitee = User::factory()->create(['name' => 'Jane Invitee']);

        $this->gamingSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'title' => 'Epic Gaming Session',
            'game_name' => 'Call of Duty',
            'scheduled_at' => now()->addDays(2),
            'max_participants' => 8,
            'description' => 'Join us for an epic gaming session!',
            'requirements' => 'Microphone required',
        ]);

        $this->invitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitee->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $this->notification = new GamingSessionInvitationNotification($this->invitation);
    }

    public function test_notification_can_be_instantiated()
    {
        $this->assertInstanceOf(GamingSessionInvitationNotification::class, $this->notification);
    }

    public function test_notification_implements_should_queue()
    {
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $this->notification);
    }

    public function test_notification_uses_queueable_trait()
    {
        $this->assertTrue(method_exists($this->notification, 'onQueue'));
        $this->assertTrue(method_exists($this->notification, 'delay'));
    }

    public function test_notification_via_channels()
    {
        $channels = $this->notification->via($this->invitee);

        $this->assertIsArray($channels);
        $this->assertCount(2, $channels);
        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
    }

    public function test_notification_mail_message_structure()
    {
        $mailMessage = $this->notification->toMail($this->invitee);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);

        // Check subject
        $this->assertEquals("🎮 You're invited to a gaming session!", $mailMessage->subject);

        // Check greeting
        $this->assertEquals("Hey {$this->invitee->name}!", $mailMessage->greeting);

        // Check action button
        $this->assertNotEmpty($mailMessage->actionText);
        $this->assertEquals('View Session & Respond', $mailMessage->actionText);
        $this->assertStringContainsString('gaming-sessions', $mailMessage->actionUrl);
    }

    public function test_notification_mail_content_includes_session_details()
    {
        $mailMessage = $this->notification->toMail($this->invitee);

        // Convert lines to string for easier testing
        $allLines = collect($mailMessage->introLines)
            ->merge($mailMessage->outroLines)
            ->implode(' ');

        $this->assertStringContainsString($this->host->name, $allLines);
        $this->assertStringContainsString($this->gamingSession->title, $allLines);
        $this->assertStringContainsString($this->gamingSession->game_name, $allLines);
        $this->assertStringContainsString($this->gamingSession->max_participants, $allLines);
        $this->assertStringContainsString($this->gamingSession->description, $allLines);
        $this->assertStringContainsString($this->gamingSession->requirements, $allLines);
    }

    public function test_notification_mail_content_with_minimal_session_data()
    {
        // Create a session with minimal data (no description or requirements)
        $minimalSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'title' => 'Simple Session',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDays(1),
            'max_participants' => 4,
            'description' => null,
            'requirements' => null,
        ]);

        $minimalInvitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $minimalSession->id,
            'invited_user_id' => $this->invitee->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $minimalNotification = new GamingSessionInvitationNotification($minimalInvitation);
        $mailMessage = $minimalNotification->toMail($this->invitee);

        $allLines = collect($mailMessage->introLines)
            ->merge($mailMessage->outroLines)
            ->implode(' ');

        $this->assertStringContainsString($minimalSession->title, $allLines);
        $this->assertStringContainsString($minimalSession->game_name, $allLines);
        $this->assertStringNotContainsString('📝 Description:', $allLines);
        $this->assertStringNotContainsString('⚠️ Requirements:', $allLines);
    }

    public function test_notification_mail_formatted_date()
    {
        $scheduledDate = now()->setDate(2024, 12, 25)->setTime(15, 30, 0);

        $this->gamingSession->update(['scheduled_at' => $scheduledDate]);
        $this->gamingSession->refresh();

        // Create a new notification with the updated gaming session
        $updatedNotification = new GamingSessionInvitationNotification($this->invitation->fresh());
        $mailMessage = $updatedNotification->toMail($this->invitee);

        $allLines = collect($mailMessage->introLines)->implode(' ');
        $expectedFormat = $scheduledDate->format('M j, Y g:i A');

        $this->assertStringContainsString($expectedFormat, $allLines);
    }

    public function test_notification_to_array_structure()
    {
        $arrayData = $this->notification->toArray($this->invitee);

        $this->assertIsArray($arrayData);

        $expectedKeys = [
            'type',
            'invitation_id',
            'gaming_session_id',
            'gaming_session_title',
            'game_name',
            'scheduled_at',
            'inviter_id',
            'inviter_name',
            'message'
        ];

        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $arrayData);
        }
    }

    public function test_notification_to_array_data_values()
    {
        $arrayData = $this->notification->toArray($this->invitee);

        $this->assertEquals('gaming_session_invitation', $arrayData['type']);
        $this->assertEquals($this->invitation->id, $arrayData['invitation_id']);
        $this->assertEquals($this->gamingSession->id, $arrayData['gaming_session_id']);
        $this->assertEquals($this->gamingSession->title, $arrayData['gaming_session_title']);
        $this->assertEquals($this->gamingSession->game_name, $arrayData['game_name']);
        $this->assertEquals($this->gamingSession->scheduled_at, $arrayData['scheduled_at']);
        $this->assertEquals($this->host->id, $arrayData['inviter_id']);
        $this->assertEquals($this->host->name, $arrayData['inviter_name']);

        $expectedMessage = "{$this->host->name} invited you to join '{$this->gamingSession->title}'";
        $this->assertEquals($expectedMessage, $arrayData['message']);
    }

    public function test_notification_can_be_sent_to_user()
    {
        Notification::fake();

        $this->invitee->notify($this->notification);

        Notification::assertSentTo($this->invitee, GamingSessionInvitationNotification::class);
    }

    public function test_notification_is_not_sent_when_faked()
    {
        Notification::fake();

        $this->invitee->notify($this->notification);

        // Should not actually send notification when faked
        Notification::assertSentTo($this->invitee, GamingSessionInvitationNotification::class, function ($notification) {
            return $notification instanceof GamingSessionInvitationNotification;
        });
    }

    public function test_notification_with_group_invitation()
    {
        $group = \App\Models\Group::factory()->create(['name' => 'Gaming Legends']);

        $groupInvitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => null,
            'invited_group_id' => $group->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $groupNotification = new GamingSessionInvitationNotification($groupInvitation);

        $this->assertInstanceOf(GamingSessionInvitationNotification::class, $groupNotification);

        // Test that notification can still be created for group invitations
        $mailMessage = $groupNotification->toMail($this->invitee);
        $this->assertInstanceOf(MailMessage::class, $mailMessage);
    }

    public function test_notification_properties_are_accessible()
    {
        $reflection = new \ReflectionClass($this->notification);

        $invitationProperty = $reflection->getProperty('invitation');
        $invitationProperty->setAccessible(true);
        $this->assertEquals($this->invitation->id, $invitationProperty->getValue($this->notification)->id);

        $gamingSessionProperty = $reflection->getProperty('gamingSession');
        $gamingSessionProperty->setAccessible(true);
        $this->assertEquals($this->gamingSession->id, $gamingSessionProperty->getValue($this->notification)->id);

        $inviterProperty = $reflection->getProperty('inviter');
        $inviterProperty->setAccessible(true);
        $this->assertEquals($this->host->id, $inviterProperty->getValue($this->notification)->id);
    }

    public function test_notification_mail_action_url_is_correct()
    {
        $mailMessage = $this->notification->toMail($this->invitee);

        $expectedUrl = route('gaming-sessions.show', $this->gamingSession);
        $this->assertEquals($expectedUrl, $mailMessage->actionUrl);
    }

    public function test_notification_handles_different_user_names()
    {
        $hostWithSpecialChars = User::factory()->create(['name' => "John O'Connor & Co."]);
        $inviteeWithSpecialChars = User::factory()->create(['name' => 'María José']);

        $specialInvitation = GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $inviteeWithSpecialChars->id,
            'invited_by_user_id' => $hostWithSpecialChars->id,
        ]);

        $specialNotification = new GamingSessionInvitationNotification($specialInvitation);

        $mailMessage = $specialNotification->toMail($inviteeWithSpecialChars);
        $arrayData = $specialNotification->toArray($inviteeWithSpecialChars);

        $this->assertStringContainsString($hostWithSpecialChars->name, collect($mailMessage->introLines)->implode(' '));
        $this->assertStringContainsString($inviteeWithSpecialChars->name, $mailMessage->greeting);
        $this->assertStringContainsString($hostWithSpecialChars->name, $arrayData['message']);
    }

    public function test_notification_mail_includes_community_message()
    {
        $mailMessage = $this->notification->toMail($this->invitee);

        $outroLines = collect($mailMessage->outroLines)->implode(' ');

        $this->assertStringContainsString('Thanks for being part of our gaming community!', $outroLines);
        $this->assertStringContainsString('Click the button above to view the session details', $outroLines);
    }

    public function test_notification_with_long_session_title()
    {
        $longTitle = str_repeat('Epic Gaming Session ', 10); // Very long title

        $this->gamingSession->update(['title' => $longTitle]);
        $this->gamingSession->refresh();

        // Create a new notification with the updated gaming session
        $updatedNotification = new GamingSessionInvitationNotification($this->invitation->fresh());
        $mailMessage = $updatedNotification->toMail($this->invitee);
        $arrayData = $updatedNotification->toArray($this->invitee);

        $this->assertStringContainsString($longTitle, collect($mailMessage->introLines)->implode(' '));
        $this->assertEquals($longTitle, $arrayData['gaming_session_title']);
    }

    public function test_notification_constructor_loads_relationships()
    {
        // Create a fresh invitation without eager loading
        $freshInvitation = GamingSessionInvitation::find($this->invitation->id);

        // Verify relationships are not loaded initially
        $this->assertFalse($freshInvitation->relationLoaded('gamingSession'));
        $this->assertFalse($freshInvitation->relationLoaded('invitedBy'));

        // Create notification (this should trigger relationship loading)
        $notification = new GamingSessionInvitationNotification($freshInvitation);

        // Test that notification works properly (implying relationships were loaded)
        $mailMessage = $notification->toMail($this->invitee);
        $this->assertInstanceOf(MailMessage::class, $mailMessage);

        // Verify the notification has access to the gaming session and inviter
        $arrayData = $notification->toArray($this->invitee);
        $this->assertEquals($this->gamingSession->title, $arrayData['gaming_session_title']);
        $this->assertEquals($this->host->name, $arrayData['inviter_name']);
    }
}
