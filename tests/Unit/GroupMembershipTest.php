<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupMembershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_membership_belongs_to_group()
    {
        $group = Group::factory()->create();
        $membership = GroupMembership::factory()->create(['group_id' => $group->id]);

        $this->assertEquals($group->id, $membership->group->id);
        $this->assertEquals($group->name, $membership->group->name);
    }

    public function test_group_membership_belongs_to_user()
    {
        $user = User::factory()->create();
        $membership = GroupMembership::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $membership->user->id);
        $this->assertEquals($user->name, $membership->user->name);
    }

    public function test_group_membership_has_fillable_attributes()
    {
        $fillable = [
            'group_id',
            'user_id',
            'role',
            'joined_at',
            'permissions',
        ];

        $membership = new GroupMembership();
        $this->assertEquals($fillable, $membership->getFillable());
    }

    public function test_group_membership_casts_attributes()
    {
        $membership = GroupMembership::factory()->create([
            'joined_at' => '2023-01-15 10:30:00',
            'permissions' => ['can_invite' => true, 'can_kick' => false],
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $membership->joined_at);
        $this->assertIsArray($membership->permissions);
        $this->assertEquals(['can_invite' => true, 'can_kick' => false], $membership->permissions);
    }

    public function test_role_constants_are_defined()
    {
        $expectedRoles = [
            'member' => 'Member',
            'moderator' => 'Moderator',
            'admin' => 'Admin',
            'owner' => 'Owner',
        ];

        $this->assertEquals('member', GroupMembership::ROLE_MEMBER);
        $this->assertEquals('moderator', GroupMembership::ROLE_MODERATOR);
        $this->assertEquals('admin', GroupMembership::ROLE_ADMIN);
        $this->assertEquals('owner', GroupMembership::ROLE_OWNER);
        $this->assertEquals($expectedRoles, GroupMembership::ROLES);
    }

    public function test_by_role_scope()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $moderatorMembership = GroupMembership::factory()->moderator()->create();

        $memberMemberships = GroupMembership::byRole(GroupMembership::ROLE_MEMBER)->get();
        $adminMemberships = GroupMembership::byRole(GroupMembership::ROLE_ADMIN)->get();

        $this->assertEquals(1, $memberMemberships->count());
        $this->assertTrue($memberMemberships->contains('id', $memberMembership->id));
        $this->assertFalse($memberMemberships->contains('id', $adminMembership->id));

        $this->assertEquals(1, $adminMemberships->count());
        $this->assertTrue($adminMemberships->contains('id', $adminMembership->id));
        $this->assertFalse($adminMemberships->contains('id', $moderatorMembership->id));
    }

    public function test_admins_scope()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $ownerMembership = GroupMembership::factory()->owner()->create();

        $adminMemberships = GroupMembership::admins()->get();

        $this->assertEquals(2, $adminMemberships->count());
        $this->assertTrue($adminMemberships->contains('id', $adminMembership->id));
        $this->assertTrue($adminMemberships->contains('id', $ownerMembership->id));
        $this->assertFalse($adminMemberships->contains('id', $memberMembership->id));
    }

    public function test_is_admin_method()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $ownerMembership = GroupMembership::factory()->owner()->create();

        $this->assertFalse($memberMembership->isAdmin());
        $this->assertTrue($adminMembership->isAdmin());
        $this->assertTrue($ownerMembership->isAdmin());
    }

    public function test_is_owner_method()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $ownerMembership = GroupMembership::factory()->owner()->create();

        $this->assertFalse($memberMembership->isOwner());
        $this->assertFalse($adminMembership->isOwner());
        $this->assertTrue($ownerMembership->isOwner());
    }

    public function test_can_invite_method()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $moderatorMembership = GroupMembership::factory()->moderator()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $ownerMembership = GroupMembership::factory()->owner()->create();

        $this->assertFalse($memberMembership->canInvite());
        $this->assertTrue($moderatorMembership->canInvite());
        $this->assertTrue($adminMembership->canInvite());
        $this->assertTrue($ownerMembership->canInvite());
    }

    public function test_role_name_attribute()
    {
        $memberMembership = GroupMembership::factory()->member()->create();
        $moderatorMembership = GroupMembership::factory()->moderator()->create();
        $adminMembership = GroupMembership::factory()->admin()->create();
        $ownerMembership = GroupMembership::factory()->owner()->create();

        $this->assertEquals('Member', $memberMembership->role_name);
        $this->assertEquals('Moderator', $moderatorMembership->role_name);
        $this->assertEquals('Admin', $adminMembership->role_name);
        $this->assertEquals('Owner', $ownerMembership->role_name);
    }

    public function test_group_membership_factory_creates_valid_membership()
    {
        $membership = GroupMembership::factory()->create();

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertNotNull($membership->group_id);
        $this->assertNotNull($membership->user_id);
        $this->assertEquals(GroupMembership::ROLE_MEMBER, $membership->role);
        $this->assertNotNull($membership->joined_at);
    }

    public function test_group_membership_can_be_created_with_complete_data()
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();

        $membershipData = [
            'group_id' => $group->id,
            'user_id' => $user->id,
            'role' => GroupMembership::ROLE_ADMIN,
            'joined_at' => now(),
            'permissions' => ['can_invite' => true, 'can_kick' => true],
        ];

        $membership = GroupMembership::create($membershipData);

        $this->assertInstanceOf(GroupMembership::class, $membership);
        $this->assertEquals($group->id, $membership->group_id);
        $this->assertEquals($user->id, $membership->user_id);
        $this->assertEquals(GroupMembership::ROLE_ADMIN, $membership->role);
        $this->assertEquals(['can_invite' => true, 'can_kick' => true], $membership->permissions);
    }

    public function test_role_hierarchy_permissions()
    {
        $member = GroupMembership::factory()->member()->create();
        $moderator = GroupMembership::factory()->moderator()->create();
        $admin = GroupMembership::factory()->admin()->create();
        $owner = GroupMembership::factory()->owner()->create();

        // Test admin privileges
        $this->assertFalse($member->isAdmin());
        $this->assertFalse($moderator->isAdmin());
        $this->assertTrue($admin->isAdmin());
        $this->assertTrue($owner->isAdmin());

        // Test invitation privileges
        $this->assertFalse($member->canInvite());
        $this->assertTrue($moderator->canInvite());
        $this->assertTrue($admin->canInvite());
        $this->assertTrue($owner->canInvite());

        // Test ownership
        $this->assertFalse($member->isOwner());
        $this->assertFalse($moderator->isOwner());
        $this->assertFalse($admin->isOwner());
        $this->assertTrue($owner->isOwner());
    }

    public function test_multiple_memberships_for_same_user_different_groups()
    {
        $user = User::factory()->create();
        $group1 = Group::factory()->create();
        $group2 = Group::factory()->create();

        $membership1 = GroupMembership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group1->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $membership2 = GroupMembership::factory()->create([
            'user_id' => $user->id,
            'group_id' => $group2->id,
            'role' => GroupMembership::ROLE_ADMIN,
        ]);

        $this->assertNotEquals($membership1->id, $membership2->id);
        $this->assertEquals($user->id, $membership1->user_id);
        $this->assertEquals($user->id, $membership2->user_id);
        $this->assertEquals(GroupMembership::ROLE_MEMBER, $membership1->role);
        $this->assertEquals(GroupMembership::ROLE_ADMIN, $membership2->role);
    }

    public function test_group_membership_with_permissions()
    {
        $membership = GroupMembership::factory()->create([
            'permissions' => [
                'can_invite' => true,
                'can_kick' => false,
                'can_edit_group' => true,
                'can_delete_messages' => false,
            ],
        ]);

        $this->assertIsArray($membership->permissions);
        $this->assertTrue($membership->permissions['can_invite']);
        $this->assertFalse($membership->permissions['can_kick']);
        $this->assertTrue($membership->permissions['can_edit_group']);
        $this->assertFalse($membership->permissions['can_delete_messages']);
    }

    public function test_group_membership_scopes_can_be_chained()
    {
        $group = Group::factory()->create();

        $memberMembership = GroupMembership::factory()->create([
            'group_id' => $group->id,
            'role' => GroupMembership::ROLE_MEMBER,
        ]);

        $adminMembership = GroupMembership::factory()->create([
            'group_id' => $group->id,
            'role' => GroupMembership::ROLE_ADMIN,
        ]);

        // Create admin in different group
        $otherGroupAdmin = GroupMembership::factory()->create([
            'group_id' => Group::factory()->create()->id,
            'role' => GroupMembership::ROLE_ADMIN,
        ]);

        $groupAdmins = GroupMembership::where('group_id', $group->id)->admins()->get();

        $this->assertEquals(1, $groupAdmins->count());
        $this->assertTrue($groupAdmins->contains('id', $adminMembership->id));
        $this->assertFalse($groupAdmins->contains('id', $memberMembership->id));
        $this->assertFalse($groupAdmins->contains('id', $otherGroupAdmin->id));
    }
}
