<?php

namespace Tests\Feature\Api;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExceptionApiTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $customerA;
    private User $customerB;
    private Shipment $shipment;
    private Location $origin;
    private Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => 'OPERATOR']);
        $this->customerA = User::factory()->create(['role' => 'CUSTOMER']);
        $this->customerB = User::factory()->create(['role' => 'CUSTOMER']);

        $carrier = Carrier::factory()->create(['code' => 'EXC_CR']);
        $this->origin = Location::factory()->create(['code' => 'EXC_ORIGIN']);
        $this->destination = Location::factory()->create(['code' => 'EXC_DEST']);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0200',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'IN_TRANSIT',
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(3),
            'estimated_arrival' => now()->addDays(3),
        ]);
    }

    public function test_operator_can_report_exception_with_eta_impact(): void
    {
        $payload = [
            'exception_type' => 'PORT_CONGESTION',
            'severity' => 'HIGH',
            'impact_hours' => 12.0,
            'description' => 'Severe berth congestion at terminal.',
            'location_id' => $this->origin->id,
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/exceptions", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.exception_type', 'PORT_CONGESTION')
            ->assertJsonPath('data.severity', 'HIGH')
            ->assertJsonPath('data.impact_hours', 12)
            ->assertJsonPath('data.status', 'OPEN');

        $this->assertDatabaseHas('shipment_exceptions', [
            'shipment_id' => $this->shipment->id,
            'exception_type' => 'PORT_CONGESTION',
            'status' => 'OPEN',
            'reported_by' => $this->operator->id,
        ]);

        // Verify ETA was adjusted on the shipment
        $this->shipment->refresh();
        $this->assertTrue($this->shipment->is_delayed);
        $this->assertEquals(12.0, (float) $this->shipment->delay_hours);
    }

    public function test_customer_cannot_report_exception(): void
    {
        $payload = [
            'exception_type' => 'WEATHER_DELAY',
            'severity' => 'LOW',
            'description' => 'Customer trying to file exception',
        ];

        $response = $this->actingAs($this->customerA, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/exceptions", $payload);

        $response->assertStatus(403);
    }

    public function test_customer_can_view_own_exceptions_and_cannot_view_others(): void
    {
        $exception = ShipmentException::create([
            'shipment_id' => $this->shipment->id,
            'exception_type' => 'CUSTOMS_HOLD',
            'severity' => 'MEDIUM',
            'status' => 'OPEN',
            'impact_hours' => 5.0,
            'description' => 'Inspection hold.',
            'detected_at' => now(),
            'reported_by' => $this->operator->id,
        ]);

        // Customer A can view list of exceptions
        $responseA = $this->actingAs($this->customerA, 'sanctum')
            ->getJson("/api/shipments/{$this->shipment->id}/exceptions");

        $responseA->assertStatus(200);
        $this->assertCount(1, $responseA->json('data'));

        // Customer B cannot view Customer A's shipment exceptions
        $responseB = $this->actingAs($this->customerB, 'sanctum')
            ->getJson("/api/shipments/{$this->shipment->id}/exceptions");

        $responseB->assertStatus(403);
    }

    public function test_operator_can_investigate_and_resolve_exception(): void
    {
        $exception = ShipmentException::create([
            'shipment_id' => $this->shipment->id,
            'exception_type' => 'DOCUMENTATION_ISSUE',
            'severity' => 'MEDIUM',
            'status' => 'OPEN',
            'impact_hours' => 6.0,
            'description' => 'Missing commercial invoice.',
            'detected_at' => now(),
            'reported_by' => $this->operator->id,
        ]);

        // 1. Investigate
        $invResponse = $this->actingAs($this->operator, 'sanctum')
            ->patchJson("/api/exceptions/{$exception->id}/investigate", [
                'notes' => 'Contacted shipper to resend documentation.',
            ]);

        $invResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'INVESTIGATING');

        // 2. Resolve
        $resolveResponse = $this->actingAs($this->operator, 'sanctum')
            ->patchJson("/api/exceptions/{$exception->id}/resolve", [
                'resolution_notes' => 'Invoice verified and approved by customs broker.',
                'eta_correction_hours' => -2.0,
            ]);

        $resolveResponse->assertStatus(200)
            ->assertJsonPath('data.status', 'RESOLVED')
            ->assertJsonPath('data.resolution_notes', 'Invoice verified and approved by customs broker.');

        $this->assertDatabaseHas('shipment_exceptions', [
            'id' => $exception->id,
            'status' => 'RESOLVED',
            'resolved_by' => $this->operator->id,
        ]);
    }

    public function test_resolved_exception_cannot_be_reinvestigated(): void
    {
        $resolvedException = ShipmentException::create([
            'shipment_id' => $this->shipment->id,
            'exception_type' => 'WEATHER_DELAY',
            'severity' => 'HIGH',
            'status' => ShipmentException::STATUS_RESOLVED,
            'impact_hours' => 8.0,
            'description' => 'Storm resolved and vessel resumed voyage.',
            'detected_at' => now()->subDays(2),
            'resolved_at' => now()->subDay(),
            'resolution_notes' => 'Sea conditions improved.',
            'reported_by' => $this->operator->id,
            'resolved_by' => $this->operator->id,
        ]);

        // Attempt to move back from RESOLVED to INVESTIGATING must be blocked (HTTP 403 or 422)
        $response = $this->actingAs($this->operator, 'sanctum')
            ->patchJson("/api/exceptions/{$resolvedException->id}/investigate", [
                'notes' => 'Trying to reopen investigation',
            ]);

        // Should return 403 Forbidden (policy guard)
        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);

        // Confirm data is untouched and remains RESOLVED
        $resolvedException->refresh();
        $this->assertEquals(ShipmentException::STATUS_RESOLVED, $resolvedException->status);
        $this->assertEquals('Sea conditions improved.', $resolvedException->resolution_notes);
    }
}
