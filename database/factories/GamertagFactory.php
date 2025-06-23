<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gamertag>
 */
class GamertagFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $platforms = ['steam', 'xbox_live', 'playstation_network', 'nintendo_online', 'battlenet'];
        $platform = $this->faker->randomElement($platforms);
        
        return [
            'platform' => $platform,
            'gamertag' => $this->generateGamertagForPlatform($platform),
            'display_name' => $this->faker->optional(0.7)->userName(),
            'is_public' => $this->faker->boolean(80), // 80% chance of being public
            'is_primary' => false, // Set to false by default, can be overridden
            'additional_data' => $this->faker->optional(0.3)->randomElements([
                'level' => $this->faker->numberBetween(1, 100),
                'achievements' => $this->faker->numberBetween(0, 500),
                'join_date' => $this->faker->date(),
            ]),
        ];
    }

    /**
     * Generate a realistic gamertag for the given platform.
     */
    private function generateGamertagForPlatform(string $platform): string
    {
        return match ($platform) {
            'steam' => $this->faker->userName() . $this->faker->optional(0.5)->numerify('###'),
            'xbox_live' => $this->faker->userName() . $this->faker->optional(0.6)->numerify('##'),
            'playstation_network' => $this->faker->userName() . $this->faker->optional(0.4)->numerify('_##'),
            'nintendo_online' => $this->faker->firstName() . $this->faker->optional(0.7)->numerify('###'),
            'battlenet' => $this->faker->userName() . '#' . $this->faker->numerify('####'),
            default => $this->faker->userName(),
        };
    }

    /**
     * Indicate that the gamertag is primary for its platform.
     */
    public function primary(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_primary' => true,
        ]);
    }

    /**
     * Indicate that the gamertag is private.
     */
    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    /**
     * Set the platform for the gamertag.
     */
    public function platform(string $platform): static
    {
        return $this->state(fn (array $attributes) => [
            'platform' => $platform,
            'gamertag' => $this->generateGamertagForPlatform($platform),
        ]);
    }
}
