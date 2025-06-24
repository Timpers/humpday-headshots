<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Gamertag;

class SampleUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create sample users with gamertags
        $users = [
            [
                'name' => 'Alice Gamer',
                'email' => 'alice@example.com',
                'password' => Hash::make('password'),
                'gamertags' => [
                    ['platform' => 'steam', 'gamertag' => 'AliceGaming2024', 'display_name' => 'Alice', 'is_primary' => true, 'is_public' => true],
                    ['platform' => 'playstation_network', 'gamertag' => 'AlicePS5', 'display_name' => 'Alice PS5', 'is_primary' => false, 'is_public' => true],
                ]
            ],
            [
                'name' => 'Bob Player',
                'email' => 'bob@example.com',
                'password' => Hash::make('password'),
                'gamertags' => [
                    ['platform' => 'xbox_live', 'gamertag' => 'BobXboxGamer', 'display_name' => 'Bob X', 'is_primary' => true, 'is_public' => true],
                    ['platform' => 'nintendo_online', 'gamertag' => 'BobSwitch', 'display_name' => 'Bob Nintendo', 'is_primary' => false, 'is_public' => true],
                ]
            ],
            [
                'name' => 'Charlie Pro',
                'email' => 'charlie@example.com',
                'password' => Hash::make('password'),
                'gamertags' => [
                    ['platform' => 'steam', 'gamertag' => 'CharliePCMaster', 'display_name' => 'Charlie', 'is_primary' => true, 'is_public' => true],
                    ['platform' => 'battlenet', 'gamertag' => 'CharlieOnBnet', 'display_name' => 'Charlie Battle', 'is_primary' => false, 'is_public' => true],
                ]
            ],
            [
                'name' => 'Diana Console',
                'email' => 'diana@example.com',
                'password' => Hash::make('password'),
                'gamertags' => [
                    ['platform' => 'playstation_network', 'gamertag' => 'DianaPS5Pro', 'display_name' => 'Diana', 'is_primary' => true, 'is_public' => true],
                    ['platform' => 'steam', 'gamertag' => 'DianaOnSteam', 'display_name' => 'Diana Steam', 'is_primary' => false, 'is_public' => false],
                ]
            ],
            [
                'name' => 'Eva Mobile',
                'email' => 'eva@example.com',
                'password' => Hash::make('password'),
                'gamertags' => [
                    ['platform' => 'nintendo_online', 'gamertag' => 'EvaMobileGamer', 'display_name' => 'Eva', 'is_primary' => true, 'is_public' => true],
                    ['platform' => 'xbox_live', 'gamertag' => 'EvaXbox', 'display_name' => 'Eva Xbox', 'is_primary' => false, 'is_public' => true],
                ]
            ],
        ];

        foreach ($users as $userData) {
            // Skip if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                continue;
            }

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ]);

            foreach ($userData['gamertags'] as $gamertagData) {
                Gamertag::create(array_merge($gamertagData, ['user_id' => $user->id]));
            }
        }

        $this->command->info('Sample users and gamertags created successfully!');
    }
}
