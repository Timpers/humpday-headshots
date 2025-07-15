<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\GamingSessionParticipant;
use App\Notifications\GamingSessionMessageNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GamingSessionMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $host;
    private User $participant;
    private User $outsider;
    private GamingSession $session;

    protected function setUp(): void
    {
        parent::setUp();

        $this->host = User::factory()->create(['name' => 'Session Host']);
        $this->participant = User::factory()->create(['name' => 'Participant']);
        $this->outsider = User::factory()->create(['name' => 'Outsider']);
        
        $this->session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'title' => 'Test Gaming Session',
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY
        ]);

        // Add participant to session
        GamingSessionParticipant::factory()->joined()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->participant->id,
        ]);
    }

    public function test_index_displays_messages_for_authorized_user()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Hello everyone!'
        ]);

        $response = $this->actingAs($this->host)
            ->get(route('gaming-sessions.messages.index', $this->session));

        $response->assertStatus(200);
        $response->assertViewIs('gaming-sessions.messages');
        $response->assertViewHas(['session', 'messages']);
        $response->assertSee('Hello everyone!');
    }

    public function test_index_returns_json_for_ajax_request()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Ajax message'
        ]);

        $response = $this->actingAs($this->host)
            ->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])
            ->get(route('gaming-sessions.messages.index', $this->session));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'messages',
            'has_more',
            'next_page'
        ]);
        $response->assertJsonFragment(['message' => 'Ajax message']);
    }

    public function test_index_prevents_unauthorized_access()
    {
        $response = $this->actingAs($this->outsider)
            ->get(route('gaming-sessions.messages.index', $this->session));

        $response->assertStatus(403);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get(route('gaming-sessions.messages.index', $this->session));

        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_message_for_host()
    {
        Notification::fake();

        $messageData = [
            'message' => 'Welcome to the session!',
            'type' => 'text'
        ];

        $response = $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'html'
        ]);

        $this->assertDatabaseHas('gaming_session_messages', [
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Welcome to the session!',
            'type' => 'text'
        ]);

        // Should notify participant but not host
        Notification::assertSentTo($this->participant, GamingSessionMessageNotification::class);
        Notification::assertNotSentTo($this->host, GamingSessionMessageNotification::class);
    }

    public function test_store_creates_message_for_participant()
    {
        Notification::fake();

        $messageData = [
            'message' => 'Thanks for hosting!',
            'type' => 'text'
        ];

        $response = $this->actingAs($this->participant)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gaming_session_messages', [
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'message' => 'Thanks for hosting!'
        ]);

        // Should notify host but not participant
        Notification::assertSentTo($this->host, GamingSessionMessageNotification::class);
        Notification::assertNotSentTo($this->participant, GamingSessionMessageNotification::class);
    }

    public function test_store_creates_announcement_message()
    {
        $messageData = [
            'message' => 'Game starts in 5 minutes!',
            'type' => 'announcement'
        ];

        $response = $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gaming_session_messages', [
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'type' => 'announcement'
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $response = $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_store_validates_message_length()
    {
        $messageData = [
            'message' => str_repeat('a', 1001), // Too long
        ];

        $response = $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_store_validates_message_type()
    {
        $messageData = [
            'message' => 'Valid message',
            'type' => 'invalid_type'
        ];

        $response = $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['type']);
    }

    public function test_store_prevents_unauthorized_user()
    {
        $messageData = [
            'message' => 'I should not be able to post this',
        ];

        $response = $this->actingAs($this->outsider)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(403);
    }

    public function test_store_requires_authentication()
    {
        $messageData = [
            'message' => 'Unauthenticated message',
        ];

        $response = $this->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        $response->assertStatus(401);
    }

    public function test_update_modifies_message_for_author()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Original message',
            'created_at' => now()
        ]);

        $updateData = [
            'message' => 'Updated message content'
        ];

        $response = $this->actingAs($this->host)
            ->putJson(route('gaming-sessions.messages.update', [$this->session, $message]), $updateData);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'message',
            'html'
        ]);

        $this->assertDatabaseHas('gaming_session_messages', [
            'id' => $message->id,
            'message' => 'Updated message content'
        ]);

        // Check that edited_at is set (message was marked as edited)
        $this->assertNotNull($message->fresh()->edited_at);
    }

    public function test_update_prevents_editing_others_messages()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Host message'
        ]);

        $updateData = [
            'message' => 'Participant trying to edit'
        ];

        $response = $this->actingAs($this->participant)
            ->putJson(route('gaming-sessions.messages.update', [$this->session, $message]), $updateData);

        $response->assertStatus(403);
    }

    public function test_update_prevents_message_from_different_session()
    {
        $otherSession = GamingSession::factory()->create();
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $otherSession->id,
            'user_id' => $this->host->id,
            'message' => 'Different session message',
            'created_at' => now()
        ]);

        $updateData = [
            'message' => 'Trying to update wrong session message'
        ];

        $response = $this->actingAs($this->host)
            ->putJson(route('gaming-sessions.messages.update', [$this->session, $message]), $updateData);

        $response->assertStatus(404);
    }

    public function test_update_validates_message_content()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Original message',
            'created_at' => now()
        ]);

        $response = $this->actingAs($this->host)
            ->putJson(route('gaming-sessions.messages.update', [$this->session, $message]), [
                'message' => str_repeat('a', 1001) // Too long
            ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['message']);
    }

    public function test_destroy_deletes_message_for_author()
    {
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Message to delete'
        ]);

        $response = $this->actingAs($this->host)
            ->deleteJson(route('gaming-sessions.messages.destroy', [$this->session, $message]));

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => 'Message deleted successfully'
        ]);

        $this->assertDatabaseMissing('gaming_session_messages', [
            'id' => $message->id
        ]);
    }

    public function test_destroy_allows_session_host_to_delete_any_message()
    {
        $participantMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'message' => 'Participant message'
        ]);

        $response = $this->actingAs($this->host)
            ->deleteJson(route('gaming-sessions.messages.destroy', [$this->session, $participantMessage]));

        $response->assertStatus(200);
        
        $this->assertDatabaseMissing('gaming_session_messages', [
            'id' => $participantMessage->id
        ]);
    }

    public function test_destroy_prevents_participant_from_deleting_host_message()
    {
        $hostMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Host message'
        ]);

        $response = $this->actingAs($this->participant)
            ->deleteJson(route('gaming-sessions.messages.destroy', [$this->session, $hostMessage]));

        $response->assertStatus(403);
    }

    public function test_destroy_prevents_message_from_different_session()
    {
        $otherSession = GamingSession::factory()->create();
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $otherSession->id,
            'user_id' => $this->host->id,
            'message' => 'Different session message'
        ]);

        $response = $this->actingAs($this->host)
            ->deleteJson(route('gaming-sessions.messages.destroy', [$this->session, $message]));

        $response->assertStatus(404);
    }    public function test_recent_returns_messages_since_timestamp()
    {
        // Create timestamp markers to ensure proper ordering
        $sinceTime = now()->subMinutes(30);
        
        $oldMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Old message',
            'created_at' => $sinceTime->copy()->subHour()
        ]);

        // Sleep briefly to ensure timestamp difference
        usleep(10000); // 10ms
        
        $newMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->participant->id,
            'message' => 'New message',
            'created_at' => $sinceTime->copy()->addMinutes(10)
        ]);

        $since = $sinceTime->format('Y-m-d H:i:s');

        $response = $this->actingAs($this->host)
            ->getJson(route('gaming-sessions.messages.recent', $this->session) . '?since=' . urlencode($since));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'messages',
            'html'
        ]);

        $messages = $response->json('messages');
        $this->assertCount(1, $messages);
        $this->assertEquals('New message', $messages[0]['message']);
    }

    public function test_recent_returns_all_messages_without_since_parameter()
    {
        GamingSessionMessage::factory()->count(3)->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id
        ]);

        $response = $this->actingAs($this->host)
            ->getJson(route('gaming-sessions.messages.recent', $this->session));

        $response->assertStatus(200);
        
        $messages = $response->json('messages');
        $this->assertCount(3, $messages);
    }

    public function test_recent_prevents_unauthorized_access()
    {
        $response = $this->actingAs($this->outsider)
            ->getJson(route('gaming-sessions.messages.recent', $this->session));

        $response->assertStatus(403);
    }

    public function test_recent_requires_authentication()
    {
        $response = $this->getJson(route('gaming-sessions.messages.recent', $this->session));

        $response->assertStatus(401);
    }

    public function test_public_session_allows_any_authenticated_user_to_view_messages()
    {
        $publicSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => GamingSession::PRIVACY_PUBLIC
        ]);

        GamingSessionMessage::factory()->create([
            'gaming_session_id' => $publicSession->id,
            'user_id' => $this->host->id,
            'message' => 'Public message'
        ]);

        $response = $this->actingAs($this->outsider)
            ->get(route('gaming-sessions.messages.index', $publicSession));

        $response->assertStatus(200);
        $response->assertSee('Public message');
    }

    public function test_public_session_allows_any_authenticated_user_to_post_messages()
    {
        $publicSession = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => GamingSession::PRIVACY_PUBLIC
        ]);

        $messageData = [
            'message' => 'Outsider message in public session'
        ];

        $response = $this->actingAs($this->outsider)
            ->postJson(route('gaming-sessions.messages.store', $publicSession), $messageData);

        $response->assertStatus(200);
        
        $this->assertDatabaseHas('gaming_session_messages', [
            'gaming_session_id' => $publicSession->id,
            'user_id' => $this->outsider->id,
            'message' => 'Outsider message in public session'
        ]);
    }

    public function test_message_notifications_are_sent_to_correct_users()
    {
        Notification::fake();

        // Add another participant
        $participant2 = User::factory()->create();
        GamingSessionParticipant::factory()->joined()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $participant2->id,
        ]);

        $messageData = [
            'message' => 'Hello everyone!'
        ];

        $this->actingAs($this->host)
            ->postJson(route('gaming-sessions.messages.store', $this->session), $messageData);

        // Should notify both participants but not the host
        Notification::assertSentTo($this->participant, GamingSessionMessageNotification::class);
        Notification::assertSentTo($participant2, GamingSessionMessageNotification::class);
        Notification::assertNotSentTo($this->host, GamingSessionMessageNotification::class);
    }

    public function test_message_editing_time_limit_enforcement()
    {
        // Create an old message (beyond edit time limit)
        $oldMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->host->id,
            'message' => 'Old message',
            'created_at' => now()->subMinutes(16) // Assuming 15 minute edit limit
        ]);

        $updateData = [
            'message' => 'Trying to edit old message'
        ];

        $response = $this->actingAs($this->host)
            ->putJson(route('gaming-sessions.messages.update', [$this->session, $oldMessage]), $updateData);

        $response->assertStatus(403);
    }
}
