<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\GroupMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GroupMembership>
 */
class GroupMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'group_id' => Group::factory(),
            'user_id' => User::factory(),
            'role' => GroupMembership::ROLE_MEMBER,
            'joined_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
            'permissions' => $this->faker->optional(0.3)->randomElement([
                ['can_invite' => true, 'can_kick' => false],
                ['can_invite' => false, 'can_kick' => true],
                null
            ]),
        ];
    }

    /**
     * Indicate that the membership is for a member.
     */
    public function member(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => GroupMembership::ROLE_MEMBER,
        ]);
    }

    /**
     * Indicate that the membership is for a moderator.
     */
    public function moderator(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => GroupMembership::ROLE_MODERATOR,
        ]);
    }

    /**
     * Indicate that the membership is for an admin.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => GroupMembership::ROLE_ADMIN,
        ]);
    }

    /**
     * Indicate that the membership is for the owner.
     */
    public function owner(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => GroupMembership::ROLE_OWNER,
        ]);
    }
}
