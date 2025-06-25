<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Game;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class GameCompatibilitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating sample users and games for compatibility testing...');

        // Create sample users
        $users = [
            [
                'name' => 'Alice Gamer',
                'email' => 'alice.gamer@example.com',
                'password' => Hash::make('password'),
                'games' => [
                    ['name' => 'Call of Duty: Modern Warfare', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.5, 'is_favorite' => true, 'genres' => [['name' => 'Shooter'], ['name' => 'Action']]],
                    ['name' => 'Valorant', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 9.0, 'is_favorite' => true, 'genres' => [['name' => 'Shooter'], ['name' => 'Tactical']]],
                    ['name' => 'Minecraft', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.5, 'genres' => [['name' => 'Sandbox'], ['name' => 'Survival']]],
                    ['name' => 'The Witcher 3', 'platform' => 'playstation_5', 'status' => Game::STATUS_COMPLETED, 'user_rating' => 9.5, 'is_favorite' => true, 'genres' => [['name' => 'RPG'], ['name' => 'Adventure']]],
                    ['name' => 'Rocket League', 'platform' => 'steam', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.0, 'genres' => [['name' => 'Sports'], ['name' => 'Arcade']]],
                ]
            ],
            [
                'name' => 'Bob Strategy',
                'email' => 'bob.strategy@example.com',
                'password' => Hash::make('password'),
                'games' => [
                    ['name' => 'Call of Duty: Modern Warfare', 'platform' => 'xbox_series', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.5, 'genres' => [['name' => 'Shooter'], ['name' => 'Action']]],
                    ['name' => 'Minecraft', 'platform' => 'nintendo_switch', 'status' => Game::STATUS_OWNED, 'user_rating' => 9.0, 'is_favorite' => true, 'genres' => [['name' => 'Sandbox'], ['name' => 'Survival']]],
                    ['name' => 'Civilization VI', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.5, 'is_favorite' => true, 'genres' => [['name' => 'Strategy'], ['name' => 'Turn-based']]],
                    ['name' => 'Age of Empires IV', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.0, 'genres' => [['name' => 'Strategy'], ['name' => 'RTS']]],
                    ['name' => 'Total War: Warhammer III', 'platform' => 'steam', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.8, 'genres' => [['name' => 'Strategy'], ['name' => 'Turn-based']]],
                    ['name' => 'Factorio', 'platform' => 'pc', 'status' => Game::STATUS_PLAYING, 'user_rating' => 9.2, 'is_favorite' => true, 'genres' => [['name' => 'Simulation'], ['name' => 'Building']]],
                ]
            ],
            [
                'name' => 'Charlie RPG',
                'email' => 'charlie.rpg@example.com',
                'password' => Hash::make('password'),
                'games' => [
                    ['name' => 'The Witcher 3', 'platform' => 'pc', 'status' => Game::STATUS_COMPLETED, 'user_rating' => 9.9, 'is_favorite' => true, 'genres' => [['name' => 'RPG'], ['name' => 'Adventure']]],
                    ['name' => 'Cyberpunk 2077', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.0, 'genres' => [['name' => 'RPG'], ['name' => 'Action']]],
                    ['name' => 'Elden Ring', 'platform' => 'playstation_5', 'status' => Game::STATUS_PLAYING, 'user_rating' => 9.5, 'is_favorite' => true, 'genres' => [['name' => 'RPG'], ['name' => 'Souls-like']]],
                    ['name' => 'Baldur\'s Gate 3', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 9.8, 'is_favorite' => true, 'genres' => [['name' => 'RPG'], ['name' => 'Turn-based']]],
                    ['name' => 'Mass Effect Legendary Edition', 'platform' => 'steam', 'status' => Game::STATUS_COMPLETED, 'user_rating' => 9.0, 'genres' => [['name' => 'RPG'], ['name' => 'Sci-Fi']]],
                    ['name' => 'Rocket League', 'platform' => 'epic_games', 'status' => Game::STATUS_OWNED, 'user_rating' => 6.5, 'genres' => [['name' => 'Sports'], ['name' => 'Arcade']]],
                ]
            ],
            [
                'name' => 'Diana Casual',
                'email' => 'diana.casual@example.com',
                'password' => Hash::make('password'),
                'games' => [
                    ['name' => 'Animal Crossing: New Horizons', 'platform' => 'nintendo_switch', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.5, 'is_favorite' => true, 'genres' => [['name' => 'Simulation'], ['name' => 'Life Sim']]],
                    ['name' => 'Stardew Valley', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 9.0, 'is_favorite' => true, 'genres' => [['name' => 'Simulation'], ['name' => 'Farming']]],
                    ['name' => 'Minecraft', 'platform' => 'pc', 'status' => Game::STATUS_OWNED, 'user_rating' => 8.8, 'is_favorite' => true, 'genres' => [['name' => 'Sandbox'], ['name' => 'Survival']]],
                    ['name' => 'Among Us', 'platform' => 'mobile', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.0, 'genres' => [['name' => 'Social Deduction'], ['name' => 'Party']]],
                    ['name' => 'Fall Guys', 'platform' => 'steam', 'status' => Game::STATUS_OWNED, 'user_rating' => 7.5, 'genres' => [['name' => 'Battle Royale'], ['name' => 'Party']]],
                ]
            ]
        ];

        foreach ($users as $userData) {
            // Skip if user already exists
            if (User::where('email', $userData['email'])->exists()) {
                $this->command->info("User {$userData['email']} already exists, skipping...");
                continue;
            }

            $user = User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => $userData['password'],
            ]);

            $this->command->info("Created user: {$user->name}");

            // Create games for this user
            foreach ($userData['games'] as $gameData) {
                Game::create(array_merge($gameData, [
                    'user_id' => $user->id,
                    'igdb_id' => rand(1000, 99999), // Fake IGDB ID for testing
                    'summary' => 'Sample game for compatibility testing',
                    'cover' => null,
                    'screenshots' => null,
                    'platforms' => [['name' => ucfirst($gameData['platform'])]],
                    'rating' => rand(70, 95),
                    'release_date' => '2023-01-01',
                    'date_purchased' => '2023-06-15',
                    'price_paid' => rand(20, 60),
                    'is_digital' => true,
                    'is_completed' => $gameData['status'] === Game::STATUS_COMPLETED,
                    'hours_played' => rand(10, 200),
                ]));
            }

            $this->command->info("Created {$user->games->count()} games for {$user->name}");
        }

        $this->command->info('Sample users and games created successfully!');
        $this->command->info('You can now test the game compatibility feature!');
    }
}
