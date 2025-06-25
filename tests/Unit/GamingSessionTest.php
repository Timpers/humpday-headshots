<?php

namespace Tests\Unit;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\GamingSessionParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GamingSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_gaming_session_belongs_to_host()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create(['host_user_id' => $host->id]);

        $this->assertEquals($host->id, $session->host->id);
        $this->assertEquals($host->name, $session->host->name);
    }

    public function test_gaming_session_has_fillable_attributes()
    {
        $fillable = [
            'host_user_id',
            'title',
            'description',
            'game_name',
            'game_data',
            'platform',
            'scheduled_at',
            'max_participants',
            'status',
            'privacy',
            'requirements',
        ];

        $session = new GamingSession();
        $this->assertEquals($fillable, $session->getFillable());
    }

    public function test_gaming_session_casts_attributes()
    {
        $session = GamingSession::factory()->create([
            'game_data' => ['title' => 'Test Game'],
            'scheduled_at' => now(),
            'privacy' => GamingSession::PRIVACY_PUBLIC,
            'max_participants' => 10,
            'status' => GamingSession::STATUS_SCHEDULED,
        ]);

        $this->assertIsArray($session->game_data);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $session->scheduled_at);
        $this->assertIsInt($session->max_participants);
        $this->assertIsString($session->privacy);
        $this->assertIsString($session->status);
    }
    public function test_gaming_session_can_check_if_past()
    {
        $pastSession = GamingSession::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);

        $futureSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addHour(),
        ]);

        $this->assertTrue($pastSession->isPast());
        $this->assertFalse($futureSession->isPast());
    }

    public function test_gaming_session_can_check_if_upcoming()
    {
        $pastSession = GamingSession::factory()->create([
            'scheduled_at' => now()->subHour(),
        ]);

        $futureSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addHour(),
        ]);

        $this->assertFalse($pastSession->isUpcoming());
        $this->assertTrue($futureSession->isUpcoming());
    }

    public function test_gaming_session_can_check_if_soon()
    {
        $soonSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addMinutes(30),
        ]);

        $laterSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addHours(2),
        ]);

        $this->assertTrue($soonSession->isSoon());
        $this->assertFalse($laterSession->isSoon());
    }

    public function test_host_can_always_join_their_own_session()
    {
        $host = User::factory()->create();
        $session = GamingSession::factory()->create([
            'host_user_id' => $host->id,
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY,
            'max_participants' => 1,
        ]);

        $this->assertTrue($session->canUserJoin($host));
    }

    public function test_user_cannot_join_cancelled_session()
    {
        $user = User::factory()->create();
        $session = GamingSession::factory()->create([
            'status' => GamingSession::STATUS_CANCELLED,
            'privacy' => GamingSession::PRIVACY_PUBLIC,
        ]);

        $this->assertFalse($session->canUserJoin($user));
    }

    public function test_user_cannot_join_past_session()
    {
        $user = User::factory()->create();
        $session = GamingSession::factory()->create([
            'scheduled_at' => now()->subHour(),
            'privacy' => GamingSession::PRIVACY_PUBLIC,
        ]);

        $this->assertFalse($session->canUserJoin($user));
    }

    public function test_user_can_join_invite_only_session_with_accepted_invitation()
    {
        $user = User::factory()->create();
        $session = GamingSession::factory()->create([
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY,
        ]);

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $user->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $this->assertTrue($session->canUserJoin($user));
    }

    public function test_user_cannot_join_invite_only_session_without_invitation()
    {
        $user = User::factory()->create();
        $session = GamingSession::factory()->create([
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY,
        ]);

        $this->assertFalse($session->canUserJoin($user));
    }

    public function test_gaming_session_scope_for_user()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $hostedSession = GamingSession::factory()->create(['host_user_id' => $user->id]);
        $participatedSession = GamingSession::factory()->create(['host_user_id' => $otherUser->id]);
        $unrelatedSession = GamingSession::factory()->create(['host_user_id' => $otherUser->id]);

        GamingSessionParticipant::factory()->create([
            'gaming_session_id' => $participatedSession->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $userSessions = GamingSession::forUser($user)->get();

        $this->assertEquals(2, $userSessions->count());
        $this->assertTrue($userSessions->contains('id', $hostedSession->id));
        $this->assertTrue($userSessions->contains('id', $participatedSession->id));
        $this->assertFalse($userSessions->contains('id', $unrelatedSession->id));
    }

    public function test_gaming_session_scope_public()
    {
        GamingSession::factory()->create(['privacy' => GamingSession::PRIVACY_PUBLIC]);
        GamingSession::factory()->create(['privacy' => GamingSession::PRIVACY_FRIENDS_ONLY]);
        GamingSession::factory()->create(['privacy' => GamingSession::PRIVACY_INVITE_ONLY]);

        $publicSessions = GamingSession::public()->get();

        $this->assertEquals(1, $publicSessions->count());
        $this->assertEquals(GamingSession::PRIVACY_PUBLIC, $publicSessions->first()->privacy);
    }

    public function test_gaming_session_game_cover_url_attribute()
    {
        $sessionWithCover = GamingSession::factory()->create([
            'game_data' => [
                'cover' => [
                    'url' => '//example.com/cover.jpg'
                ]
            ]
        ]);

        $sessionWithoutCover = GamingSession::factory()->create([
            'game_data' => null
        ]);

        $this->assertEquals('https://example.com/cover.jpg', $sessionWithCover->game_cover_url);
        $this->assertNull($sessionWithoutCover->game_cover_url);
    }

    public function test_gaming_session_pending_invitations_relationship()
    {
        $session = GamingSession::factory()->create();

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $this->assertEquals(1, $session->pendingInvitations->count());
    }

    public function test_gaming_session_accepted_invitations_relationship()
    {
        $session = GamingSession::factory()->create();

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $this->assertEquals(1, $session->acceptedInvitations->count());
    }

    public function test_gaming_session_has_many_invitations()
    {
        $session = GamingSession::factory()->create();
        $invitations = GamingSessionInvitation::factory()->count(3)->create([
            'gaming_session_id' => $session->id,
        ]);

        $this->assertEquals(3, $session->invitations->count());
        $this->assertEquals($invitations->pluck('id')->sort()->values(), $session->invitations->pluck('id')->sort()->values());
    }

    public function test_gaming_session_has_many_participants()
    {
        $session = GamingSession::factory()->create();
        $participants = GamingSessionParticipant::factory()->count(2)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $this->assertEquals(2, $session->participants->count());
        $this->assertEquals($participants->pluck('id')->sort()->values(), $session->participants->pluck('id')->sort()->values());
    }

    public function test_gaming_session_active_participants_scope()
    {
        $session = GamingSession::factory()->create();

        // Create joined participants
        GamingSessionParticipant::factory()->count(2)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        // Create left participants
        GamingSessionParticipant::factory()->count(1)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_LEFT,
        ]);

        $this->assertEquals(2, $session->activeParticipants->count());
    }

    public function test_gaming_session_is_full_when_at_max_participants()
    {
        $session = GamingSession::factory()->create(['max_participants' => 2]);

        GamingSessionParticipant::factory()->count(2)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $this->assertTrue($session->isFull());
    }

    public function test_gaming_session_is_not_full_when_below_max_participants()
    {
        $session = GamingSession::factory()->create(['max_participants' => 3]);

        GamingSessionParticipant::factory()->count(2)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $this->assertFalse($session->isFull());
    }

    public function test_gaming_session_is_not_full_when_max_participants_is_large()
    {
        $session = GamingSession::factory()->create(['max_participants' => 100]);

        GamingSessionParticipant::factory()->count(10)->create([
            'gaming_session_id' => $session->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);

        $this->assertFalse($session->isFull());
    }

    public function test_user_can_join_gaming_session()
    {
        $session = GamingSession::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        $result = $session->addParticipant($user);

        $this->assertTrue($result);
        $this->assertDatabaseHas('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);
    }

    public function test_user_cannot_join_full_gaming_session()
    {
        $session = GamingSession::factory()->create(['max_participants' => 1]);
        $existingUser = User::factory()->create();
        $newUser = User::factory()->create();

        // Fill the session
        $session->addParticipant($existingUser);

        $result = $session->addParticipant($newUser);

        $this->assertFalse($result);
        $this->assertDatabaseMissing('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $newUser->id,
        ]);
    }

    public function test_user_cannot_join_same_session_twice()
    {
        $session = GamingSession::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        // Join first time
        $result1 = $session->addParticipant($user);
        // Try to join again
        $result2 = $session->addParticipant($user);

        $this->assertTrue($result1);
        $this->assertFalse($result2);

        // Should only have one participant record
        $this->assertEquals(1, GamingSessionParticipant::where([
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
        ])->count());
    }

    public function test_user_can_leave_gaming_session()
    {
        $session = GamingSession::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        // First join
        $session->addParticipant($user);

        // Then leave
        $result = $session->removeParticipant($user);

        $this->assertTrue($result);
        $this->assertDatabaseHas('gaming_session_participants', [
            'gaming_session_id' => $session->id,
            'user_id' => $user->id,
            'status' => GamingSessionParticipant::STATUS_LEFT,
        ]);
    }

    public function test_user_cannot_leave_session_they_havent_joined()
    {
        $session = GamingSession::factory()->create(['privacy' => 'public']);
        $user = User::factory()->create();

        $result = $session->removeParticipant($user);

        $this->assertFalse($result);
    }

    public function test_check_if_user_is_participant()
    {
        $session = GamingSession::factory()->create(['privacy' => 'public']);
        $participantUser = User::factory()->create();
        $nonParticipantUser = User::factory()->create();

        $session->addParticipant($participantUser);

        $this->assertTrue($session->isParticipant($participantUser));
        $this->assertFalse($session->isParticipant($nonParticipantUser));
    }

    public function test_check_if_user_has_pending_invitation()
    {
        $session = GamingSession::factory()->create();
        $invitedUser = User::factory()->create();
        $notInvitedUser = User::factory()->create();

        GamingSessionInvitation::factory()->create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $invitedUser->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $this->assertTrue($session->hasPendingInvitation($invitedUser));
        $this->assertFalse($session->hasPendingInvitation($notInvitedUser));
    }

    public function test_gaming_session_scheduled_date_is_cast_to_carbon()
    {
        $session = GamingSession::factory()->create([
            'scheduled_at' => '2024-12-25 15:30:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $session->scheduled_at);
        $this->assertEquals('2024-12-25 15:30:00', $session->scheduled_at->format('Y-m-d H:i:s'));
    }

    public function test_gaming_session_scope_upcoming()
    {
        // Create past session
        GamingSession::factory()->create([
            'scheduled_at' => now()->subDay(),
        ]);

        // Create future session
        $futureSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addDay(),
        ]);

        $upcomingSessions = GamingSession::upcoming()->get();

        $this->assertEquals(1, $upcomingSessions->count());
        $this->assertEquals($futureSession->id, $upcomingSessions->first()->id);
    }

    public function test_gaming_session_fillable_attributes()
    {
        $data = [
            'title' => 'Test Session',
            'description' => 'Test Description',
            'game_name' => 'Test Game',
            'scheduled_at' => now()->addDay(),
            'max_participants' => 4,
        ];

        $session = new GamingSession();
        $session->fill($data);

        $this->assertEquals('Test Session', $session->title);
        $this->assertEquals('Test Description', $session->description);
        $this->assertEquals('Test Game', $session->game_name);
        $this->assertEquals(4, $session->max_participants);
    }
}
