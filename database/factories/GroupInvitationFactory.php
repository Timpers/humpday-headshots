<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupInvitation>
 */
class GroupInvitationFactory extends Factory
{
    private const RESPONSE_DATE_RANGE = '-1 week';

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'invited_user_id' => User::factory(),
            'invited_by_user_id' => User::factory(),
            'status' => GroupInvitation::STATUS_PENDING,
            'message' => $this->faker->optional(0.6)->sentence(),
            'responded_at' => null,
        ];
    }

    /**
     * Indicate that the invitation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupInvitation::STATUS_PENDING,
            'responded_at' => null,
        ]);
    }

    /**
     * Indicate that the invitation was accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupInvitation::STATUS_ACCEPTED,
            'responded_at' => $this->faker->dateTimeBetween(self::RESPONSE_DATE_RANGE, 'now'),
        ]);
    }

    /**
     * Indicate that the invitation was declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupInvitation::STATUS_DECLINED,
            'responded_at' => $this->faker->dateTimeBetween(self::RESPONSE_DATE_RANGE, 'now'),
        ]);
    }

    /**
     * Indicate that the invitation was cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GroupInvitation::STATUS_CANCELLED,
            'responded_at' => $this->faker->dateTimeBetween(self::RESPONSE_DATE_RANGE, 'now'),
        ]);
    }
}
