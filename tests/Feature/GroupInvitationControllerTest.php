<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupInvitationControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
    }

    public function test_index_displays_user_invitations()
    {
        $user = User::factory()->create();
        $inviter = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $inviter->id]);

        // Create invitation for the user
        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $inviter->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        // Create invitation for someone else (should not appear)
        $otherUser = User::factory()->create();
        GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $otherUser->id,
            'invited_by_user_id' => $inviter->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('group-invitations.index'));

        $response->assertStatus(200);
        $response->assertViewIs('groups.invitations.index');
        $response->assertViewHas(['invitations', 'status']);

        $invitations = $response->viewData('invitations');
        $this->assertCount(1, $invitations);
        $this->assertEquals($invitation->id, $invitations->first()->id);
    }

    public function test_index_filters_by_status()
    {
        $user = User::factory()->create();
        $inviter = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $inviter->id]);

        // Create invitations with different statuses
        $pendingInvitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $inviter->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $acceptedInvitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $inviter->id,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);

        // Test filtering for pending only
        $response = $this->actingAs($user)->get(route('group-invitations.index', [
            'status' => GroupInvitation::STATUS_PENDING
        ]));

        $response->assertStatus(200);
        $invitations = $response->viewData('invitations');
        $this->assertCount(1, $invitations);
        $this->assertEquals($pendingInvitation->id, $invitations->first()->id);

        // Test filtering for accepted only
        $response = $this->actingAs($user)->get(route('group-invitations.index', [
            'status' => GroupInvitation::STATUS_ACCEPTED
        ]));

        $response->assertStatus(200);
        $invitations = $response->viewData('invitations');
        $this->assertCount(1, $invitations);
        $this->assertEquals($acceptedInvitation->id, $invitations->first()->id);
    }

    public function test_index_requires_authentication()
    {
        $response = $this->get(route('group-invitations.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_invitation()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Make owner a member with invite permission
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $invitationData = [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
            'message' => 'Join our awesome group!',
        ];

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.store'), $invitationData);

        $response->assertRedirect();
        $response->assertSessionHas('success', "Invitation sent to {$invitedUser->name}!");

        $this->assertDatabaseHas('group_invitations', [
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'message' => 'Join our awesome group!',
            'status' => GroupInvitation::STATUS_PENDING,
        ]);
    }

    public function test_store_prevents_unauthorized_user_from_inviting()
    {
        $owner = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitationData = [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
        ];

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('group-invitations.store'), $invitationData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You do not have permission to invite users to this group.');

        $this->assertDatabaseMissing('group_invitations', [
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
        ]);
    }

    public function test_store_prevents_inviting_existing_member()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Make owner able to invite
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        // Make target user already a member
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $invitationData = [
            'group_id' => $group->id,
            'user_id' => $member->id,
        ];

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.store'), $invitationData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This user is already a member of the group.');
    }

    public function test_store_prevents_duplicate_pending_invitation()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        // Create existing pending invitation
        GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $invitationData = [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
        ];

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.store'), $invitationData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This user already has a pending invitation to this group.');
    }

    public function test_store_prevents_inviting_to_full_group()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'max_members' => 2,
        ]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        // Fill the group to capacity
        $member = User::factory()->create();
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $invitationData = [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
        ];

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.store'), $invitationData);

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This group is full.');
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('group-invitations.store'), []);

        $response->assertSessionHasErrors(['group_id', 'user_id']);
    }

    public function test_store_validates_foreign_keys()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('group-invitations.store'), [
                'group_id' => 99999, // Non-existent
                'user_id' => 99999, // Non-existent
            ]);

        $response->assertSessionHasErrors(['group_id', 'user_id']);
    }

    public function test_store_validates_message_length()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.store'), [
                'group_id' => $group->id,
                'user_id' => $invitedUser->id,
                'message' => str_repeat('a', 501), // Too long
            ]);

        $response->assertSessionHasErrors(['message']);
    }

    public function test_accept_creates_membership_and_updates_invitation()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($invitedUser)
            ->post(route('group-invitations.accept', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success', "You have joined {$group->name}!");

        // Check invitation was accepted
        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation->id,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);

        // Check membership was created
        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);
    }

    public function test_accept_prevents_unauthorized_user()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('group-invitations.accept', $invitation));

        $response->assertStatus(403);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);
    }

    public function test_accept_prevents_accepting_non_pending_invitation()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);

        $response = $this->actingAs($invitedUser)
            ->post(route('group-invitations.accept', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This invitation is no longer valid.');
    }

    public function test_accept_prevents_joining_full_group()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'max_members' => 2,
        ]);

        // Fill the group after invitation was sent
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $member = User::factory()->create();
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($invitedUser)
            ->post(route('group-invitations.accept', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This group is now full.');
    }

    public function test_decline_updates_invitation_status()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($invitedUser)
            ->post(route('group-invitations.decline', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation declined.');

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation->id,
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);

        // Should not create membership
        $this->assertDatabaseMissing('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $invitedUser->id,
        ]);
    }

    public function test_decline_prevents_unauthorized_user()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('group-invitations.decline', $invitation));

        $response->assertStatus(403);
    }

    public function test_cancel_updates_invitation_status()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('group-invitations.cancel', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation cancelled.');

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation->id,
            'status' => GroupInvitation::STATUS_CANCELLED,
        ]);
    }

    public function test_cancel_allows_group_admin()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Make admin a group admin
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
            'role' => GroupMembership::ROLE_ADMIN,
        ]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($admin)
            ->post(route('group-invitations.cancel', $invitation));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Invitation cancelled.');
    }

    public function test_cancel_prevents_unauthorized_user()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->post(route('group-invitations.cancel', $invitation));

        $response->assertStatus(403);
    }

    public function test_show_displays_invitation_details()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($invitedUser)
            ->get(route('group-invitations.show', $invitation));

        $response->assertStatus(200);
        $response->assertViewIs('groups.invitations.show');
        $response->assertViewHas('invitation');
    }

    public function test_show_prevents_unauthorized_user()
    {
        $owner = User::factory()->create();
        $invitedUser = User::factory()->create();
        $unauthorizedUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $invitedUser->id,
            'invited_by_user_id' => $owner->id,
        ]);

        $response = $this->actingAs($unauthorizedUser)
            ->get(route('group-invitations.show', $invitation));

        $response->assertStatus(403);
    }

    public function test_bulk_action_accepts_multiple_invitations()
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $group1 = Group::factory()->create(['owner_id' => $owner->id]);
        $group2 = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation1 = GroupInvitation::factory()->create([
            'group_id' => $group1->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $invitation2 = GroupInvitation::factory()->create([
            'group_id' => $group2->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'accept',
                'invitation_ids' => [$invitation1->id, $invitation2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 invitation(s) accepted.');

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation1->id,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation2->id,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);

        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group1->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group2->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_bulk_action_declines_multiple_invitations()
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();
        $group1 = Group::factory()->create(['owner_id' => $owner->id]);
        $group2 = Group::factory()->create(['owner_id' => $owner->id]);

        $invitation1 = GroupInvitation::factory()->create([
            'group_id' => $group1->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $invitation2 = GroupInvitation::factory()->create([
            'group_id' => $group2->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'decline',
                'invitation_ids' => [$invitation1->id, $invitation2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '2 invitation(s) declined.');

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation1->id,
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);

        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation2->id,
            'status' => GroupInvitation::STATUS_DECLINED,
        ]);
    }

    public function test_bulk_action_skips_full_groups_on_accept()
    {
        $user = User::factory()->create();
        $owner = User::factory()->create();

        $fullGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'max_members' => 1,
        ]);

        $normalGroup = Group::factory()->create(['owner_id' => $owner->id]);

        // Fill the first group
        GroupMembership::factory()->create([
            'group_id' => $fullGroup->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $invitation1 = GroupInvitation::factory()->create([
            'group_id' => $fullGroup->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $invitation2 = GroupInvitation::factory()->create([
            'group_id' => $normalGroup->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'accept',
                'invitation_ids' => [$invitation1->id, $invitation2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '1 invitation(s) accepted.');

        // Full group invitation should remain pending
        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation1->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        // Normal group invitation should be accepted
        $this->assertDatabaseHas('group_invitations', [
            'id' => $invitation2->id,
            'status' => GroupInvitation::STATUS_ACCEPTED,
        ]);
    }

    public function test_bulk_action_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), []);

        $response->assertSessionHasErrors(['action', 'invitation_ids']);
    }

    public function test_bulk_action_validates_action_values()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'invalid_action',
                'invitation_ids' => [1],
            ]);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_bulk_action_validates_invitation_existence()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'accept',
                'invitation_ids' => [99999], // Non-existent
            ]);

        $response->assertSessionHasErrors(['invitation_ids.0']);
    }

    public function test_bulk_action_only_processes_user_invitations()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Create invitation for other user
        $otherInvitation = GroupInvitation::factory()->create([
            'group_id' => $group->id,
            'invited_user_id' => $otherUser->id,
            'invited_by_user_id' => $owner->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)
            ->post(route('group-invitations.bulk-action'), [
                'action' => 'accept',
                'invitation_ids' => [$otherInvitation->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', '0 invitation(s) accepted.');

        // Other user's invitation should remain unchanged
        $this->assertDatabaseHas('group_invitations', [
            'id' => $otherInvitation->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);
    }

    public function test_all_actions_require_authentication()
    {
        $invitation = GroupInvitation::factory()->create();

        $routes = [
            ['get', 'group-invitations.index'],
            ['post', 'group-invitations.store'],
            ['post', 'group-invitations.accept', $invitation],
            ['post', 'group-invitations.decline', $invitation],
            ['post', 'group-invitations.cancel', $invitation],
            ['get', 'group-invitations.show', $invitation],
            ['post', 'group-invitations.bulk-action'],
        ];

        foreach ($routes as $routeData) {
            $method = $routeData[0];
            $routeName = $routeData[1];
            $parameter = $routeData[2] ?? null;

            $route = $parameter ? route($routeName, $parameter) : route($routeName);
            $response = $this->$method($route);
            $response->assertRedirect(route('login'));
        }
    }
}
