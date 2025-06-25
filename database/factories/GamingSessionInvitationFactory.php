<?php

namespace Database\Factories;

use App\Models\GamingSession;
use App\Models\GamingSessionInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GamingSessionInvitation>
 */
class GamingSessionInvitationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'gaming_session_id' => GamingSession::factory(),
            'invited_by_user_id' => User::factory(),
            'invited_user_id' => User::factory(),
            'invited_group_id' => null, // Default to user invitation
            'status' => $this->faker->randomElement([
                GamingSessionInvitation::STATUS_PENDING,
                GamingSessionInvitation::STATUS_ACCEPTED,
                GamingSessionInvitation::STATUS_DECLINED,
            ]),
            'message' => $this->faker->optional()->sentence(),
        ];
    }

    /**
     * Indicate that the invitation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSessionInvitation::STATUS_PENDING,
        ]);
    }

    /**
     * Indicate that the invitation is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSessionInvitation::STATUS_ACCEPTED,
        ]);
    }

    /**
     * Indicate that the invitation is declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSessionInvitation::STATUS_DECLINED,
        ]);
    }

    /**
     * Indicate that the invitation is for a group.
     */
    public function forGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_group_id' => \App\Models\Group::factory(),
            'invited_user_id' => null,
        ]);
    }

    /**
     * Indicate that the invitation is for a user.
     */
    public function forUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'invited_user_id' => User::factory(),
            'invited_group_id' => null,
        ]);
    }
}
