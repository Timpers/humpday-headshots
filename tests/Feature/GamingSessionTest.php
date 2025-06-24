<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GamingSession;
use App\Models\GamingSessionParticipant;
use App\Models\GamingSessionInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Carbon\Carbon;

class GamingSessionTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $host;
    protected User $participant;
    protected User $invitedUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create users for testing
        $this->host = User::factory()->create(['name' => 'Host User']);
        $this->participant = User::factory()->create(['name' => 'Participant User']);
        $this->invitedUser = User::factory()->create(['name' => 'Invited User']);
    }

    /** @test */
    public function it_creates_a_gaming_session_with_valid_data()
    {
        $sessionData = [
            'host_user_id' => $this->host->id,
            'title' => 'Epic Raid Night',
            'description' => 'Join us for an epic raid!',
            'game_name' => 'World of Warcraft',
            'platform' => 'pc',
            'scheduled_at' => now()->addHours(2),
            'max_participants' => 8,
            'privacy' => 'public',
            'requirements' => 'Level 60+ required',
        ];

        $session = GamingSession::create($sessionData);

        $this->assertInstanceOf(GamingSession::class, $session);
        $this->assertEquals($sessionData['title'], $session->title);
        $this->assertEquals($sessionData['game_name'], $session->game_name);
        $this->assertEquals(GamingSession::STATUS_SCHEDULED, $session->status);
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $session = GamingSession::factory()->create(['host_user_id' => $this->host->id]);

        // Test host relationship
        $this->assertInstanceOf(User::class, $session->host);
        $this->assertEquals($this->host->id, $session->host->id);

        // Create participants
        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->participant->id,
            'joined_at' => now(),
        ]);

        // Test participants relationship
        $this->assertCount(1, $session->participants);
        $this->assertInstanceOf(GamingSessionParticipant::class, $session->participants->first());

        // Test participantUsers relationship
        $this->assertCount(1, $session->participantUsers);
        $this->assertEquals($this->participant->id, $session->participantUsers->first()->id);

        // Create invitation
        GamingSessionInvitation::create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        // Test invitations relationship
        $this->assertCount(1, $session->invitations);
        $this->assertInstanceOf(GamingSessionInvitation::class, $session->invitations->first());
    }

    /** @test */
    public function it_has_status_constants()
    {
        $this->assertEquals('scheduled', GamingSession::STATUS_SCHEDULED);
        $this->assertEquals('active', GamingSession::STATUS_ACTIVE);
        $this->assertEquals('completed', GamingSession::STATUS_COMPLETED);
        $this->assertEquals('cancelled', GamingSession::STATUS_CANCELLED);
    }

    /** @test */
    public function it_has_privacy_constants()
    {
        $this->assertEquals('public', GamingSession::PRIVACY_PUBLIC);
        $this->assertEquals('friends_only', GamingSession::PRIVACY_FRIENDS_ONLY);
        $this->assertEquals('invite_only', GamingSession::PRIVACY_INVITE_ONLY);
    }

    /** @test */
    public function it_scopes_upcoming_sessions()
    {
        // Create past session
        GamingSession::factory()->create([
            'scheduled_at' => now()->subHours(2),
            'status' => GamingSession::STATUS_COMPLETED
        ]);

        // Create future session
        $futureSession = GamingSession::factory()->create([
            'scheduled_at' => now()->addHours(2),
            'status' => GamingSession::STATUS_SCHEDULED
        ]);

        $upcomingSessions = GamingSession::upcoming()->get();

        $this->assertCount(1, $upcomingSessions);
        $this->assertEquals($futureSession->id, $upcomingSessions->first()->id);
    }

    /** @test */
    public function it_scopes_public_sessions()
    {
        GamingSession::factory()->create(['privacy' => GamingSession::PRIVACY_INVITE_ONLY]);
        $publicSession = GamingSession::factory()->create(['privacy' => GamingSession::PRIVACY_PUBLIC]);

        $publicSessions = GamingSession::public()->get();

        $this->assertCount(1, $publicSessions);
        $this->assertEquals($publicSession->id, $publicSessions->first()->id);
    }

    /** @test */
    public function it_scopes_sessions_for_user()
    {
        // Session hosted by user
        $hostedSession = GamingSession::factory()->create(['host_user_id' => $this->host->id]);

        // Session where user participates
        $participatingSession = GamingSession::factory()->create();
        GamingSessionParticipant::create([
            'gaming_session_id' => $participatingSession->id,
            'user_id' => $this->host->id,
            'joined_at' => now(),
        ]);

        // Session where user is invited
        $invitedSession = GamingSession::factory()->create();
        GamingSessionInvitation::create([
            'gaming_session_id' => $invitedSession->id,
            'invited_user_id' => $this->host->id,
            'invited_by_user_id' => $this->participant->id,
        ]);

        // Unrelated session
        GamingSession::factory()->create();

        $userSessions = GamingSession::forUser($this->host)->get();

        $this->assertCount(3, $userSessions);
        $sessionIds = $userSessions->pluck('id')->toArray();
        $this->assertContains($hostedSession->id, $sessionIds);
        $this->assertContains($participatingSession->id, $sessionIds);
        $this->assertContains($invitedSession->id, $sessionIds);
    }

    /** @test */
    public function it_checks_if_session_is_full()
    {
        $session = GamingSession::factory()->create(['max_participants' => 2]);

        $this->assertFalse($session->isFull());

        // Add two participants
        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->host->id,
            'joined_at' => now(),
        ]);

        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->participant->id,
            'joined_at' => now(),
        ]);

        $session->refresh();
        $this->assertTrue($session->isFull());
    }

    /** @test */
    public function it_gets_available_spots()
    {
        $session = GamingSession::factory()->create(['max_participants' => 5]);

        $this->assertEquals(5, $session->getAvailableSpots());

        // Add two participants
        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->host->id,
            'joined_at' => now(),
        ]);

        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->participant->id,
            'joined_at' => now(),
        ]);

        $session->refresh();
        $this->assertEquals(3, $session->getAvailableSpots());
    }

    /** @test */
    public function it_checks_if_user_can_join()
    {
        $session = GamingSession::factory()->create([
            'max_participants' => 2,
            'privacy' => GamingSession::PRIVACY_PUBLIC,
            'scheduled_at' => now()->addHours(2),
            'status' => GamingSession::STATUS_SCHEDULED
        ]);

        // User can join public session with available spots
        $this->assertTrue($session->canUserJoin($this->participant));

        // User cannot join if already a participant
        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->participant->id,
            'joined_at' => now(),
        ]);

        $session->refresh();
        $this->assertFalse($session->canUserJoin($this->participant));

        // New user cannot join if session is full
        GamingSessionParticipant::create([
            'gaming_session_id' => $session->id,
            'user_id' => $this->host->id,
            'joined_at' => now(),
        ]);

        $session->refresh();
        $this->assertFalse($session->canUserJoin($this->invitedUser));
    }

    /** @test */
    public function it_checks_if_user_can_join_private_session()
    {
        $session = GamingSession::factory()->create([
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY,
            'scheduled_at' => now()->addHours(2),
            'status' => GamingSession::STATUS_SCHEDULED
        ]);

        // User cannot join invite-only session without invitation
        $this->assertFalse($session->canUserJoin($this->participant));

        // User can join with invitation
        GamingSessionInvitation::create([
            'gaming_session_id' => $session->id,
            'invited_user_id' => $this->participant->id,
            'invited_by_user_id' => $this->host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $session->refresh();
        $this->assertTrue($session->canUserJoin($this->participant));
    }

    /** @test */
    public function it_formats_scheduled_date()
    {
        $scheduledAt = Carbon::create(2025, 6, 25, 15, 30, 0);
        $session = GamingSession::factory()->create(['scheduled_at' => $scheduledAt]);

        $this->assertEquals('Jun 25, 2025 3:30 PM', $session->getFormattedScheduledAtAttribute());
    }

    /** @test */
    public function it_gets_status_badge_color()
    {
        $session = GamingSession::factory()->create(['status' => GamingSession::STATUS_SCHEDULED]);
        $this->assertEquals('blue', $session->getStatusBadgeColorAttribute());

        $session = GamingSession::factory()->create(['status' => GamingSession::STATUS_ACTIVE]);
        $this->assertEquals('green', $session->getStatusBadgeColorAttribute());

        $session = GamingSession::factory()->create(['status' => GamingSession::STATUS_COMPLETED]);
        $this->assertEquals('gray', $session->getStatusBadgeColorAttribute());

        $session = GamingSession::factory()->create(['status' => GamingSession::STATUS_CANCELLED]);
        $this->assertEquals('red', $session->getStatusBadgeColorAttribute());
    }

    /** @test */
    public function it_cannot_join_past_session()
    {
        $session = GamingSession::factory()->create([
            'scheduled_at' => now()->subHours(2),
            'status' => GamingSession::STATUS_COMPLETED
        ]);

        $this->assertFalse($session->canUserJoin($this->participant));
    }

    /** @test */
    public function it_cannot_join_cancelled_session()
    {
        $session = GamingSession::factory()->create([
            'status' => GamingSession::STATUS_CANCELLED,
            'scheduled_at' => now()->addHours(2)
        ]);

        $this->assertFalse($session->canUserJoin($this->participant));
    }
}
