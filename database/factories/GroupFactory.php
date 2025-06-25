<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Group>
 */
class GroupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(3, true),
            'description' => $this->faker->optional(0.8)->paragraph(),
            'game' => $this->faker->randomElement(array_keys(Group::POPULAR_GAMES)),
            'platform' => $this->faker->randomElement(array_keys(Group::PLATFORMS)),
            'owner_id' => User::factory(),
            'is_public' => $this->faker->boolean(70), // 70% chance of being public
            'max_members' => $this->faker->numberBetween(5, 50),
            'avatar' => $this->faker->optional(0.3)->imageUrl(200, 200, 'cats'),
            'settings' => $this->faker->optional(0.5)->randomElement([
                ['notifications' => true, 'auto_approve' => false],
                ['notifications' => false, 'auto_approve' => true],
                null
            ]),
        ];
    }

    /**
     * Indicate that the group is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    /**
     * Indicate that the group is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Set the group for a specific game.
     */
    public function forGame(string $game): static
    {
        return $this->state(fn (array $attributes) => [
            'game' => $game,
        ]);
    }

    /**
     * Set the group for a specific platform.
     */
    public function forPlatform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
        ]);
    }

    /**
     * Create a small group.
     */
    public function small(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_members' => $this->faker->numberBetween(5, 10),
        ]);
    }

    /**
     * Create a large group.
     */
    public function large(): static
    {
        return $this->state(fn (array $attributes) => [
            'max_members' => $this->faker->numberBetween(30, 50),
        ]);
    }
}
