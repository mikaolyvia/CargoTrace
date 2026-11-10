<?php

namespace Tests\Feature\Web;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\TrackingService;
use Tests\TestCase;

class TrackingWebTest extends TestCase
{
    protected User $operator;
    protected User $customer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;
    protected Shipment $shipment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');

        $this->operator = User::firstOrCreate(
            ['email' => 'operator_map_test@cargotrace.com'],
            ['name' => 'Operator Test', 'role' => 'OPERATOR', 'password' => bcrypt('password')]
        );

        $this->customer = User::firstOrCreate(
            ['email' => 'customer_map_test@cargotrace.com'],
            ['name' => 'Customer Test', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->carrier = Carrier::firstOrCreate(
            ['code' => 'OC-MAP'],
            ['name' => 'Ocean Express', 'type' => 'SEA', 'is_active' => true]
        );

        $this->origin = Location::firstOrCreate(
            ['code' => 'MAP-ORG'],
            [
                'name' => 'Tanjung Priok Port',
                'type' => 'PORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1000000,
                'longitude' => 106.8800000,
            ]
        );

        $this->destination = Location::firstOrCreate(
            ['code' => 'MAP-DST'],
            [
                'name' => 'Rotterdam Port',
                'type' => 'PORT',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'latitude' => 51.9500000,
                'longitude' => 4.1400000,
            ]
        );

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-MAP-TEST-001',
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 1500,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
            'current_status' => 'IN_TRANSIT',
            'created_by' => $this->operator->id,
        ]);
    }

    public function test_tracking_map_page_loads_successfully(): void
    {
        $response = $this->get('/tracking-map');

        $response->assertStatus(200);
        $response->assertSee('CargoTrace', false);
        $response->assertSee('id="map"', false);
        $response->assertSee('OpenStreetMap', false);
        $response->assertSee('Open-Meteo', false);
    }

    public function test_tracking_route_aliases_work(): void
    {
        $this->get('/tracking')->assertStatus(200);
        $this->get('/track')->assertStatus(200);
    }

    public function test_route_points_provide_rich_data_for_leaflet_popups(): void
    {
        // Record intermediate event with weather
        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'event_type' => 'IN_TRANSIT',
            'event_time' => now()->subDay(),
            'location_id' => $this->origin->id,
            'latitude' => -5.5000000,
            'longitude' => 105.5000000,
            'description' => 'Sailing through Sunda Strait',
            'weather_condition' => 'Partly cloudy',
            'weather_temp_c' => 28.5,
            'created_by' => $this->operator->id,
        ]);

        /** @var TrackingService $trackingService */
        $trackingService = app(TrackingService::class);
        $points = $trackingService->getMapRoutePoints($this->shipment);

        $this->assertCount(3, $points); // Origin, Checkpoint, Destination

        // Checkpoint point verification for PPT Slide 09: Status, Waktu, Lokasi, Cuaca
        $checkpoint = $points[1];
        $this->assertEquals('CHECKPOINT', $checkpoint['type']);
        $this->assertEquals('IN_TRANSIT', $checkpoint['status']);
        $this->assertNotNull($checkpoint['event_time']);
        $this->assertNotNull($checkpoint['location_name']);
        $this->assertEquals('Partly cloudy', $checkpoint['weather_condition']);
        $this->assertEquals(28.5, $checkpoint['weather_temp_c']);
    }

    public function test_api_tracking_endpoint_provides_popup_attributes_to_client(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson("/api/shipments/{$this->shipment->id}/tracking");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'shipment_id',
                'shipment_number',
                'current_status',
                'route_points' => [
                    '*' => [
                        'lat',
                        'lng',
                        'label',
                        'type',
                        'status',
                        'location_name',
                        'event_time',
                        'weather_condition',
                        'weather_temp_c',
                    ],
                ],
                'timeline',
            ],
        ]);
    }

    public function test_tracking_map_supports_multi_criteria_filters(): void
    {
        $response = $this->get('/tracking-map?status=IN_TRANSIT&country=Indonesia');

        $response->assertStatus(200);
        $response->assertSee('Dipantau', false);
        $response->assertSee('Tanjung Priok Port', false);
        $response->assertSee('IN_TRANSIT', false);
    }

    public function test_tracking_map_scopes_shipments_strictly_for_customer_role(): void
    {
        $otherCustomer = User::factory()->create(['role' => 'CUSTOMER']);
        $otherShipment = Shipment::create([
            'shipment_number' => 'CT-SECRET-OTHER-99',
            'customer_id' => $otherCustomer->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'Secret Customer Freight',
            'current_status' => 'IN_TRANSIT',
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(10),
            'estimated_arrival' => now()->addDays(10),
        ]);

        // When $this->customer logs in and visits /tracking-map
        $response = $this->actingAs($this->customer)->get('/tracking-map');

        $response->assertStatus(200);
        // Can see own shipment
        $response->assertSee($this->shipment->shipment_number, false);
        // CANNOT see other customer's shipment
        $response->assertDontSee('CT-SECRET-OTHER-99', false);
    }

    public function test_tracking_map_export_csv_downloads_streamed_report(): void
    {
        $response = $this->get("/tracking-map/export?code={$this->shipment->shipment_number}");

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('CARGOTRACE MARITIME SHIPMENT REPORT', $response->streamedContent());
        $this->assertStringContainsString($this->shipment->shipment_number, $response->streamedContent());
    }

    public function test_tracking_map_simulate_ais_advances_vessel_and_records_telemetry(): void
    {
        $vessel = \App\Models\Vessel::create([
            'carrier_id' => $this->carrier->id,
            'name' => 'Atlantic Mariner',
            'imo_number' => '9988112',
            'status' => 'UNDERWAY',
            'last_latitude' => -5.0,
            'last_longitude' => 105.0,
        ]);

        $this->shipment->update(['vessel_id' => $vessel->id]);

        $response = $this->post('/tracking-map/simulate-ais', [
            'shipment_id' => $this->shipment->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('vessels', [
            'id' => $vessel->id,
            'status' => 'UNDERWAY',
        ]);

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'event_type' => 'IN_TRANSIT',
        ]);
    }
}
