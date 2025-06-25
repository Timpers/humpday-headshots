<?php

namespace Database\Factories;

use App\Models\Game;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Game>
 */
class GameFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'igdb_id' => $this->faker->optional(0.7)->numberBetween(1000, 999999),
            'name' => $this->faker->words(3, true),
            'summary' => $this->faker->optional(0.8)->paragraph(),
            'slug' => $this->faker->slug(),
            'cover' => $this->faker->optional(0.6)->randomElement([
                ['url' => '//images.igdb.com/igdb/image/upload/t_thumb/example.jpg'],
                null
            ]),
            'screenshots' => $this->faker->optional(0.4)->randomElement([
                [
                    ['url' => '//images.igdb.com/igdb/image/upload/t_screenshot_med/shot1.jpg'],
                    ['url' => '//images.igdb.com/igdb/image/upload/t_screenshot_med/shot2.jpg']
                ],
                null
            ]),
            'release_date' => $this->faker->optional(0.8)->dateTimeBetween('-10 years', '+1 year'),
            'genres' => $this->faker->optional(0.7)->randomElement([
                [['name' => 'Action'], ['name' => 'Adventure']],
                [['name' => 'RPG'], ['name' => 'Fantasy']],
                [['name' => 'Strategy'], ['name' => 'Simulation']],
                [['name' => 'Sports']],
                null
            ]),
            'platforms' => $this->faker->optional(0.7)->randomElement([
                [['name' => 'PC']],
                [['name' => 'PlayStation 5'], ['name' => 'PC']],
                [['name' => 'Xbox Series X/S']],
                null
            ]),
            'rating' => $this->faker->optional(0.6)->randomFloat(1, 0, 10),
            'status' => $this->faker->randomElement(array_keys(Game::STATUSES)),
            'platform' => $this->faker->randomElement(array_keys(Game::PLATFORMS)),
            'user_rating' => $this->faker->optional(0.5)->randomFloat(1, 0, 10),
            'notes' => $this->faker->optional(0.3)->sentence(),
            'hours_played' => $this->faker->optional(0.4)->numberBetween(1, 500),
            'date_purchased' => $this->faker->optional(0.6)->dateTimeBetween('-2 years', 'now'),
            'price_paid' => $this->faker->optional(0.6)->randomFloat(2, 0, 99.99),
            'is_digital' => $this->faker->boolean(70), // 70% chance of being digital
            'is_completed' => $this->faker->boolean(30), // 30% chance of being completed
            'is_favorite' => $this->faker->boolean(20), // 20% chance of being favorite
        ];
    }

    /**
     * Indicate that the game is owned.
     */
    public function owned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Game::STATUS_OWNED,
        ]);
    }

    /**
     * Indicate that the game is on wishlist.
     */
    public function wishlist(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Game::STATUS_WISHLIST,
        ]);
    }

    /**
     * Indicate that the game is currently being played.
     */
    public function playing(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Game::STATUS_PLAYING,
        ]);
    }

    /**
     * Indicate that the game is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Game::STATUS_COMPLETED,
            'is_completed' => true,
        ]);
    }

    /**
     * Indicate that the game is a favorite.
     */
    public function favorite(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_favorite' => true,
        ]);
    }

    /**
     * Indicate that the game is digital.
     */
    public function digital(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_digital' => true,
        ]);
    }

    /**
     * Set the platform for the game.
     */
    public function platform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }
}
