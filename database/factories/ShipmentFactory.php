<?php

namespace Database\Factories;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Shipment>
 */
class ShipmentFactory extends Factory
{
    protected $model = Shipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $dep = fake()->dateTimeBetween('+1 days', '+3 days');
        $arr = (clone $dep)->modify('+14 days');

        return [
            'shipment_number' => 'CT-' . date('Ymd') . '-' . fake()->unique()->numerify('####'),
            'customer_id' => User::factory()->create(['role' => 'CUSTOMER'])->id,
            'created_by' => User::factory()->create(['role' => 'OPERATOR'])->id,
            'carrier_id' => Carrier::factory(),
            'origin_id' => Location::factory()->port(),
            'destination_id' => Location::factory()->port(),
            'current_location_id' => null,
            'cargo_type' => fake()->randomElement(['Electronics', 'Textiles', 'Automotive Parts', 'Industrial Machinery', 'Perishable Goods']),
            'total_weight_kg' => 0.00,
            'total_volume_cbm' => 0.00,
            'current_status' => 'CREATED',
            'expected_departure' => $dep,
            'expected_arrival' => $arr,
            'estimated_arrival' => $arr,
            'actual_arrival' => null,
            'is_delayed' => false,
            'delay_hours' => 0.00,
            'delay_severity' => 'NONE',
        ];
    }
}
