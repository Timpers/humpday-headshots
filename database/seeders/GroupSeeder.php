<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\GroupInvitation;
use Illuminate\Support\Facades\DB;

class GroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get existing users (make sure we have enough users)
            $users = User::all();
            
            if ($users->count() < 10) {
                $this->command->info('Creating additional users for group seeding...');
                // Create more users if we don't have enough
                $additionalUsers = collect([
                    ['name' => 'Gamer Mike', 'email' => 'mike@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Sarah Pro', 'email' => 'sarah@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Gaming Alex', 'email' => 'alex@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Elite Emma', 'email' => 'emma@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Noob Nathan', 'email' => 'nathan@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Pro Gamer Lisa', 'email' => 'lisa@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Casual Chris', 'email' => 'chris@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Hardcore Helen', 'email' => 'helen@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Speedrun Steve', 'email' => 'steve@example.com', 'password' => bcrypt('password')],
                    ['name' => 'Strategy Sam', 'email' => 'sam@example.com', 'password' => bcrypt('password')],
                ]);

                foreach ($additionalUsers as $userData) {
                    if (!User::where('email', $userData['email'])->exists()) {
                        User::create($userData);
                    }
                }
                
                $users = User::all(); // Refresh users collection
            }            // Define sample groups with realistic gaming data
            $groupsData = [
                [
                    'name' => 'Halo Legends Elite',
                    'description' => 'Competitive Halo players looking for ranked matches and tournament play. We focus on team coordination and skill improvement.',
                    'game' => 'halo',
                    'platform' => 'xbox_live',
                    'is_public' => true,
                    'max_members' => 16,
                    'member_count' => 8,
                ],
                [
                    'name' => 'Call of Duty Squad',
                    'description' => 'Casual to competitive COD players. We play various game modes and help each other improve. All skill levels welcome!',
                    'game' => 'call_of_duty',
                    'platform' => 'steam',
                    'is_public' => true,
                    'max_members' => 12,
                    'member_count' => 6,
                ],
                [
                    'name' => 'FIFA Champions League',
                    'description' => 'FIFA enthusiasts who love Ultimate Team and career mode. Regular tournaments and friendly matches.',
                    'game' => 'fifa',
                    'platform' => 'playstation_network',
                    'is_public' => true,
                    'max_members' => 20,
                    'member_count' => 12,
                ],
                [
                    'name' => 'Rocket League Pros',
                    'description' => 'High-level Rocket League players aiming for Grand Champion and beyond. Serious training and ranked matches only.',
                    'game' => 'rocket_league',
                    'platform' => 'steam',
                    'is_public' => false,
                    'max_members' => 10,
                    'member_count' => 5,
                ],
                [
                    'name' => 'Valorant Tactical Team',
                    'description' => 'Strategic Valorant players focused on team composition and tactical gameplay. Looking for dedicated players.',
                    'game' => 'valorant',
                    'platform' => 'battlenet',
                    'is_public' => false,
                    'max_members' => 15,
                    'member_count' => 7,
                ],
                [
                    'name' => 'Minecraft Builders United',
                    'description' => 'Creative Minecraft players who love building massive structures and exploring new worlds together.',
                    'game' => 'minecraft',
                    'platform' => 'cross_platform',
                    'is_public' => true,
                    'max_members' => 25,
                    'member_count' => 15,
                ],
                [
                    'name' => 'Apex Legends Masters',
                    'description' => 'Competitive Apex players grinding to Master and Predator ranks. Team communication and skill required.',
                    'game' => 'apex_legends',
                    'platform' => 'steam',
                    'is_public' => false,
                    'max_members' => 9,
                    'member_count' => 6,
                ],
                [
                    'name' => 'Casual Gaming Club',
                    'description' => 'Relaxed gaming community for players who enjoy various games without the pressure. Fun first!',
                    'game' => null, // No specific game
                    'platform' => 'cross_platform', // Multi-platform
                    'is_public' => true,
                    'max_members' => 50,
                    'member_count' => 22,
                ],
                [
                    'name' => 'Destiny Guardians',
                    'description' => 'Destiny clan focused on raids, nightfalls, and PvP. We help each other with quests and exotic hunts.',
                    'game' => 'destiny',
                    'platform' => 'steam',
                    'is_public' => true,
                    'max_members' => 100,
                    'member_count' => 45,
                ],
                [
                    'name' => 'Fortnite Victory Squad',
                    'description' => 'Fortnite players aiming for Victory Royales! We play all modes and help each other improve building skills.',
                    'game' => 'fortnite',
                    'platform' => 'cross_platform',
                    'is_public' => true,
                    'max_members' => 30,
                    'member_count' => 18,
                ],
            ];

            $createdGroups = [];

            // Create groups
            foreach ($groupsData as $index => $groupData) {
                $owner = $users->random();
                  $group = Group::create([
                    'name' => $groupData['name'],
                    'description' => $groupData['description'],
                    'game' => $groupData['game'],
                    'platform' => $groupData['platform'],
                    'max_members' => $groupData['max_members'],
                    'owner_id' => $owner->id,
                    'is_public' => $groupData['is_public'],
                ]);

                $createdGroups[] = [
                    'group' => $group,
                    'target_member_count' => $groupData['member_count'],
                    'owner' => $owner,
                ];

                $this->command->info("Created group: {$group->name}");
            }

            // Add members to groups
            foreach ($createdGroups as $groupData) {
                $group = $groupData['group'];
                $targetCount = $groupData['target_member_count'];
                $owner = $groupData['owner'];

                // Add owner as admin
                GroupMembership::create([
                    'group_id' => $group->id,
                    'user_id' => $owner->id,
                    'role' => GroupMembership::ROLE_ADMIN,
                    'joined_at' => now()->subDays(rand(1, 30)),
                ]);

                // Add other members
                $availableUsers = $users->where('id', '!=', $owner->id);
                $membersToAdd = $availableUsers->random(min($targetCount - 1, $availableUsers->count()));

                foreach ($membersToAdd as $member) {
                    // Randomly assign roles (mostly members, some moderators)
                    $role = rand(1, 10) <= 8 ? GroupMembership::ROLE_MEMBER : GroupMembership::ROLE_MODERATOR;
                    
                    GroupMembership::create([
                        'group_id' => $group->id,
                        'user_id' => $member->id,
                        'role' => $role,
                        'joined_at' => now()->subDays(rand(1, 25)),
                    ]);
                }

                $this->command->info("Added {$targetCount} members to {$group->name}");
            }

            // Create group invitations
            $this->createGroupInvitations($createdGroups, $users);

            $this->command->info('Groups, memberships, and invitations created successfully!');
        });
    }

    /**
     * Create realistic group invitations
     */
    private function createGroupInvitations($createdGroups, $users)
    {
        $invitationMessages = [
            "Hey! Would you like to join our gaming group? We're always looking for skilled players!",
            "I think you'd be a great addition to our team. Want to join us?",
            "We noticed you're into [GAME]. Join our group for regular matches and fun!",
            "Looking for dedicated players to join our competitive group. Interested?",
            "Join us for some epic gaming sessions! We have a great community.",
            "Your gaming skills would be perfect for our group. Want to team up?",
            "We're recruiting new members for our gaming clan. Join us!",
            "Casual gaming group looking for friendly players. Come join the fun!",
            null, // Some invitations have no message
            null,
        ];

        foreach ($createdGroups as $groupData) {
            $group = $groupData['group'];
            $currentMembers = $group->members()->pluck('user_id')->toArray();
            $availableUsers = $users->whereNotIn('id', $currentMembers);

            // Skip if group is full or no available users
            if ($group->isFull() || $availableUsers->isEmpty()) {
                continue;
            }

            // Create 2-5 invitations per group
            $invitationCount = rand(2, min(5, $availableUsers->count()));
            $usersToInvite = $availableUsers->random($invitationCount);

            foreach ($usersToInvite as $userToInvite) {
                // Get a random current member who can invite (admin or moderator)
                $inviter = $group->members()
                    ->whereIn('role', [GroupMembership::ROLE_ADMIN, GroupMembership::ROLE_MODERATOR])
                    ->inRandomOrder()
                    ->first();

                if (!$inviter) {
                    $inviter = $group->owner; // Fallback to owner
                }

                // Random status for invitations (mostly pending, some accepted/declined)
                $statusRand = rand(1, 10);
                if ($statusRand <= 6) {
                    $status = 'pending';
                } elseif ($statusRand <= 8) {
                    $status = 'accepted';
                } else {
                    $status = 'declined';
                }

                $invitation = GroupInvitation::create([
                    'group_id' => $group->id,
                    'invited_user_id' => $userToInvite->id,
                    'invited_by_user_id' => $inviter->id,
                    'message' => $invitationMessages[array_rand($invitationMessages)],
                    'status' => $status,
                    'created_at' => now()->subDays(rand(0, 7)),
                    'updated_at' => $status !== 'pending' ? now()->subDays(rand(0, 5)) : now()->subDays(rand(0, 7)),
                ]);

                // If invitation was accepted, add user to group
                if ($status === 'accepted') {
                    GroupMembership::create([
                        'group_id' => $group->id,
                        'user_id' => $userToInvite->id,
                        'role' => GroupMembership::ROLE_MEMBER,
                        'joined_at' => $invitation->updated_at,
                    ]);
                }
            }

            $this->command->info("Created {$invitationCount} invitations for {$group->name}");
        }
    }
}
