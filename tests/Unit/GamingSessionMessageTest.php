<?php

namespace Tests\Unit;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamingSessionMessageTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_gaming_session_message()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => 'Test message content',
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);

        $this->assertDatabaseHas('gaming_session_messages', [
            'id' => $message->id,
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => 'Test message content',
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);
    }

    public function test_message_belongs_to_gaming_session()
    {
        $session = GamingSession::factory()->create();
        $message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $session->id,
        ]);

        $this->assertEquals($session->id, $message->gamingSession->id);
        $this->assertEquals($session->title, $message->gamingSession->title);
    }

    public function test_message_belongs_to_user()
    {
        $user = User::factory()->create();
        $message = GamingSessionMessage::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $message->user->id);
        $this->assertEquals($user->name, $message->user->name);
    }

    public function test_message_has_fillable_attributes()
    {
        $data = [
            'gaming_session_id' => 1,
            'user_id' => 1,
            'message' => 'Test message',
            'type' => GamingSessionMessage::TYPE_TEXT,
            'metadata' => ['key' => 'value'],
        ];

        $message = new GamingSessionMessage();
        $message->fill($data);

        $this->assertEquals(1, $message->gaming_session_id);
        $this->assertEquals(1, $message->user_id);
        $this->assertEquals('Test message', $message->message);
        $this->assertEquals(GamingSessionMessage::TYPE_TEXT, $message->type);
        $this->assertEquals(['key' => 'value'], $message->metadata);
    }

    public function test_message_casts_attributes()
    {
        $message = GamingSessionMessage::factory()->create([
            'metadata' => ['test' => 'data'],
            'edited_at' => now(),
        ]);

        $this->assertIsArray($message->metadata);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->edited_at);
    }

    public function test_type_constants_are_defined()
    {
        $this->assertEquals('text', GamingSessionMessage::TYPE_TEXT);
        $this->assertEquals('system', GamingSessionMessage::TYPE_SYSTEM);
        $this->assertEquals('announcement', GamingSessionMessage::TYPE_ANNOUNCEMENT);
    }

    public function test_is_edited_method_returns_false_for_unedited_message()
    {
        $message = GamingSessionMessage::factory()->create([
            'edited_at' => null,
        ]);

        $this->assertFalse($message->isEdited());
    }

    public function test_is_edited_method_returns_true_for_edited_message()
    {
        $message = GamingSessionMessage::factory()->create([
            'edited_at' => now(),
        ]);

        $this->assertTrue($message->isEdited());
    }

    public function test_is_system_message_method_returns_true_for_system_message()
    {
        $message = GamingSessionMessage::factory()->create([
            'type' => GamingSessionMessage::TYPE_SYSTEM,
        ]);

        $this->assertTrue($message->isSystemMessage());
    }

    public function test_is_system_message_method_returns_false_for_non_system_message()
    {
        $message = GamingSessionMessage::factory()->create([
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);

        $this->assertFalse($message->isSystemMessage());
    }

    public function test_is_announcement_method_returns_true_for_announcement()
    {
        $message = GamingSessionMessage::factory()->create([
            'type' => GamingSessionMessage::TYPE_ANNOUNCEMENT,
        ]);

        $this->assertTrue($message->isAnnouncement());
    }

    public function test_is_announcement_method_returns_false_for_non_announcement()
    {
        $message = GamingSessionMessage::factory()->create([
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);

        $this->assertFalse($message->isAnnouncement());
    }

    public function test_mark_as_edited_method_sets_edited_at_timestamp()
    {
        $message = GamingSessionMessage::factory()->create([
            'edited_at' => null,
        ]);

        // Verify message is not initially edited
        $this->assertFalse($message->isEdited());
        $this->assertNull($message->edited_at);

        // Record time before marking as edited
        $beforeEditing = now()->subSecond();

        // Mark as edited
        $message->markAsEdited();

        // Refresh to get updated data
        $message->refresh();

        // Verify edited_at timestamp was set
        $this->assertTrue($message->isEdited());
        $this->assertNotNull($message->edited_at);
        $this->assertGreaterThan($beforeEditing, $message->edited_at);
        $this->assertLessThanOrEqual(now(), $message->edited_at);
    }

    public function test_mark_as_edited_method_persists_changes_to_database()
    {
        $message = GamingSessionMessage::factory()->create([
            'edited_at' => null,
        ]);

        $message->markAsEdited();

        // Verify changes were persisted to database
        $freshMessage = GamingSessionMessage::find($message->id);
        $this->assertNotNull($freshMessage->edited_at);
        $this->assertTrue($freshMessage->isEdited());
    }

    public function test_mark_as_edited_method_can_be_called_multiple_times()
    {
        $message = GamingSessionMessage::factory()->create([
            'edited_at' => null,
        ]);

        // Mark as edited first time
        $message->markAsEdited();
        $message->refresh();
        $firstEditTime = $message->edited_at;

        // Wait a moment and mark as edited again
        sleep(1);
        $message->markAsEdited();
        $message->refresh();
        $secondEditTime = $message->edited_at;

        // Verify timestamp was updated
        $this->assertGreaterThan($firstEditTime, $secondEditTime);
    }

    public function test_for_session_scope()
    {
        $session1 = GamingSession::factory()->create();
        $session2 = GamingSession::factory()->create();

        // Create messages for session1
        GamingSessionMessage::factory()->count(3)->create([
            'gaming_session_id' => $session1->id,
        ]);

        // Create messages for session2
        GamingSessionMessage::factory()->count(2)->create([
            'gaming_session_id' => $session2->id,
        ]);

        $session1Messages = GamingSessionMessage::forSession($session1)->get();

        $this->assertEquals(3, $session1Messages->count());
        $this->assertTrue($session1Messages->every(fn($message) => $message->gaming_session_id === $session1->id));
    }

    public function test_recent_scope_limits_results()
    {
        // Create 10 messages
        GamingSessionMessage::factory()->count(10)->create();

        $recentMessages = GamingSessionMessage::recent(5)->get();

        $this->assertEquals(5, $recentMessages->count());
    }

    public function test_recent_scope_orders_by_created_at_desc()
    {
        // Create messages with specific timestamps
        $oldMessage = GamingSessionMessage::factory()->create([
            'created_at' => now()->subDays(2),
        ]);
        $middleMessage = GamingSessionMessage::factory()->create([
            'created_at' => now()->subDay(),
        ]);
        $newMessage = GamingSessionMessage::factory()->create([
            'created_at' => now(),
        ]);

        $recentMessages = GamingSessionMessage::recent()->get();

        // Verify newest message is first
        $this->assertEquals($newMessage->id, $recentMessages->first()->id);
        $this->assertEquals($oldMessage->id, $recentMessages->last()->id);
    }

    public function test_recent_scope_default_limit()
    {
        // Create more than 50 messages
        GamingSessionMessage::factory()->count(60)->create();

        $recentMessages = GamingSessionMessage::recent()->get();

        // Should default to 50
        $this->assertEquals(50, $recentMessages->count());
    }

    public function test_by_type_scope_filters_correctly()
    {
        // Create different types of messages
        GamingSessionMessage::factory()->count(3)->create([
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);
        GamingSessionMessage::factory()->count(2)->create([
            'type' => GamingSessionMessage::TYPE_SYSTEM,
        ]);
        GamingSessionMessage::factory()->count(1)->create([
            'type' => GamingSessionMessage::TYPE_ANNOUNCEMENT,
        ]);

        $textMessages = GamingSessionMessage::byType(GamingSessionMessage::TYPE_TEXT)->get();
        $systemMessages = GamingSessionMessage::byType(GamingSessionMessage::TYPE_SYSTEM)->get();
        $announcementMessages = GamingSessionMessage::byType(GamingSessionMessage::TYPE_ANNOUNCEMENT)->get();

        $this->assertEquals(3, $textMessages->count());
        $this->assertEquals(2, $systemMessages->count());
        $this->assertEquals(1, $announcementMessages->count());

        $this->assertTrue($textMessages->every(fn($message) => $message->type === GamingSessionMessage::TYPE_TEXT));
        $this->assertTrue($systemMessages->every(fn($message) => $message->type === GamingSessionMessage::TYPE_SYSTEM));
        $this->assertTrue($announcementMessages->every(fn($message) => $message->type === GamingSessionMessage::TYPE_ANNOUNCEMENT));
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $session = GamingSession::factory()->create();

        // Create text messages for session
        GamingSessionMessage::factory()->count(3)->create([
            'gaming_session_id' => $session->id,
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);

        // Create system messages for session
        GamingSessionMessage::factory()->count(2)->create([
            'gaming_session_id' => $session->id,
            'type' => GamingSessionMessage::TYPE_SYSTEM,
        ]);

        // Create messages for other session
        GamingSessionMessage::factory()->count(5)->create([
            'type' => GamingSessionMessage::TYPE_TEXT,
        ]);

        $sessionTextMessages = GamingSessionMessage::forSession($session)
            ->byType(GamingSessionMessage::TYPE_TEXT)
            ->get();

        $this->assertEquals(3, $sessionTextMessages->count());
        $this->assertTrue($sessionTextMessages->every(fn($message) => 
            $message->gaming_session_id === $session->id && 
            $message->type === GamingSessionMessage::TYPE_TEXT
        ));
    }

    public function test_message_timestamps_are_recorded()
    {
        $message = GamingSessionMessage::factory()->create();

        $this->assertNotNull($message->created_at);
        $this->assertNotNull($message->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $message->updated_at);
    }

    public function test_message_can_be_created_with_metadata()
    {
        $metadata = [
            'attachments' => ['file1.png', 'file2.pdf'],
            'reactions' => ['👍' => 5, '😀' => 2],
            'thread_id' => 'thread-123',
        ];

        $message = GamingSessionMessage::factory()->create([
            'metadata' => $metadata,
        ]);

        $this->assertEquals($metadata, $message->metadata);
        $this->assertEquals(['file1.png', 'file2.pdf'], $message->metadata['attachments']);
        $this->assertEquals(5, $message->metadata['reactions']['👍']);
    }

    public function test_message_can_be_created_without_metadata()
    {
        $message = GamingSessionMessage::factory()->create([
            'metadata' => null,
        ]);

        $this->assertNull($message->metadata);
    }

    public function test_message_factory_creates_valid_message()
    {
        $message = GamingSessionMessage::factory()->create();

        $this->assertNotNull($message->gaming_session_id);
        $this->assertNotNull($message->user_id);
        $this->assertNotNull($message->message);
        $this->assertContains($message->type, [
            GamingSessionMessage::TYPE_TEXT,
            GamingSessionMessage::TYPE_SYSTEM,
            GamingSessionMessage::TYPE_ANNOUNCEMENT,
        ]);
    }

    public function test_message_can_be_created_with_complete_data()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        $message = GamingSessionMessage::create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => 'Complete test message',
            'type' => GamingSessionMessage::TYPE_ANNOUNCEMENT,
            'metadata' => ['priority' => 'high'],
            'edited_at' => now(),
        ]);

        $this->assertEquals($session->id, $message->gaming_session_id);
        $this->assertEquals($user->id, $message->user_id);
        $this->assertEquals('Complete test message', $message->message);
        $this->assertEquals(GamingSessionMessage::TYPE_ANNOUNCEMENT, $message->type);
        $this->assertEquals(['priority' => 'high'], $message->metadata);
        $this->assertTrue($message->isEdited());
        $this->assertTrue($message->isAnnouncement());
    }
}
