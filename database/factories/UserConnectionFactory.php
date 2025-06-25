<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserConnection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UserConnection>
 */
class UserConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'requester_id' => User::factory(),
            'recipient_id' => User::factory(),
            'status' => $this->faker->randomElement([
                UserConnection::STATUS_PENDING,
                UserConnection::STATUS_ACCEPTED,
                UserConnection::STATUS_DECLINED,
            ]),
            'message' => $this->faker->optional(0.6)->sentence(),
            'accepted_at' => function (array $attributes) {
                return $attributes['status'] === UserConnection::STATUS_ACCEPTED
                    ? $this->faker->dateTimeBetween('-1 month', 'now')
                    : null;
            },
        ];
    }

    /**
     * Indicate that the connection is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserConnection::STATUS_PENDING,
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate that the connection is accepted.
     */
    public function accepted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserConnection::STATUS_ACCEPTED,
            'accepted_at' => $this->faker->dateTimeBetween('-1 month', 'now'),
        ]);
    }

    /**
     * Indicate that the connection is declined.
     */
    public function declined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserConnection::STATUS_DECLINED,
            'accepted_at' => null,
        ]);
    }

    /**
     * Indicate that the connection is blocked.
     */
    public function blocked(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => UserConnection::STATUS_BLOCKED,
            'accepted_at' => null,
        ]);
    }
}
