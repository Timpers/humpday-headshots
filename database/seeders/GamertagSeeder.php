<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Gamertag;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GamertagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create some users if they don't exist
        if (User::count() === 0) {
            User::factory(5)->create();
        }

        $users = User::all();
        $platforms = ['steam', 'xbox_live', 'playstation_network', 'nintendo_online', 'battlenet'];

        foreach ($users as $user) {
            // Each user gets 2-4 random gamertags
            $numberOfGamertags = rand(2, 4);
            $userPlatforms = collect($platforms)->shuffle()->take($numberOfGamertags);

            foreach ($userPlatforms as $platform) {
                Gamertag::factory()
                    ->for($user)
                    ->platform($platform)
                    ->primary() // Make each gamertag primary for its platform
                    ->create();

                // 30% chance to add a secondary gamertag for the same platform
                if (rand(1, 100) <= 30) {
                    Gamertag::factory()
                        ->for($user)
                        ->platform($platform)
                        ->create(); // Not primary
                }
            }
        }
    }
}
