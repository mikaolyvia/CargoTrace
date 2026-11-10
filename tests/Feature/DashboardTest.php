<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\DashboardService;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    protected User $admin;
    protected User $operator;
    protected User $customerA;
    protected User $customerB;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->admin = User::firstOrCreate(
            ['email' => 'admin_dash_test@cargotrace.com'],
            ['name' => 'Admin Dash', 'role' => 'ADMIN', 'password' => bcrypt('password')]
        );

        $this->operator = User::firstOrCreate(
            ['email' => 'operator_dash_test@cargotrace.com'],
            ['name' => 'Operator Dash', 'role' => 'OPERATOR', 'password' => bcrypt('password')]
        );

        $this->customerA = User::firstOrCreate(
            ['email' => 'customer_a_dash@cargotrace.com'],
            ['name' => 'Customer A', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->customerB = User::firstOrCreate(
            ['email' => 'customer_b_dash@cargotrace.com'],
            ['name' => 'Customer B', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->carrier = Carrier::firstOrCreate(
            ['code' => 'DASH-CARRIER'],
            ['name' => 'Dash Logistics', 'type' => 'SEA', 'is_active' => true]
        );

        $this->origin = Location::firstOrCreate(
            ['code' => 'DASH-ORG'],
            [
                'name' => 'Origin Port',
                'type' => 'PORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1000000,
                'longitude' => 106.8800000,
            ]
        );

        $this->destination = Location::firstOrCreate(
            ['code' => 'DASH-DST'],
            [
                'name' => 'Dest Port',
                'type' => 'PORT',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'latitude' => 1.2600000,
                'longitude' => 103.8200000,
            ]
        );

        // Shipment for Customer A (IN_TRANSIT, Delayed)
        Shipment::create([
            'shipment_number' => 'CT-DASH-001',
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 100,
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->subHours(5),
            'estimated_arrival' => now()->addHours(10), // Delayed
            'current_status' => 'IN_TRANSIT',
            'created_by' => $this->operator->id,
        ]);

        // Shipment for Customer B (DELIVERED)
        Shipment::create([
            'shipment_number' => 'CT-DASH-002',
            'customer_id' => $this->customerB->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 200,
            'expected_departure' => now()->subDays(4),
            'expected_arrival' => now()->subDays(1),
            'estimated_arrival' => now()->subDays(1),
            'actual_arrival' => now()->subDays(1),
            'current_status' => 'DELIVERED',
            'created_by' => $this->operator->id,
        ]);
    }

    public function test_dashboard_service_computes_correct_global_metrics(): void
    {
        /** @var DashboardService $service */
        $service = app(DashboardService::class);
        $globalMetrics = $service->getMetrics($this->admin);

        $this->assertEquals(2, $globalMetrics['summary']['total_shipments']);
        $this->assertEquals(1, $globalMetrics['summary']['in_transit']);
        $this->assertEquals(1, $globalMetrics['summary']['delivered']);
        $this->assertEquals(1, $globalMetrics['summary']['delayed']);
        $this->assertEquals(50.0, $globalMetrics['summary']['delay_rate_percentage']);
    }

    public function test_dashboard_service_scopes_customer_metrics(): void
    {
        // Customer A should only see 1 shipment
        /** @var DashboardService $service */
        $service = app(DashboardService::class);
        $customerMetrics = $service->getMetrics($this->customerA);

        $this->assertEquals(1, $customerMetrics['summary']['total_shipments']);
        $this->assertEquals(1, $customerMetrics['summary']['in_transit']);
        $this->assertEquals(0, $customerMetrics['summary']['delivered']);
        $this->assertTrue($customerMetrics['user']['is_customer']);
    }

    public function test_api_dashboard_endpoint_returns_json_metrics(): void
    {
        $response = $this->actingAs($this->operator, 'sanctum')
            ->getJson('/api/dashboard');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'success',
            'data' => [
                'summary' => [
                    'total_shipments',
                    'in_transit',
                    'delivered',
                    'delayed',
                    'open_exceptions',
                    'delay_rate_percentage',
                    'exception_rate_percentage',
                ],
                'charts' => [
                    'status_distribution',
                    'carrier_distribution',
                ],
                'recent_shipments',
                'user',
            ],
        ]);
    }

    public function test_web_dashboard_page_loads_with_chart_elements(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('statusChart', false);
        $response->assertSee('carrierChart', false);
        $response->assertSee('Total Shipments', false);
        $response->assertSee('Chart.js', false);
    }
}
