<?php

namespace Database\Factories;

use App\Models\GamingSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GamingSession>
 */
class GamingSessionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host_user_id' => User::factory(),
            'title' => $this->faker->sentence(3),
            'description' => $this->faker->paragraph(),
            'game_name' => $this->faker->randomElement([
                'Call of Duty: Black Ops 6',
                'Halo Infinite',
                'Apex Legends',
                'Fortnite',
                'World of Warcraft',
                'League of Legends',
                'Counter-Strike 2',
                'Valorant',
                'Overwatch 2',
                'Minecraft'
            ]),
            'platform' => $this->faker->randomElement(['pc', 'playstation_5', 'xbox_series', 'nintendo_switch']),
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
            'max_participants' => $this->faker->numberBetween(2, 20),
            'privacy' => $this->faker->randomElement([
                GamingSession::PRIVACY_PUBLIC,
                GamingSession::PRIVACY_FRIENDS_ONLY,
                GamingSession::PRIVACY_INVITE_ONLY
            ]),
            'requirements' => $this->faker->optional(0.3)->sentence(),
            'status' => GamingSession::STATUS_SCHEDULED,
        ];
    }

    /**
     * Indicate that the gaming session is scheduled.
     */
    public function scheduled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSession::STATUS_SCHEDULED,
            'scheduled_at' => $this->faker->dateTimeBetween('now', '+1 month'),
        ]);
    }

    /**
     * Indicate that the gaming session is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSession::STATUS_ACTIVE,
            'scheduled_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
        ]);
    }

    /**
     * Indicate that the gaming session is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSession::STATUS_COMPLETED,
            'scheduled_at' => $this->faker->dateTimeBetween('-1 month', '-1 hour'),
        ]);
    }

    /**
     * Indicate that the gaming session is cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSession::STATUS_CANCELLED,
        ]);
    }

    /**
     * Indicate that the gaming session is public.
     */
    public function public(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => GamingSession::PRIVACY_PUBLIC,
        ]);
    }

    /**
     * Indicate that the gaming session is invite only.
     */
    public function inviteOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'privacy' => GamingSession::PRIVACY_INVITE_ONLY,
        ]);
    }
}
