<?php

namespace Database\Factories;

use App\Models\Shipment;
use App\Models\ShipmentItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShipmentItem>
 */
class ShipmentItemFactory extends Factory
{
    protected $model = ShipmentItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'shipment_id' => Shipment::factory(),
            'item_name' => fake()->words(3, true),
            'hs_code' => fake()->numerify('####.##.##'),
            'quantity' => fake()->numberBetween(1, 100),
            'weight_kg' => fake()->randomFloat(2, 5, 250),
            'length_cm' => fake()->randomFloat(2, 20, 150),
            'width_cm' => fake()->randomFloat(2, 20, 120),
            'height_cm' => fake()->randomFloat(2, 10, 100),
        ];
    }
}
