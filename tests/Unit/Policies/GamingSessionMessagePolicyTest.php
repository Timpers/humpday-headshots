<?php

namespace Tests\Unit\Policies;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use App\Policies\GamingSessionMessagePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GamingSessionMessagePolicyTest extends TestCase
{
    use RefreshDatabase;

    private GamingSessionMessagePolicy $policy;
    private User $host;
    private User $messageAuthor;
    private User $otherUser;
    private GamingSession $session;
    private GamingSessionMessage $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->policy = new GamingSessionMessagePolicy();
        $this->host = User::factory()->create();
        $this->messageAuthor = User::factory()->create();
        $this->otherUser = User::factory()->create();

        $this->session = GamingSession::factory()->create([
            'host_user_id' => $this->host->id,
            'privacy' => 'public'
        ]);

        $this->message = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->messageAuthor->id,
            'message' => 'Test message content',
            'created_at' => now()->subMinutes(5)
        ]);
    }

    public function test_view_any_allows_all_authenticated_users()
    {
        $user = User::factory()->create();
        
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_view_delegates_to_session_view_messages_policy()
    {
        // For public session, anyone can view messages
        $this->assertTrue($this->policy->view($this->otherUser, $this->message));

        // For private session, only authorized users can view
        $this->session->update(['privacy' => 'invite_only']);
        $this->session->refresh();
        $this->message->load('gamingSession'); // Reload the relationship

        // Host can view
        $this->assertTrue($this->policy->view($this->host, $this->message));
        
        // Random user cannot view private session messages
        $this->assertFalse($this->policy->view($this->otherUser, $this->message));
    }

    public function test_create_allows_all_authenticated_users()
    {
        $user = User::factory()->create();
        
        $this->assertTrue($this->policy->create($user));
    }

    public function test_update_allows_author_within_time_limit()
    {
        // Message created 5 minutes ago - should be editable
        $this->assertTrue($this->policy->update($this->messageAuthor, $this->message));
    }

    public function test_update_denies_author_after_time_limit()
    {
        // Update message to be created 20 minutes ago using DB query to bypass fillable restrictions
        DB::table('gaming_session_messages')
            ->where('id', $this->message->id)
            ->update(['created_at' => now()->subMinutes(20)]);
        $this->message->refresh();

        $this->assertFalse($this->policy->update($this->messageAuthor, $this->message));
    }

    public function test_update_denies_non_author()
    {
        $this->assertFalse($this->policy->update($this->otherUser, $this->message));
        $this->assertFalse($this->policy->update($this->host, $this->message));
    }

    public function test_update_handles_edge_case_at_15_minute_mark()
    {
        // Message created exactly 15 minutes ago - use a specific timestamp to avoid timing issues
        $baseTime = now();
        $exactly15MinutesAgo = $baseTime->copy()->subMinutes(15);
        
        DB::table('gaming_session_messages')
            ->where('id', $this->message->id)
            ->update(['created_at' => $exactly15MinutesAgo]);
        $this->message->refresh();

        $this->assertTrue($this->policy->update($this->messageAuthor, $this->message));

        // Message created 16 minutes ago (clearly over the limit)
        $over15MinutesAgo = $baseTime->copy()->subMinutes(16);
        
        DB::table('gaming_session_messages')
            ->where('id', $this->message->id)
            ->update(['created_at' => $over15MinutesAgo]);
        $this->message->refresh();

        $this->assertFalse($this->policy->update($this->messageAuthor, $this->message));
    }

    public function test_delete_allows_message_author()
    {
        $this->assertTrue($this->policy->delete($this->messageAuthor, $this->message));
    }

    public function test_delete_allows_session_host()
    {
        $this->assertTrue($this->policy->delete($this->host, $this->message));
    }

    public function test_delete_denies_other_users()
    {
        $this->assertFalse($this->policy->delete($this->otherUser, $this->message));
    }

    public function test_restore_same_as_delete_permissions()
    {
        // Author can restore
        $this->assertTrue($this->policy->restore($this->messageAuthor, $this->message));
        
        // Host can restore
        $this->assertTrue($this->policy->restore($this->host, $this->message));
        
        // Other users cannot restore
        $this->assertFalse($this->policy->restore($this->otherUser, $this->message));
    }

    public function test_force_delete_allows_only_session_host()
    {
        $this->assertTrue($this->policy->forceDelete($this->host, $this->message));
        $this->assertFalse($this->policy->forceDelete($this->messageAuthor, $this->message));
        $this->assertFalse($this->policy->forceDelete($this->otherUser, $this->message));
    }

    public function test_host_can_delete_any_message_in_their_session()
    {
        $anotherUserMessage = GamingSessionMessage::factory()->create([
            'gaming_session_id' => $this->session->id,
            'user_id' => $this->otherUser->id,
            'message' => 'Another user message'
        ]);

        $this->assertTrue($this->policy->delete($this->host, $anotherUserMessage));
        $this->assertTrue($this->policy->forceDelete($this->host, $anotherUserMessage));
    }

    public function test_author_cannot_force_delete_their_own_message()
    {
        $this->assertFalse($this->policy->forceDelete($this->messageAuthor, $this->message));
    }

    public function test_time_based_update_policy_with_different_scenarios()
    {
        $scenarios = [
            ['minutes' => 1, 'expected' => true],   // 1 minute ago
            ['minutes' => 10, 'expected' => true],  // 10 minutes ago
            ['minutes' => 14, 'expected' => true],  // 14 minutes ago
            ['minutes' => 15, 'expected' => true],  // 15 minutes ago (edge case)
            ['minutes' => 16, 'expected' => false], // 16 minutes ago
            ['minutes' => 30, 'expected' => false], // 30 minutes ago
        ];

        foreach ($scenarios as $scenario) {
            $testMessage = GamingSessionMessage::factory()->create([
                'gaming_session_id' => $this->session->id,
                'user_id' => $this->messageAuthor->id,
                'created_at' => now()->subMinutes($scenario['minutes'])
            ]);

            $result = $this->policy->update($this->messageAuthor, $testMessage);
            
            $this->assertEquals(
                $scenario['expected'], 
                $result, 
                "Failed for {$scenario['minutes']} minutes ago. Expected " . 
                ($scenario['expected'] ? 'true' : 'false') . " but got " . 
                ($result ? 'true' : 'false')
            );
        }
    }
}
