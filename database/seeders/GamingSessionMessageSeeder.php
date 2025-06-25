<?php

namespace Database\Seeders;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GamingSessionMessageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get some users and gaming sessions
        $users = User::limit(3)->get();
        $sessions = GamingSession::limit(2)->get();

        if ($users->isEmpty() || $sessions->isEmpty()) {
            $this->command->info('Please ensure you have users and gaming sessions in your database first.');
            return;
        }

        $sampleMessages = [
            "Hey everyone! Looking forward to this session!",
            "What time zone are we playing in again?",
            "I'll bring my best loadout for this one 🎮",
            "Should we use voice chat or stick to text?",
            "This is going to be epic! Can't wait!",
            "Any tips for a first-timer in this game?",
            "I'll be online 15 minutes early to test my setup",
            "Let me know if anyone wants to practice beforehand",
            "Perfect timing - just finished downloading the update",
            "Who's bringing the snacks? (kidding 😄)"
        ];

        foreach ($sessions as $session) {
            // Add 3-5 messages per session
            $messageCount = rand(3, 5);
            
            for ($i = 0; $i < $messageCount; $i++) {
                GamingSessionMessage::create([
                    'gaming_session_id' => $session->id,
                    'user_id' => $users->random()->id,
                    'message' => $sampleMessages[array_rand($sampleMessages)],
                    'type' => 'text',
                    'created_at' => now()->subMinutes(rand(5, 120)),
                    'updated_at' => now()->subMinutes(rand(5, 120)),
                ]);
            }

            $this->command->info("Added {$messageCount} messages to session: {$session->title}");
        }
    }
}
