<?php

namespace Database\Factories;

use App\Models\GamingSession;
use App\Models\GamingSessionMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GamingSessionMessage>
 */
class GamingSessionMessageFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = GamingSessionMessage::class;

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
            'message' => $this->faker->realText(200),
            'created_at' => $this->faker->dateTimeBetween('-1 hour', 'now'),
            'updated_at' => function (array $attributes) {
                return $attributes['created_at'];
            },
        ];
    }

    /**
     * Create a message with short content.
     */
    public function short(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'message' => $this->faker->sentence(),
            ];
        });
    }

    /**
     * Create a message with long content.
     */
    public function long(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'message' => $this->faker->realText(500),
            ];
        });
    }

    /**
     * Create a message from a specific user.
     */
    public function fromUser(User $user): Factory
    {
        return $this->state(function (array $attributes) use ($user) {
            return [
                'user_id' => $user->id,
            ];
        });
    }

    /**
     * Create a message in a specific session.
     */
    public function inSession(GamingSession $session): Factory
    {
        return $this->state(function (array $attributes) use ($session) {
            return [
                'gaming_session_id' => $session->id,
            ];
        });
    }

    /**
     * Create a message created at a specific time.
     */
    public function createdAt($time): Factory
    {
        return $this->state(function (array $attributes) use ($time) {
            return [
                'created_at' => $time,
                'updated_at' => $time,
            ];
        });
    }
}
