<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('?????')),
            'name' => fake()->city() . ' Logistics Hub',
            'type' => fake()->randomElement(['PORT', 'AIRPORT', 'WAREHOUSE', 'HUB', 'CUSTOMS']),
            'city' => fake()->city(),
            'country' => fake()->country(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
        ];
    }

    /**
     * Indicate that the location is a maritime port.
     */
    public function port(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'PORT',
        ]);
    }

    /**
     * Indicate that the location is an airport.
     */
    public function airport(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'AIRPORT',
        ]);
    }
}
