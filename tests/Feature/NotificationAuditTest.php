<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\Location;
use App\Models\Notification;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use Tests\TestCase;

class NotificationAuditTest extends TestCase
{
    protected User $operator;
    protected User $customer;
    protected User $otherCustomer;
    protected Carrier $carrier;
    protected Location $origin;
    protected Location $destination;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');

        $this->operator = User::firstOrCreate(
            ['email' => 'op_audit_test@cargotrace.com'],
            ['name' => 'Operator Audit', 'role' => 'OPERATOR', 'password' => bcrypt('password')]
        );

        $this->customer = User::firstOrCreate(
            ['email' => 'cust_audit_test@cargotrace.com'],
            ['name' => 'Customer Audit', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->otherCustomer = User::firstOrCreate(
            ['email' => 'other_cust_test@cargotrace.com'],
            ['name' => 'Other Customer', 'role' => 'CUSTOMER', 'password' => bcrypt('password')]
        );

        $this->carrier = Carrier::firstOrCreate(
            ['code' => 'NOTIF-CARRIER'],
            ['name' => 'Audit Express', 'type' => 'AIR', 'is_active' => true]
        );

        $this->origin = Location::firstOrCreate(
            ['code' => 'AUD-ORG'],
            [
                'name' => 'Origin Hub',
                'type' => 'AIRPORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'latitude' => -6.1275000,
                'longitude' => 106.6537000,
            ]
        );

        $this->destination = Location::firstOrCreate(
            ['code' => 'AUD-DST'],
            [
                'name' => 'Dest Hub',
                'type' => 'AIRPORT',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'latitude' => 35.7720000,
                'longitude' => 140.3929000,
            ]
        );
    }

    public function test_shipment_creation_and_transition_generate_audit_log_and_notifications(): void
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = app(ShipmentService::class);

        // 1. Create shipment
        $shipment = $shipmentService->createShipment([
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'ELECTRONICS',
            'weight_kg' => 50,
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(3),
            'items' => [
                ['item_name' => 'Microcontrollers', 'quantity' => 10, 'weight_kg' => 50],
            ],
        ], $this->operator);

        // Verify audit log for creation
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'SHIPMENT_CREATED',
            'entity_type' => Shipment::class,
            'entity_id' => $shipment->id,
        ]);

        // Verify customer received notification
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'INFO',
        ]);

        // 2. Transition status: CREATED -> BOOKED
        $shipmentService->transitionTo($shipment, 'BOOKED', [
            'description' => 'Flight confirmed by air cargo.',
        ], $this->operator);

        // Verify audit log for transition
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'STATUS_TRANSITION',
            'entity_type' => Shipment::class,
            'entity_id' => $shipment->id,
        ]);

        // Verify second notification sent
        $this->assertEquals(2, Notification::where('user_id', $this->customer->id)->count());
    }

    public function test_exception_lifecycle_triggers_audit_and_customer_warning_notification(): void
    {
        /** @var ShipmentService $shipmentService */
        $shipmentService = app(ShipmentService::class);
        $shipment = $shipmentService->createShipment([
            'customer_id' => $this->customer->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'weight_kg' => 10,
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(2),
        ], $this->operator);

        /** @var ExceptionService $exceptionService */
        $exceptionService = app(ExceptionService::class);

        // Report exception
        $exception = $exceptionService->reportException($shipment, [
            'exception_type' => 'WEATHER_DELAY',
            'severity' => 'HIGH',
            'impact_hours' => 12.0,
            'description' => 'Severe typhoon grounded all flights.',
        ], $this->operator);

        // Verify audit log for exception
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EXCEPTION_REPORTED',
            'entity_type' => ShipmentException::class,
            'entity_id' => $exception->id,
        ]);

        // Verify warning notification sent to customer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'WARNING',
        ]);

        // Resolve exception
        $exceptionService->resolveException($exception, [
            'resolution_notes' => 'Weather cleared, flight rescheduled.',
        ], $this->operator);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'EXCEPTION_RESOLVED',
            'entity_type' => ShipmentException::class,
            'entity_id' => $exception->id,
        ]);

        // Verify success notification sent to customer
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->customer->id,
            'type' => 'SUCCESS',
        ]);
    }

    public function test_notification_endpoints_enforce_customer_isolation(): void
    {
        $notif = Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Secret Customer Notif',
            'message' => 'Private shipment info',
            'type' => 'INFO',
        ]);

        // Other customer tries to mark as read -> blocked with 403
        $response = $this->actingAs($this->otherCustomer, 'sanctum')
            ->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(403);

        // Owner customer can mark as read
        $response = $this->actingAs($this->customer, 'sanctum')
            ->patchJson("/api/notifications/{$notif->id}/read");

        $response->assertStatus(200);
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_audit_logs_endpoint_blocks_customer_and_allows_operator(): void
    {
        // Customer blocked
        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/audit-logs');

        $response->assertStatus(403);

        // Operator allowed
        $response = $this->actingAs($this->operator, 'sanctum')
            ->getJson('/api/audit-logs');

        $response->assertStatus(200);
        $response->assertJsonStructure(['success', 'data']);
    }
}
