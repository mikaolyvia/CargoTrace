<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentException>
 */
class ShipmentExceptionFactory extends Factory
{
    protected $model = ShipmentException::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'tracking_event_id' => null,
            'exception_type' => fake()->randomElement(ShipmentException::VALID_TYPES),
            'severity' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH', 'CRITICAL']),
            'status' => ShipmentException::STATUS_OPEN,
            'impact_hours' => fake()->randomFloat(2, 4, 36),
            'description' => fake()->sentence(),
            'detected_at' => now(),
            'resolved_at' => null,
            'resolution_notes' => null,
            'reported_by' => User::factory()->create(['role' => 'OPERATOR'])->id,
            'resolved_by' => null,
        ];
    }

    /**
     * Indicate that the exception is resolved.
     */
    public function resolved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ShipmentException::STATUS_RESOLVED,
            'resolved_at' => now(),
            'resolved_by' => User::factory()->create(['role' => 'OPERATOR'])->id,
            'resolution_notes' => 'Issue verified and cleared by customs authority.',
        ]);
    }
}
