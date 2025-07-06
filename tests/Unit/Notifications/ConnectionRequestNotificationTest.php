<?php

namespace Tests\Unit\Notifications;

use App\Models\User;
use App\Models\UserConnection;
use App\Notifications\ConnectionRequestNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Tests\TestCase;

class ConnectionRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    private User $requester;
    private User $recipient;
    private UserConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->requester = User::factory()->create(['name' => 'John Requester']);
        $this->recipient = User::factory()->create(['name' => 'Jane Recipient']);
        
        $this->connection = UserConnection::factory()->create([
            'requester_id' => $this->requester->id,
            'recipient_id' => $this->recipient->id,
            'message' => 'Let\'s game together!',
            'status' => 'pending'
        ]);
    }

    public function test_constructor_loads_relationships()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'sent');

        $this->assertEquals($this->connection->id, $notification->connectionId);
        $this->assertEquals('sent', $notification->action);
        
        // Test that the getConnection method works and loads relationships
        $connection = $notification->getConnection();
        $this->assertEquals($this->connection->id, $connection->id);
        $this->assertTrue($connection->relationLoaded('requester'));
    }

    public function test_via_returns_correct_channels()
    {
        $notification = new ConnectionRequestNotification($this->connection);
        $channels = $notification->via($this->recipient);

        $this->assertEquals(['database', 'broadcast'], $channels);
    }

    public function test_to_mail_for_sent_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'sent');
        $mailMessage = $notification->toMail($this->recipient);

        $this->assertInstanceOf(MailMessage::class, $mailMessage);
        $this->assertEquals('Connection Request Update', $mailMessage->subject);
        $this->assertStringContainsString('John Requester sent you a connection request', $mailMessage->introLines[0]);
        $this->assertStringContainsString('Let\'s game together!', $mailMessage->introLines[1]);
        $this->assertEquals('View Connections', $mailMessage->actionText);
        $this->assertStringContainsString('social/requests', $mailMessage->actionUrl);
    }

    public function test_to_mail_for_accepted_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'accepted');
        $mailMessage = $notification->toMail($this->requester);

        $this->assertStringContainsString('accepted your connection request', $mailMessage->introLines[0]);
    }

    public function test_to_mail_for_declined_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'declined');
        $mailMessage = $notification->toMail($this->requester);

        $this->assertStringContainsString('declined your connection request', $mailMessage->introLines[0]);
    }

    public function test_to_mail_without_message()
    {
        $this->connection->update(['message' => null]);
        $this->connection->refresh();

        $notification = new ConnectionRequestNotification($this->connection, 'sent');
        $mailMessage = $notification->toMail($this->recipient);

        // Should only have one intro line when no message
        $this->assertCount(1, $mailMessage->introLines);
    }

    public function test_to_array_returns_correct_structure()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'sent');
        $array = $notification->toArray($this->recipient);

        $this->assertEquals([
            'type' => 'connection_request',
            'connection_id' => $this->connection->id,
            'action' => 'sent',
            'requester_name' => 'John Requester',
            'requester_id' => $this->requester->id,
            'message' => 'Let\'s game together!',
            'url' => route('social.requests'),
        ], $array);
    }

    public function test_to_broadcast_for_sent_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'sent');
        $broadcastMessage = $notification->toBroadcast($this->recipient);

        $this->assertInstanceOf(BroadcastMessage::class, $broadcastMessage);
        
        $data = $broadcastMessage->data;
        $this->assertEquals('connection_request', $data['type']);
        $this->assertEquals('New Connection Request', $data['title']);
        $this->assertStringContainsString('John Requester wants to connect with you', $data['body']);
        $this->assertEquals('/images/connection-icon.png', $data['icon']);
        $this->assertStringContainsString('social/requests', $data['url']);
    }

    public function test_to_broadcast_for_accepted_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'accepted');
        $broadcastMessage = $notification->toBroadcast($this->requester);

        $data = $broadcastMessage->data;
        $this->assertEquals('Connection Request Accepted', $data['title']);
        $this->assertStringContainsString('John Requester accepted your connection request', $data['body']);
    }

    public function test_to_broadcast_for_declined_request()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'declined');
        $broadcastMessage = $notification->toBroadcast($this->requester);

        $data = $broadcastMessage->data;
        $this->assertEquals('Connection Request Declined', $data['title']);
        $this->assertStringContainsString('John Requester declined your connection request', $data['body']);
    }

    public function test_implements_should_queue()
    {
        $notification = new ConnectionRequestNotification($this->connection);
        
        $this->assertInstanceOf(\Illuminate\Contracts\Queue\ShouldQueue::class, $notification);
        $this->assertContains(\Illuminate\Bus\Queueable::class, class_uses_recursive($notification));
    }

    public function test_action_match_statement_handles_unknown_action()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'unknown_action');
        $mailMessage = $notification->toMail($this->recipient);

        $this->assertStringContainsString('updated your connection request', $mailMessage->introLines[0]);
    }

    public function test_broadcast_match_statement_handles_unknown_action()
    {
        $notification = new ConnectionRequestNotification($this->connection, 'unknown_action');
        $broadcastMessage = $notification->toBroadcast($this->recipient);

        $data = $broadcastMessage->data;
        $this->assertEquals('Connection Update', $data['title']);
        $this->assertEquals('Connection request updated', $data['body']);
    }
}
