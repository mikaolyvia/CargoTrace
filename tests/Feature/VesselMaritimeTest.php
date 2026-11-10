<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vessel;
use App\Models\VesselPosition;
use Tests\TestCase;

class VesselMaritimeTest extends TestCase
{
    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $originPort;
    protected Location $destinationPort;
    protected Vessel $vessel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->operator = User::firstOrCreate(
            ['email' => 'op_maritime_test@cargotrace.com'],
            ['name' => 'Operator Maritime', 'role' => 'OPERATOR', 'password' => bcrypt('password')]
        );

        $this->customer = User::firstOrCreate(
            ['email' => 'cust_maritime_test@cargotrace.com'],
            ['name' => 'Customer Maritime', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->carrier = Carrier::firstOrCreate(
            ['code' => 'OC-MSC'],
            ['name' => 'Mediterranean Shipping Company', 'type' => 'SEA', 'is_active' => true]
        );

        $this->originPort = Location::firstOrCreate(
            ['code' => 'MAR-JKT'],
            [
                'name' => 'Port of Tanjung Priok',
                'type' => 'PORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1018000,
                'longitude' => 106.8833000,
            ]
        );

        $this->destinationPort = Location::firstOrCreate(
            ['code' => 'MAR-SIN'],
            [
                'name' => 'Port of Singapore',
                'type' => 'PORT',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.2644000,
                'longitude' => 103.8222000,
            ]
        );

        $this->vessel = Vessel::firstOrCreate(
            ['imo_number' => '9839438'],
            [
                'carrier_id' => $this->carrier->id,
                'name' => 'MSC Gülsün',
                'mmsi' => '353083000',
                'flag_country' => 'Panama',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 23756,
                'status' => 'UNDERWAY',
                'last_latitude' => 1.2500000,
                'last_longitude' => 104.1000000,
                'last_speed_knots' => 14.8,
                'last_heading_degrees' => 60.0,
                'last_position_at' => now(),
            ]
        );
    }

    public function test_vessel_seeder_populates_major_global_vessels(): void
    {
        $this->artisan('db:seed', ['--class' => 'VesselSeeder']);

        $this->assertDatabaseHas('vessels', ['imo_number' => '9778791', 'name' => 'Madrid Maersk']);
        $this->assertDatabaseHas('vessels', ['imo_number' => '9811000', 'name' => 'Ever Given']);
        $this->assertDatabaseHas('vessels', ['imo_number' => '9839438', 'name' => 'MSC Gülsün']);
    }

    public function test_shipment_links_with_vessel_and_container_number(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-MAR-001',
            'container_number' => 'MSCU-9021844',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'vessel_id' => $this->vessel->id,
            'origin_id' => $this->originPort->id,
            'destination_id' => $this->destinationPort->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 12000,
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
            'current_status' => 'BOOKED',
            'created_by' => $this->operator->id,
        ]);

        $this->assertEquals($this->vessel->id, $shipment->vessel_id);
        $this->assertEquals('MSC Gülsün', $shipment->vessel->name);
        $this->assertEquals('MSCU-9021844', $shipment->container_number);
    }

    public function test_vessel_api_index_and_show_endpoints(): void
    {
        $response = $this->actingAs($this->operator, 'sanctum')
            ->getJson('/api/vessels');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'data' => [
                    '*' => ['id', 'name', 'imo_number', 'mmsi', 'flag_country', 'capacity_teu', 'status'],
                ],
            ],
        ]);

        $detailResponse = $this->actingAs($this->operator, 'sanctum')
            ->getJson("/api/vessels/{$this->vessel->id}");

        $detailResponse->assertStatus(200);
        $detailResponse->assertJsonPath('data.imo_number', '9839438');
    }

    public function test_ais_integration_endpoint_records_position_and_propagates_to_active_shipments(): void
    {
        // Create an IN_TRANSIT shipment assigned to this vessel
        $shipment = Shipment::create([
            'shipment_number' => 'CT-PROPAGATE-001',
            'container_number' => 'MSCU-1234567',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'vessel_id' => $this->vessel->id,
            'origin_id' => $this->originPort->id,
            'destination_id' => $this->destinationPort->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 5000,
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(2),
            'estimated_arrival' => now()->addDays(2),
            'current_status' => 'IN_TRANSIT',
            'created_by' => $this->operator->id,
        ]);

        $telemetry = [
            'imo_number' => '9839438',
            'latitude' => 1.3500000,
            'longitude' => 104.3000000,
            'speed_knots' => 16.5,
            'heading_degrees' => 45.0,
            'status' => 'UNDERWAY',
            'source' => 'PYTHON_AIS_COLLECTOR',
            'recorded_at' => now()->toIso8601String(),
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson('/api/integrations/vessel-positions', $telemetry);

        $response->assertStatus(201);
        $response->assertJsonPath('data.propagated_shipments', 1);

        // Verify vessel position record created
        $this->assertDatabaseHas('vessel_positions', [
            'vessel_id' => $this->vessel->id,
            'latitude' => 1.3500000,
            'longitude' => 104.3000000,
        ]);

        // Verify tracking event auto-recorded on the active shipment
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $shipment->id,
            'event_type' => 'IN_TRANSIT',
            'latitude' => 1.3500000,
            'longitude' => 104.3000000,
        ]);
    }

    public function test_ais_integration_detects_and_ignores_duplicate_coordinates(): void
    {
        $this->vessel->update([
            'last_latitude' => 1.3500000,
            'last_longitude' => 104.3000000,
            'last_position_at' => now(),
        ]);

        $duplicatePayload = [
            'imo_number' => '9839438',
            'latitude' => 1.3500000,
            'longitude' => 104.3000000,
            'speed_knots' => 16.5,
            'heading_degrees' => 45.0,
            'recorded_at' => now()->toIso8601String(),
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson('/api/integrations/vessel-positions', $duplicatePayload);

        $response->assertStatus(200);
        $response->assertJsonPath('data.is_duplicate', true);
    }
}
