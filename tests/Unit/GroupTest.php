<?php

namespace Tests\Unit;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GroupTest extends TestCase
{
    use RefreshDatabase;

    public function test_group_belongs_to_owner()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $this->assertEquals($owner->id, $group->owner->id);
        $this->assertEquals($owner->name, $group->owner->name);
    }

    public function test_group_has_fillable_attributes()
    {
        $fillable = [
            'name',
            'description',
            'game',
            'platform',
            'owner_id',
            'is_public',
            'max_members',
            'avatar',
            'settings',
        ];

        $group = new Group();
        $this->assertEquals($fillable, $group->getFillable());
    }

    public function test_group_casts_attributes()
    {
        $owner = User::factory()->create();
        $group = Group::factory()->create([
            'owner_id' => $owner->id,
            'is_public' => true,
            'settings' => ['notifications' => true, 'auto_approve' => false],
        ]);

        $this->assertIsBool($group->is_public);
        $this->assertIsArray($group->settings);
        $this->assertTrue($group->is_public);
        $this->assertEquals(['notifications' => true, 'auto_approve' => false], $group->settings);
    }

    public function test_platform_constants_are_defined()
    {
        $expectedPlatforms = [
            'steam' => 'Steam',
            'xbox_live' => 'Xbox Live',
            'playstation_network' => 'PlayStation Network',
            'nintendo_online' => 'Nintendo Online',
            'battlenet' => 'Battle.net',
            'cross_platform' => 'Cross Platform',
        ];

        $this->assertEquals($expectedPlatforms, Group::PLATFORMS);
    }

    public function test_popular_games_constants_are_defined()
    {
        $expectedGames = [
            'call_of_duty' => 'Call of Duty',
            'halo' => 'Halo',
            'fifa' => 'FIFA',
            'fortnite' => 'Fortnite',
            'apex_legends' => 'Apex Legends',
            'valorant' => 'Valorant',
            'counter_strike' => 'Counter-Strike',
            'overwatch' => 'Overwatch',
            'rocket_league' => 'Rocket League',
            'minecraft' => 'Minecraft',
            'gta_v' => 'Grand Theft Auto V',
            'destiny' => 'Destiny',
            'warframe' => 'Warframe',
            'league_of_legends' => 'League of Legends',
            'dota_2' => 'Dota 2',
            'other' => 'Other',
        ];

        $this->assertEquals($expectedGames, Group::POPULAR_GAMES);
    }

    public function test_group_has_many_memberships()
    {
        $group = Group::factory()->create();
        $membership = GroupMembership::factory()->create(['group_id' => $group->id]);

        $this->assertTrue($group->memberships->contains($membership));
        $this->assertEquals(1, $group->memberships->count());
    }

    public function test_group_has_many_invitations()
    {
        $group = Group::factory()->create();
        $invitation = GroupInvitation::factory()->create(['group_id' => $group->id]);

        $this->assertTrue($group->invitations->contains($invitation));
        $this->assertEquals(1, $group->invitations->count());
    }

    public function test_public_scope()
    {
        $publicGroup = Group::factory()->public()->create();
        $privateGroup = Group::factory()->private()->create();

        $publicGroups = Group::public()->get();

        $this->assertEquals(1, $publicGroups->count());
        $this->assertTrue($publicGroups->contains('id', $publicGroup->id));
        $this->assertFalse($publicGroups->contains('id', $privateGroup->id));
    }

    public function test_by_game_scope()
    {
        $callOfDutyGroup = Group::factory()->forGame('call_of_duty')->create();
        $haloGroup = Group::factory()->forGame('halo')->create();

        $callOfDutyGroups = Group::byGame('call_of_duty')->get();

        $this->assertEquals(1, $callOfDutyGroups->count());
        $this->assertTrue($callOfDutyGroups->contains('id', $callOfDutyGroup->id));
        $this->assertFalse($callOfDutyGroups->contains('id', $haloGroup->id));
    }

    public function test_by_platform_scope()
    {
        $steamGroup = Group::factory()->forPlatform('steam')->create();
        $xboxGroup = Group::factory()->forPlatform('xbox_live')->create();

        $steamGroups = Group::byPlatform('steam')->get();

        $this->assertEquals(1, $steamGroups->count());
        $this->assertTrue($steamGroups->contains('id', $steamGroup->id));
        $this->assertFalse($steamGroups->contains('id', $xboxGroup->id));
    }

    public function test_platform_name_attribute()
    {
        $group = Group::factory()->forPlatform('steam')->create();
        $this->assertEquals('Steam', $group->platform_name);

        $customGroup = Group::factory()->create(['platform' => 'custom_platform']);
        $this->assertEquals('custom_platform', $customGroup->platform_name);
    }

    public function test_game_name_attribute()
    {
        $group = Group::factory()->forGame('call_of_duty')->create();
        $this->assertEquals('Call of Duty', $group->game_name);

        $customGroup = Group::factory()->create(['game' => 'custom_game']);
        $this->assertEquals('custom_game', $customGroup->game_name);
    }

    public function test_member_count_attribute()
    {
        $group = Group::factory()->create();
        GroupMembership::factory()->count(3)->create(['group_id' => $group->id]);

        $this->assertEquals(3, $group->member_count);
    }

    public function test_is_full_method()
    {
        $group = Group::factory()->create(['max_members' => 3]);
        GroupMembership::factory()->count(2)->create(['group_id' => $group->id]);

        $this->assertFalse($group->isFull());

        GroupMembership::factory()->create(['group_id' => $group->id]);
        $group->refresh();

        $this->assertTrue($group->isFull());
    }

    public function test_has_member_method()
    {
        $group = Group::factory()->create();
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($group->hasMember($user));
        $this->assertFalse($group->hasMember($otherUser));
    }

    public function test_is_owner_method()
    {
        $owner = User::factory()->create();
        $otherUser = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        $this->assertTrue($group->isOwner($owner));
        $this->assertFalse($group->isOwner($otherUser));
    }

    public function test_is_admin_method()
    {
        $owner = User::factory()->create();
        $admin = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        GroupMembership::factory()->admin()->create([
            'group_id' => $group->id,
            'user_id' => $admin->id,
        ]);

        GroupMembership::factory()->member()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);

        $this->assertTrue($group->isAdmin($owner)); // Owner is admin
        $this->assertTrue($group->isAdmin($admin));
        $this->assertFalse($group->isAdmin($member));
    }

    public function test_can_invite_method()
    {
        $owner = User::factory()->create();
        $moderator = User::factory()->create();
        $member = User::factory()->create();
        $group = Group::factory()->create(['owner_id' => $owner->id]);

        GroupMembership::factory()->moderator()->create([
            'group_id' => $group->id,
            'user_id' => $moderator->id,
        ]);

        GroupMembership::factory()->member()->create([
            'group_id' => $group->id,
            'user_id' => $member->id,
        ]);

        $this->assertTrue($group->canInvite($owner)); // Owner can invite
        $this->assertTrue($group->canInvite($moderator)); // Moderator can invite
        $this->assertFalse($group->canInvite($member)); // Member cannot invite
    }

    public function test_get_membership_for_method()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $group = Group::factory()->create();

        $membership = GroupMembership::factory()->create([
            'group_id' => $group->id,
            'user_id' => $user->id,
        ]);

        $this->assertEquals($membership->id, $group->getMembershipFor($user)->id);
        $this->assertNull($group->getMembershipFor($otherUser));
    }

    public function test_has_pending_invitation_method()
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $group = Group::factory()->create();

        GroupInvitation::factory()->pending()->create([
            'group_id' => $group->id,
            'invited_user_id' => $user->id,
        ]);

        $this->assertTrue($group->hasPendingInvitation($user));
        $this->assertFalse($group->hasPendingInvitation($otherUser));
    }

    public function test_group_factory_creates_valid_group()
    {
        $group = Group::factory()->create();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertNotNull($group->name);
        $this->assertNotNull($group->owner_id);
        $this->assertContains($group->game, array_keys(Group::POPULAR_GAMES));
        $this->assertContains($group->platform, array_keys(Group::PLATFORMS));
        $this->assertIsBool($group->is_public);
        $this->assertIsInt($group->max_members);
    }

    public function test_group_can_be_created_with_complete_data()
    {
        $owner = User::factory()->create();
        $groupData = [
            'name' => 'Test Gaming Group',
            'description' => 'A test group for unit testing',
            'game' => 'call_of_duty',
            'platform' => 'steam',
            'owner_id' => $owner->id,
            'is_public' => true,
            'max_members' => 20,
            'avatar' => 'https://example.com/avatar.jpg',
            'settings' => ['notifications' => true, 'auto_approve' => false],
        ];

        $group = Group::create($groupData);

        $this->assertInstanceOf(Group::class, $group);
        $this->assertEquals('Test Gaming Group', $group->name);
        $this->assertEquals('call_of_duty', $group->game);
        $this->assertEquals('steam', $group->platform);
        $this->assertEquals($owner->id, $group->owner_id);
        $this->assertTrue($group->is_public);
        $this->assertEquals(20, $group->max_members);
        $this->assertEquals(['notifications' => true, 'auto_approve' => false], $group->settings);
    }

    public function test_multiple_scopes_can_be_chained()
    {
        $targetGroup = Group::factory()->create([
            'game' => 'call_of_duty',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        $otherGroup = Group::factory()->create([
            'game' => 'halo',
            'platform' => 'steam',
            'is_public' => true,
        ]);

        $results = Group::public()
            ->byGame('call_of_duty')
            ->byPlatform('steam')
            ->get();

        $this->assertEquals(1, $results->count());
        $this->assertTrue($results->contains('id', $targetGroup->id));
        $this->assertFalse($results->contains('id', $otherGroup->id));
    }

    public function test_has_member_returns_false_for_null_user()
    {
        $group = Group::factory()->create();

        // Test the early return on line 167 when user is null
        $result = $group->hasMember(null);

        $this->assertFalse($result);
    }

    public function test_has_pending_invitation_returns_false_for_null_user()
    {
        $group = Group::factory()->create();

        // Test the early return on line 221 when user is null
        $result = $group->hasPendingInvitation(null);

        $this->assertFalse($result);
    }
}
