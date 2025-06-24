<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\GamingSessionParticipant;
use App\Models\Group;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GamingSessionInvitationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected User $host;
    protected User $invitedUser;
    protected GamingSession $gamingSession;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->host = User::factory()->create(['name' => 'Host User']);
        $this->invitedUser = User::factory()->create(['name' => 'Invited User']);
        $this->gamingSession = GamingSession::factory()->create(['host_user_id' => $this->host->id]);
    }

    /** @test */
    public function it_creates_an_invitation_with_valid_data()
    {
        $invitationData = [
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ];

        $invitation = GamingSessionInvitation::create($invitationData);

        $this->assertInstanceOf(GamingSessionInvitation::class, $invitation);
        $this->assertEquals($this->gamingSession->id, $invitation->gaming_session_id);
        $this->assertEquals($this->invitedUser->id, $invitation->invited_user_id);
        $this->assertEquals(GamingSessionInvitation::STATUS_PENDING, $invitation->status);
    }

    /** @test */
    public function it_has_status_constants()
    {
        $this->assertEquals('pending', GamingSessionInvitation::STATUS_PENDING);
        $this->assertEquals('accepted', GamingSessionInvitation::STATUS_ACCEPTED);
        $this->assertEquals('declined', GamingSessionInvitation::STATUS_DECLINED);
        $this->assertEquals('expired', GamingSessionInvitation::STATUS_EXPIRED);
    }

    /** @test */
    public function it_has_proper_relationships()
    {
        $invitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        // Test gaming session relationship
        $this->assertInstanceOf(GamingSession::class, $invitation->gamingSession);
        $this->assertEquals($this->gamingSession->id, $invitation->gamingSession->id);

        // Test invited user relationship
        $this->assertInstanceOf(User::class, $invitation->invitedUser);
        $this->assertEquals($this->invitedUser->id, $invitation->invitedUser->id);

        // Test invited by relationship
        $this->assertInstanceOf(User::class, $invitation->invitedBy);
        $this->assertEquals($this->host->id, $invitation->invitedBy->id);
    }

    /** @test */
    public function it_can_accept_invitation()
    {
        $invitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $result = $invitation->accept();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_ACCEPTED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->responded_at);

        // Check that user was added as participant
        $this->assertTrue(
            GamingSessionParticipant::where('gaming_session_id', $this->gamingSession->id)
                ->where('user_id', $this->invitedUser->id)
                ->exists()
        );
    }

    /** @test */
    public function it_can_decline_invitation()
    {
        $invitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $result = $invitation->decline();

        $this->assertTrue($result);
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->fresh()->status);
        $this->assertNotNull($invitation->fresh()->responded_at);

        // Check that user was NOT added as participant
        $this->assertFalse(
            GamingSessionParticipant::where('gaming_session_id', $this->gamingSession->id)
                ->where('user_id', $this->invitedUser->id)
                ->exists()
        );
    }

    /** @test */
    public function it_identifies_user_invitations()
    {
        $userInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $group = Group::factory()->create();
        $groupInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_group_id' => $group->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $this->assertTrue($userInvitation->isUserInvitation());
        $this->assertFalse($groupInvitation->isUserInvitation());
    }

    /** @test */
    public function it_identifies_group_invitations()
    {
        $userInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $group = Group::factory()->create();
        $groupInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_group_id' => $group->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $this->assertFalse($userInvitation->isGroupInvitation());
        $this->assertTrue($groupInvitation->isGroupInvitation());
    }

    /** @test */
    public function it_scopes_pending_invitations()
    {
        $pendingInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);

        $acceptedInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => User::factory()->create()->id,
            'invited_by_user_id' => $this->host->id,
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);

        $pendingInvitations = GamingSessionInvitation::pending()->get();

        $this->assertCount(1, $pendingInvitations);
        $this->assertEquals($pendingInvitation->id, $pendingInvitations->first()->id);
    }

    /** @test */
    public function it_scopes_user_invitations()
    {
        $userInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $group = Group::factory()->create();
        $groupInvitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_group_id' => $group->id,
            'invited_by_user_id' => $this->host->id,
        ]);

        $userInvitations = GamingSessionInvitation::forUsers()->get();

        $this->assertCount(1, $userInvitations);
        $this->assertEquals($userInvitation->id, $userInvitations->first()->id);
    }

    /** @test */
    public function it_cannot_accept_already_responded_invitation()
    {
        $invitation = GamingSessionInvitation::create([
            'gaming_session_id' => $this->gamingSession->id,
            'invited_user_id' => $this->invitedUser->id,
            'invited_by_user_id' => $this->host->id,
            'status' => GamingSessionInvitation::STATUS_DECLINED,
            'responded_at' => now(),
        ]);

        // Should not change status or create participant
        $result = $invitation->accept();
        
        $this->assertTrue($result); // Method returns true but doesn't change anything
        $this->assertEquals(GamingSessionInvitation::STATUS_DECLINED, $invitation->fresh()->status);
    }
}
