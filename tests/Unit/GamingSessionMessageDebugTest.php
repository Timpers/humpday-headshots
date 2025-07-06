<?php

namespace Tests\Unit;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GamingSessionMessageDebugTest extends TestCase
{
    use RefreshDatabase;

    public function test_direct_database_insert()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        // Try direct insert with DB facade
        DB::table('gaming_session_messages')->insert([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => 'Test message',
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('gaming_session_messages', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'message' => 'Test message',
            'type' => 'text',
        ]);
    }
}
