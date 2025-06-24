<?php

namespace Tests\Unit;

use App\Models\GamingSession;
use App\Models\GamingSessionParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamingSessionParticipantTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_create_gaming_session_participant()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        $participant = GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $this->assertDatabaseHas('gaming_session_participants', [
            'id' => $participant->id,
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);
    }

    public function test_participant_belongs_to_gaming_session()
    {
        $session = GamingSession::factory()->create();
        $participant = GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
        ]);

        $this->assertEquals($session->id, $participant->gamingSession->id);
        $this->assertEquals($session->title, $participant->gamingSession->title);
    }

    public function test_participant_belongs_to_user()
    {
        $user = User::factory()->create();
        $participant = GamingSessionParticipant::factory()->create([
            'user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $participant->user->id);
        $this->assertEquals($user->name, $participant->user->name);
    }

    public function test_is_active_method_for_joined_participant()
    {
        $participant = GamingSessionParticipant::factory()->joined()->create();

        $this->assertTrue($participant->isActive());
    }

    public function test_is_active_method_for_left_participant()
    {
        $participant = GamingSessionParticipant::factory()->left()->create();

        $this->assertFalse($participant->isActive());
    }

    public function test_active_scope()
    {
        GamingSessionParticipant::factory()->joined()->count(3)->create();
        GamingSessionParticipant::factory()->left()->count(2)->create();

        $activeParticipants = GamingSessionParticipant::active()->get();

        $this->assertEquals(3, $activeParticipants->count());
        $this->assertTrue($activeParticipants->every(fn($participant) => $participant->isActive()));
    }

    public function test_for_session_scope()
    {
        $session1 = GamingSession::factory()->create();
        $session2 = GamingSession::factory()->create();

        GamingSessionParticipant::factory()->count(2)->create([
            'gaming_session_id' => $session1->id,
        ]);

        GamingSessionParticipant::factory()->count(1)->create([
            'gaming_session_id' => $session2->id,
        ]);

        $session1Participants = GamingSessionParticipant::forSession($session1)->get();

        $this->assertEquals(2, $session1Participants->count());
        $this->assertTrue($session1Participants->every(fn($participant) => $participant->gaming_session_id === $session1->id));
    }

    public function test_for_user_scope()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        GamingSessionParticipant::factory()->count(2)->create([
            'user_id' => $user1->id,
        ]);

        GamingSessionParticipant::factory()->count(1)->create([
            'user_id' => $user2->id,
        ]);

        $user1Participants = GamingSessionParticipant::forUser($user1)->get();

        $this->assertEquals(2, $user1Participants->count());
        $this->assertTrue($user1Participants->every(fn($participant) => $participant->user_id === $user1->id));
    }

    public function test_unique_constraint_prevents_duplicate_participants()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        // Create first participant record
        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ]);

        // Attempt to create duplicate should fail
        $this->expectException(\Illuminate\Database\QueryException::class);
        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_participant_can_join_session()
    {
        $session = GamingSession::factory()->create();
        $user = User::factory()->create();

        $participant = GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
            'joined_at' => now(),
        ]);

        $this->assertTrue($participant->isActive());
        $this->assertEquals(GamingSessionParticipant::STATUS_JOINED, $participant->status);
    }

    public function test_participant_can_leave_session()
    {
        $participant = GamingSessionParticipant::factory()->joined()->create();

        $participant->update(['status' => GamingSessionParticipant::STATUS_LEFT]);

        $this->assertFalse($participant->isActive());
        $this->assertEquals(GamingSessionParticipant::STATUS_LEFT, $participant->status);
    }

    public function test_participant_timestamps_are_recorded()
    {
        $participant = GamingSessionParticipant::factory()->create();

        $this->assertNotNull($participant->created_at);
        $this->assertNotNull($participant->updated_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $participant->created_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $participant->updated_at);
    }

    public function test_participant_fillable_attributes()
    {
        $data = [
            'gaming_session_id' => 1,
            'user_id' => 1,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ];

        $participant = new GamingSessionParticipant();
        $participant->fill($data);

        $this->assertEquals(1, $participant->gaming_session_id);
        $this->assertEquals(1, $participant->user_id);
        $this->assertEquals(GamingSessionParticipant::STATUS_JOINED, $participant->status);
    }

    public function test_status_constants_are_defined()
    {
        $this->assertEquals('joined', GamingSessionParticipant::STATUS_JOINED);
        $this->assertEquals('left', GamingSessionParticipant::STATUS_LEFT);
    }

    public function test_active_participants_for_specific_session()
    {
        $session = GamingSession::factory()->create();
        $otherSession = GamingSession::factory()->create();

        // Create active participants for our session
        GamingSessionParticipant::factory()->joined()->count(2)->create([
            'gaming_session_id' => $session->id,
        ]);

        // Create inactive participants for our session
        GamingSessionParticipant::factory()->left()->count(1)->create([
            'gaming_session_id' => $session->id,
        ]);

        // Create participants for other session
        GamingSessionParticipant::factory()->joined()->count(3)->create([
            'gaming_session_id' => $otherSession->id,
        ]);

        $activeForSession = GamingSessionParticipant::active()->forSession($session)->get();

        $this->assertEquals(2, $activeForSession->count());
        $this->assertTrue($activeForSession->every(fn($participant) => 
            $participant->gaming_session_id === $session->id && $participant->isActive()
        ));
    }

    public function test_user_active_participations()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Create active participations for our user
        GamingSessionParticipant::factory()->joined()->count(2)->create([
            'user_id' => $user->id,
        ]);

        // Create inactive participations for our user
        GamingSessionParticipant::factory()->left()->count(1)->create([
            'user_id' => $user->id,
        ]);

        // Create participations for other user
        GamingSessionParticipant::factory()->joined()->count(3)->create([
            'user_id' => $otherUser->id,
        ]);

        $userActiveParticipations = GamingSessionParticipant::active()->forUser($user)->get();

        $this->assertEquals(2, $userActiveParticipations->count());
        $this->assertTrue($userActiveParticipations->every(fn($participant) => 
            $participant->user_id === $user->id && $participant->isActive()
        ));
    }
}
