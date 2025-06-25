<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class GroupControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--seed' => true]);
    }

    public function test_index_displays_public_groups()
    {
        $owner = User::factory()->create();

        $publicGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Public Gaming Group',
            'is_public' => true,
        ]);

        $privateGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Private Gaming Group',
            'is_public' => false,
        ]);

        // Create memberships to test count
        GroupMembership::factory()->create([
            'group_id' => $publicGroup->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $response = $this->get(route('groups.index'));

        $response->assertStatus(200);
        $response->assertViewIs('groups.index');
        $response->assertViewHas(['groups', 'games', 'platforms']);
        $response->assertSee('Public Gaming Group');
        $response->assertDontSee('Private Gaming Group');
    }

    public function test_index_filters_by_game()
    {
        $owner = User::factory()->create();

        $valorantGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'game' => 'Valorant',
            'is_public' => true,
        ]);

        $csgoGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'game' => 'CS:GO',
            'is_public' => true,
        ]);

        $response = $this->get(route('groups.index', ['game' => 'Valorant']));

        $response->assertStatus(200);
        $groups = $response->viewData('groups');
        $this->assertTrue($groups->contains('id', $valorantGroup->id));
        $this->assertFalse($groups->contains('id', $csgoGroup->id));
    }

    public function test_index_filters_by_platform()
    {
        $owner = User::factory()->create();

        $pcGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'platform' => 'pc',
            'is_public' => true,
        ]);

        $xboxGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'platform' => 'xbox',
            'is_public' => true,
        ]);

        $response = $this->get(route('groups.index', ['platform' => 'pc']));

        $response->assertStatus(200);
        $groups = $response->viewData('groups');
        $this->assertTrue($groups->contains('id', $pcGroup->id));
        $this->assertFalse($groups->contains('id', $xboxGroup->id));
    }

    public function test_index_searches_by_name_and_description()
    {
        $owner = User::factory()->create();

        $searchableGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Searchable Group Name',
            'description' => 'Regular description',
            'is_public' => true,
        ]);

        $descriptionGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Regular Name',
            'description' => 'Searchable description content',
            'is_public' => true,
        ]);

        $otherGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Other Group',
            'description' => 'Other content',
            'is_public' => true,
        ]);

        $response = $this->get(route('groups.index', ['search' => 'Searchable']));

        $response->assertStatus(200);
        $groups = $response->viewData('groups');
        $this->assertTrue($groups->contains('id', $searchableGroup->id));
        $this->assertTrue($groups->contains('id', $descriptionGroup->id));
        $this->assertFalse($groups->contains('id', $otherGroup->id));
    }

    public function test_create_displays_form()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('groups.create'));

        $response->assertStatus(200);
        $response->assertViewIs('groups.create');
        $response->assertViewHas(['games', 'platforms']);
    }

    public function test_create_requires_authentication()
    {
        $response = $this->get(route('groups.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_store_creates_group_with_owner_membership()
    {
        $user = User::factory()->create();

        $groupData = [
            'name' => 'New Gaming Group',
            'description' => 'A great group for gaming',
            'game' => 'Valorant',
            'platform' => 'pc',
            'is_public' => true,
            'max_members' => 25,
        ];

        $response = $this->actingAs($user)->post(route('groups.store'), $groupData);

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'Group created successfully!');

        $this->assertDatabaseHas('groups', [
            'owner_id' => $user->id,
            'name' => 'New Gaming Group',
            'description' => 'A great group for gaming',
            'game' => 'Valorant',
            'platform' => 'pc',
            'is_public' => true,
            'max_members' => 25,
        ]);

        $group = Group::where('name', 'New Gaming Group')->first();
        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);
    }

    public function test_store_handles_boolean_fields()
    {
        $user = User::factory()->create();

        // Test with is_public not explicitly set (should default to true)
        $groupData = [
            'name' => 'Test Group',
            'max_members' => 50,
        ];

        $response = $this->actingAs($user)->post(route('groups.store'), $groupData);

        $response->assertRedirect(route('groups.index'));

        $this->assertDatabaseHas('groups', [
            'owner_id' => $user->id,
            'name' => 'Test Group',
            'is_public' => true,
            'max_members' => 50,
        ]);
    }

    public function test_store_validates_required_fields()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), []);

        $response->assertSessionHasErrors(['name']);
    }

    public function test_store_validates_platform_values()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Test Group',
            'platform' => 'invalid_platform',
        ]);

        $response->assertSessionHasErrors(['platform']);
    }

    public function test_store_validates_max_members_range()
    {
        $user = User::factory()->create();

        // Test minimum
        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Test Group',
            'max_members' => 1, // Too low
        ]);

        $response->assertSessionHasErrors(['max_members']);

        // Test maximum
        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => 'Test Group',
            'max_members' => 501, // Too high
        ]);

        $response->assertSessionHasErrors(['max_members']);
    }

    public function test_store_validates_string_lengths()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('groups.store'), [
            'name' => str_repeat('a', 256), // Too long
            'description' => str_repeat('b', 1001), // Too long
            'game' => str_repeat('c', 256), // Too long
        ]);

        $response->assertSessionHasErrors(['name', 'description', 'game']);
    }

    public function test_store_requires_authentication()
    {
        $response = $this->post(route('groups.store'), [
            'name' => 'Test Group',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_show_displays_group_details()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Create memberships
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($owner)->get(route('groups.show', $group));

        $response->assertStatus(200);
        $response->assertViewIs('groups.show');
        $response->assertViewHas(['group', 'membership', 'canJoin', 'canInvite', 'friendsToInvite']);
    }

    public function test_show_calculates_user_permissions_correctly()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $nonMember = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        // Test as owner
        $response = $this->actingAs($owner)->get(route('groups.show', $group));
        $response->assertStatus(200);
        $canInvite = $response->viewData('canInvite');
        $this->assertTrue($canInvite);

        // Test as non-member
        $response = $this->actingAs($nonMember)->get(route('groups.show', $group));
        $response->assertStatus(200);
        $canJoin = $response->viewData('canJoin');
        $this->assertTrue($canJoin);
    }

    public function test_edit_displays_form_for_admin()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)->get(route('groups.edit', $group));

        $response->assertStatus(200);
        $response->assertViewIs('groups.edit');
        $response->assertViewHas(['group', 'games', 'platforms']);
    }

    public function test_edit_prevents_non_admin_access()
    {
        $owner = User::factory()->create();
        $nonAdmin = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonAdmin)->get(route('groups.edit', $group));

        $response->assertStatus(403);
    }

    public function test_edit_requires_authentication()
    {
        $group = Group::factory()->create();

        $response = $this->get(route('groups.edit', $group));

        $response->assertRedirect(route('login'));
    }

    public function test_update_modifies_group()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Old Name',
            'description' => 'Old description',
        ]);

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'game' => 'New Game',
            'platform' => 'pc',
            'is_public' => false,
            'max_members' => 30,
        ];

        $response = $this->actingAs($owner)
            ->put(route('groups.update', $group), $updateData);

        $response->assertRedirect(route('groups.show', $group));
        $response->assertSessionHas('success', 'Group updated successfully!');

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'Updated Name',
            'description' => 'Updated description',
            'game' => 'New Game',
            'platform' => 'pc',
            'is_public' => false,
            'max_members' => 30,
        ]);
    }

    public function test_update_prevents_non_admin_modification()
    {
        $owner = User::factory()->create();
        $nonAdmin = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonAdmin)
            ->put(route('groups.update', $group), [
                'name' => 'Hacked Name',
            ]);

        $response->assertStatus(403);
    }

    public function test_destroy_deletes_group()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($owner)
            ->delete(route('groups.destroy', $group));

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'Group deleted successfully!');

        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
        ]);
    }

    public function test_destroy_prevents_non_owner_deletion()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        // Even admins can't delete if they're not the owner
        $response = $this->actingAs($admin)
            ->delete(route('groups.destroy', $group));

        $response->assertStatus(403);

        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
        ]);
    }

    public function test_join_adds_user_to_public_group()
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);

        $response = $this->actingAs($joiner)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'You have successfully joined the group!');

        $this->assertDatabaseHas('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);
    }

    public function test_join_prevents_joining_private_group()
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => false,
        ]);

        $response = $this->actingAs($joiner)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This group is private. You need an invitation to join.');

        $this->assertDatabaseMissing('group_memberships', [
            'group_id' => $group->id,
            'user_id' => $joiner->id,
        ]);
    }

    public function test_join_prevents_double_joining()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
        ]);

        // Create existing membership
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You are already a member of this group.');
    }

    public function test_join_prevents_joining_full_group()
    {
        $owner = User::factory()->create();
        $joiner = User::factory()->create();

        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
            'max_members' => 2,
        ]);

        // Fill up the group (owner + 1 member = 2 total)
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $existingMember = User::factory()->create();
        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $existingMember->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($joiner)
            ->post(route('groups.join', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'This group is full.');
    }

    public function test_leave_removes_user_from_group()
    {
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $membership = GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $response = $this->actingAs($member)
            ->post(route('groups.leave', $group));

        $response->assertRedirect(route('groups.index'));
        $response->assertSessionHas('success', 'You have left the group.');

        $this->assertDatabaseMissing('group_memberships', [
            'id' => $membership->id,
        ]);
    }

    public function test_leave_prevents_owner_from_leaving()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $owner->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        $response = $this->actingAs($owner)
            ->post(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Group owners cannot leave their group. Transfer ownership or delete the group instead.');
    }

    public function test_leave_prevents_non_member_from_leaving()
    {
        $owner = User::factory()->create();
        $nonMember = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $response = $this->actingAs($nonMember)
            ->post(route('groups.leave', $group));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'You are not a member of this group.');
    }

    public function test_my_groups_displays_user_groups()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        // Owned group
        $ownedGroup = Group::factory()->create(['owner_id' => $user->id]);
        GroupMembership::factory()->create([
            'group_id' => $ownedGroup->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_OWNER,
        ]);

        // Member group
        $memberGroup = Group::factory()->create(['owner_id' => $otherUser->id]);
        GroupMembership::factory()->create([
            'group_id' => $memberGroup->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        // Pending invitation
        $invitedGroup = Group::factory()->create(['owner_id' => $otherUser->id]);
        GroupInvitation::factory()->create([
            'group_id' => $invitedGroup->id,
            'invited_user_id' => $user->id,
            'invited_by_user_id' => $otherUser->id,
            'status' => GroupInvitation::STATUS_PENDING,
        ]);

        $response = $this->actingAs($user)->get(route('groups.my-groups'));

        $response->assertStatus(200);
        $response->assertViewIs('groups.my-groups');
        $response->assertViewHas(['ownedGroups', 'memberGroups', 'pendingInvitations']);

        $ownedGroups = $response->viewData('ownedGroups');
        $memberGroups = $response->viewData('memberGroups');
        $pendingInvitations = $response->viewData('pendingInvitations');

        $this->assertTrue($ownedGroups->contains('id', $ownedGroup->id));
        $this->assertTrue($memberGroups->contains('id', $memberGroup->id));
        $this->assertCount(1, $pendingInvitations);
    }

    public function test_my_groups_requires_authentication()
    {
        $response = $this->get(route('groups.my-groups'));

        $response->assertRedirect(route('login'));
    }

    public function test_all_routes_require_authentication_except_index_and_show()
    {
        $group = Group::factory()->create();

        $authRequiredRoutes = [
            ['get', 'groups.create'],
            ['post', 'groups.store'],
            ['get', 'groups.edit', $group],
            ['put', 'groups.update', $group],
            ['delete', 'groups.destroy', $group],
            ['post', 'groups.join', $group],
            ['post', 'groups.leave', $group],
            ['get', 'groups.my-groups'],
        ];

        foreach ($authRequiredRoutes as $routeData) {
            $method = $routeData[0];
            $routeName = $routeData[1];
            $parameter = $routeData[2] ?? null;

            $route = $parameter ? route($routeName, $parameter) : route($routeName);
            $response = $this->$method($route);
            $response->assertRedirect(route('login'));
        }
    }

    public function test_group_ordering_and_pagination()
    {
        $owner = User::factory()->create();

        // Create groups with different timestamps
        $olderGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
            'created_at' => now()->subHours(2),
        ]);

        $newerGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
            'created_at' => now()->subHour(),
        ]);

        $response = $this->get(route('groups.index'));

        $response->assertStatus(200);
        $groups = $response->viewData('groups');

        // Should be ordered by created_at desc (newer first)
        $this->assertEquals($newerGroup->id, $groups->first()->id);
        $this->assertEquals($olderGroup->id, $groups->last()->id);
    }

    public function test_complex_group_filtering()
    {
        $owner = User::factory()->create();

        $targetGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'Valorant PC Squad',
            'game' => 'Valorant',
            'platform' => 'pc',
            'is_public' => true,
        ]);

        $otherGroup = Group::factory()->create([
            'owner_id' => $owner->id,
            'name' => 'CS:GO Xbox Team',
            'game' => 'CS:GO',
            'platform' => 'xbox',
            'is_public' => true,
        ]);

        $response = $this->get(route('groups.index', [
            'game' => 'Valorant',
            'platform' => 'pc',
            'search' => 'Squad',
        ]));

        $response->assertStatus(200);
        $groups = $response->viewData('groups');

        $this->assertTrue($groups->contains('id', $targetGroup->id));
        $this->assertFalse($groups->contains('id', $otherGroup->id));
    }
}
