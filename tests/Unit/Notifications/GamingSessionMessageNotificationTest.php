<?php

namespace Tests\Unit\Notifications;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use App\Notifications\GamingSessionMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class GamingSessionMessageNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $messageAuthor;
    private User $recipient;
    private GamingSession $session;
    private GamingSessionMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->messageAuthor = User::factory()->create(['name' => 'John Sender']);
        $this->recipient = User::factory()->create(['name' => 'Jane Recipient']);

        $this->session = GamingSession::factory()->create([
            'host_user_id' => $this->messageAuthor->id,
            'title' => 'Epic Gaming Session'
        ]);

        $this->message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->messageAuthor->id,
            'message' => 'Hey everyone, ready to game?'
        ]);
    }

    public function test_constructor_sets_message_id()
    {
        $notification = new GamingSessionMessageNotification($this->message);

        $this->assertEquals($this->message->id, $notification->messageId);
    }

    public function test_get_message_loads_relationships()
    {
        $notification = new GamingSessionMessageNotification($this->message);
        $retrievedMessage = $notification->getMessage();

        $this->assertEquals($this->message->id, $retrievedMessage->id);
        $this->assertTrue($retrievedMessage->relationLoaded('gamingSession'));
        $this->assertTrue($retrievedMessage->relationLoaded('user'));
    }

    public function test_via_returns_correct_channels()
    {
        $notification = new GamingSessionMessageNotification($this->message);
        $channels = $notification->via($this->recipient);

        $this->assertEquals(['database', 'broadcast'], $channels);
    }

    public function test_to_mail_contains_correct_content()
    {
        $notification = new GamingSessionMessageNotification($this->message);
        $mailMessage = $notification->toMail($this->recipient);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('New message in Epic Gaming Session', $mailMessage->subject);
        $this->assertStringContainsString('John Sender posted a new message', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Epic Gaming Session', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Hey everyone, ready to game?', $mailMessage->introLines[1]);
        $this->assertEquals('View Messages', $mailMessage->actionText);
        $this->assertStringContainsString('gaming-sessions/' . $this->session->id . '/messages', $mailMessage->actionUrl);
        $this->assertEquals('Join the conversation!', $mailMessage->outroLines[0]);
    }

    public function test_to_array_returns_correct_structure()
    {
        $notification = new GamingSessionMessageNotification($this->message);
        $array = $notification->toArray($this->recipient);

        $expectedArray = [
            'type' => 'gaming_session_message',
            'message_id' => $this->message->id,
            'session_id' => $this->session->id,
            'session_title' => 'Epic Gaming Session',
            'sender_name' => 'John Sender',
            'sender_id' => $this->messageAuthor->id,
            'message_preview' => 'Hey everyone, ready to game?', // Full message since it's under 100 chars
            'url' => route('gaming-sessions.messages.index', $this->session),
        ];

        $this->assertEquals($expectedArray, $array);
    }

    public function test_to_array_truncates_long_message_preview()
    {
        $longMessage = str_repeat('This is a very long message content. ', 10); // About 370 chars
        $this->message->update(['message' => $longMessage]);
        $this->message->refresh();

        $notification = new GamingSessionMessageNotification($this->message);
        $array = $notification->toArray($this->recipient);

        $this->assertEquals(100, strlen($array['message_preview']));
        $this->assertEquals(substr($longMessage, 0, 100), $array['message_preview']);
    }

    public function test_to_broadcast_returns_correct_structure()
    {
        $notification = new GamingSessionMessageNotification($this->message);
        $broadcastMessage = $notification->toBroadcast($this->recipient);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastMessage);

        $data = $broadcastMessage->data;
        $this->assertEquals('gaming_session_message', $data['type']);
        $this->assertEquals('New Message in Epic Gaming Session', $data['title']);
        $this->assertStringContainsString('John Sender: Hey everyone, ready to game?', $data['body']);
        $this->assertEquals('/images/message-icon.png', $data['icon']);
        $this->assertStringContainsString('gaming-sessions/' . $this->session->id . '/messages', $data['url']);
        $this->assertArrayHasKey('data', $data);
    }

    public function test_to_broadcast_truncates_long_message_in_body()
    {
        $longMessage = str_repeat('This is a very long message that should be truncated. ', 5); // About 270 chars
        $this->message->update(['message' => $longMessage]);
        $this->message->refresh();

        $notification = new GamingSessionMessageNotification($this->message);
        $broadcastMessage = $notification->toBroadcast($this->recipient);

        $data = $broadcastMessage->data;
        $expectedBody = 'John Sender: ' . substr($longMessage, 0, 50) . '...';
        $this->assertEquals($expectedBody, $data['body']);
    }

    public function test_implements_should_queue()
    {
        $notification = new GamingSessionMessageNotification($this->message);

        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification));
    }

    public function test_relationships_loaded_in_notification()
    {
        $notification = new GamingSessionMessageNotification($this->message);

        // Access the relationships to ensure they work
        $retrievedMessage = $notification->getMessage();
        $this->assertEquals($this->session->title, $retrievedMessage->gamingSession->title);
        $this->assertEquals($this->messageAuthor->name, $retrievedMessage->user->name);
    }

    public function test_edge_case_exactly_50_chars_in_broadcast_body()
    {
        $fiftyCharMessage = str_repeat('a', 50); // Exactly 50 characters
        $this->message->update(['message' => $fiftyCharMessage]);
        $this->message->refresh();

        $notification = new GamingSessionMessageNotification($this->message);
        $broadcastMessage = $notification->toBroadcast($this->recipient);

        $data = $broadcastMessage->data;
        $expectedBody = 'John Sender: ' . $fiftyCharMessage . '...';
        $this->assertEquals($expectedBody, $data['body']);
    }

    public function test_edge_case_exactly_100_chars_in_array_preview()
    {
        $hundredCharMessage = str_repeat('a', 100); // Exactly 100 characters
        $this->message->update(['message' => $hundredCharMessage]);
        $this->message->refresh();

        $notification = new GamingSessionMessageNotification($this->message);
        $array = $notification->toArray($this->recipient);

        $this->assertEquals($hundredCharMessage, $array['message_preview']);
        $this->assertEquals(100, strlen($array['message_preview']));
    }
}
