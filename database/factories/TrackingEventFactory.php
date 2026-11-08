<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TrackingEvent>
 */
class TrackingEventFactory extends Factory
{
    protected $model = TrackingEvent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'location_id' => Location::factory(),
            'event_type' => 'IN_TRANSIT',
            'event_time' => now(),
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'description' => fake()->sentence(),
            'weather_condition' => fake()->randomElement(['Clear Sky', 'Scattered Clouds', 'Light Rain', 'Moderate Breeze']),
            'weather_temp_c' => fake()->randomFloat(2, 15, 33),
            'created_by' => User::factory()->create(['role' => 'OPERATOR'])->id,
        ];
    }
}
