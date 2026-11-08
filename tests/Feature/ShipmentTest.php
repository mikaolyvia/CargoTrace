<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use Database\Seeders\CarrierSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\ShipmentSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ShipmentTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ShipmentService::class);
    }

    public function test_shipment_service_creates_shipment_with_items_and_recalculates_totals(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->service->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Medical Equipment',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(10)->toDateTimeString(),
            'items' => [
                [
                    'item_name' => 'Ultrasound Machine',
                    'quantity' => 2,
                    'weight_kg' => 80.00,
                    'length_cm' => 70.00,
                    'width_cm' => 60.00,
                    'height_cm' => 120.00,
                ],
                [
                    'item_name' => 'Probe Kits',
                    'quantity' => 5,
                    'weight_kg' => 2.00,
                    'length_cm' => 30.00,
                    'width_cm' => 20.00,
                    'height_cm' => 15.00,
                ],
            ],
        ], $operator);

        $this->assertNotNull($shipment);
        $this->assertStringStartsWith('CT-' . date('Ymd') . '-', $shipment->shipment_number);
        $this->assertEquals('CREATED', $shipment->current_status);
        $this->assertEquals($customer->id, $shipment->customer_id);
        $this->assertEquals($operator->id, $shipment->created_by);

        // Verify Weight: (2 * 80) + (5 * 2) = 160 + 10 = 170.00 kg
        $this->assertEquals(170.00, $shipment->total_weight_kg);

        // Verify Volume:
        // Item 1: 2 * (70 * 60 * 120 / 1,000,000) = 2 * 0.504 = 1.008 cbm
        // Item 2: 5 * (30 * 20 * 15 / 1,000,000) = 5 * 0.009 = 0.045 cbm
        // Total = 1.008 + 0.045 = 1.053 cbm
        $this->assertEquals(1.053, $shipment->total_volume_cbm);
        $this->assertCount(2, $shipment->items);
    }

    public function test_shipment_creation_fails_if_customer_role_is_not_customer(): void
    {
        $nonCustomer = User::factory()->create(['role' => 'OPERATOR']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('CUSTOMER');

        $this->service->createShipment([
            'customer_id' => $nonCustomer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Raw Materials',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);
    }

    public function test_nullable_dimensions_default_to_zero_volume_without_error(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->service->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Bulk Grain',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(7)->toDateTimeString(),
            'items' => [
                [
                    'item_name' => 'Grain Sacks',
                    'quantity' => 50,
                    'weight_kg' => 20.00,
                    'length_cm' => null, // Dimensions not measured
                    'width_cm' => null,
                    'height_cm' => null,
                ],
            ],
        ], $operator);

        $this->assertEquals(1000.00, $shipment->total_weight_kg);
        $this->assertEquals(0.00, $shipment->total_volume_cbm);
    }

    public function test_shipment_soft_delete_preserves_historical_records(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = Shipment::factory()->create([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
        ]);

        $shipmentId = $shipment->id;
        $shipment->delete();

        $this->assertNull(Shipment::find($shipmentId));
        $this->assertNotNull(Shipment::withTrashed()->find($shipmentId));
        $this->assertNotNull(Shipment::withTrashed()->find($shipmentId)->deleted_at);
    }

    public function test_shipment_seeder_populates_realistic_shipments(): void
    {
        $this->seed(UserSeeder::class);
        $this->seed(CarrierSeeder::class);
        $this->seed(LocationSeeder::class);
        $this->seed(ShipmentSeeder::class);

        $this->assertDatabaseHas('shipments', [
            'shipment_number' => 'CT-20261109-0001',
            'cargo_type' => 'Industrial Machinery',
            'current_status' => 'CREATED',
        ]);

        $this->assertDatabaseHas('shipments', [
            'shipment_number' => 'CT-20261109-0002',
            'cargo_type' => 'Consumer Electronics',
        ]);

        $this->assertDatabaseHas('shipments', [
            'shipment_number' => 'CT-20261109-0003',
            'cargo_type' => 'Precision Semiconductor Wafers',
        ]);

        $shipment1 = Shipment::where('shipment_number', 'CT-20261109-0001')->first();
        $this->assertEquals(2400.00, $shipment1->total_weight_kg);
        $this->assertEquals(3.84, $shipment1->total_volume_cbm);
        $this->assertCount(2, $shipment1->items);
    }
}
