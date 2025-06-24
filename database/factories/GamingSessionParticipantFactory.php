<?php

namespace Database\Factories;

use App\Models\GamingSession;
use App\Models\GamingSessionParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GamingSessionParticipant>
 */
class GamingSessionParticipantFactory extends Factory
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
            'user_id' => User::factory(),
            'status' => $this->faker->randomElement([
                GamingSessionParticipant::STATUS_JOINED,
                GamingSessionParticipant::STATUS_LEFT,
            ]),
            'joined_at' => now(),
            'left_at' => function (array $attributes) {
                return $attributes['status'] === GamingSessionParticipant::STATUS_LEFT ? now() : null;
            },
        ];
    }

    /**
     * Indicate that the participant has joined.
     */
    public function joined(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSessionParticipant::STATUS_JOINED,
        ]);
    }

    /**
     * Indicate that the participant has left.
     */
    public function left(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => GamingSessionParticipant::STATUS_LEFT,
        ]);
    }
}
