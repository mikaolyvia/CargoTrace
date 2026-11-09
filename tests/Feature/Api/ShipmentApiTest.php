<?php

namespace Tests\Feature\Api;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentApiTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $operator;
    private User $customerA;
    private User $customerB;
    private Carrier $carrier;
    private Location $origin;
    private Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'ADMIN']);
        $this->operator = User::factory()->create(['role' => 'OPERATOR']);
        $this->customerA = User::factory()->create(['role' => 'CUSTOMER']);
        $this->customerB = User::factory()->create(['role' => 'CUSTOMER']);

        $this->carrier = Carrier::factory()->create(['code' => 'TEST_CR']);
        $this->origin = Location::factory()->create(['code' => 'ORIGIN_LOC', 'latitude' => -6.2088, 'longitude' => 106.8456]);
        $this->destination = Location::factory()->create(['code' => 'DEST_LOC', 'latitude' => 1.3521, 'longitude' => 103.8198]);
    }

    public function test_operator_can_create_shipment_with_items(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'expected_departure' => now()->addDay()->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                [
                    'item_description' => 'Industrial Equipment',
                    'quantity' => 2,
                    'weight_kg' => 250.50,
                    'length_cm' => 120,
                    'width_cm' => 80,
                    'height_cm' => 100,
                ],
            ],
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson('/api/shipments', $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.customer_id', $this->customerA->id)
            ->assertJsonPath('data.current_status', 'CREATED')
            ->assertJsonPath('data.total_weight_kg', 501);

        $this->assertDatabaseHas('shipments', [
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'current_status' => 'CREATED',
        ]);
    }

    public function test_customer_cannot_create_shipment(): void
    {
        $payload = [
            'customer_id' => $this->customerA->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'expected_departure' => now()->addDay()->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
            'items' => [
                [
                    'item_description' => 'Sample Goods',
                    'quantity' => 1,
                    'weight_kg' => 10.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->customerA, 'sanctum')
            ->postJson('/api/shipments', $payload);

        $response->assertStatus(403);
    }

    public function test_anti_idor_customer_cannot_view_another_customer_shipment(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0001',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        // Customer A can view own shipment
        $responseA = $this->actingAs($this->customerA, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}");
        $responseA->assertStatus(200)
            ->assertJsonPath('data.shipment_number', 'CT-20261110-0001');

        // Customer B is forbidden from viewing Customer A's shipment (Anti-IDOR)
        $responseB = $this->actingAs($this->customerB, 'sanctum')
            ->getJson("/api/shipments/{$shipment->id}");
        $responseB->assertStatus(403);
    }

    public function test_customer_listing_is_scoped_strictly_to_own_shipments(): void
    {
        Shipment::create([
            'shipment_number' => 'CT-20261110-0010',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        Shipment::create([
            'shipment_number' => 'CT-20261110-0020',
            'customer_id' => $this->customerB->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        // Customer A queries shipments listing
        $response = $this->actingAs($this->customerA, 'sanctum')
            ->getJson('/api/shipments');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals('CT-20261110-0010', $data[0]['shipment_number']);

        // Operator sees both shipments
        $opResponse = $this->actingAs($this->operator, 'sanctum')
            ->getJson('/api/shipments');
        $this->assertCount(2, $opResponse->json('data'));
    }

    public function test_shipment_search_escapes_sql_wildcards_percent_and_underscore(): void
    {
        Shipment::create([
            'shipment_number' => 'CT-100%-SPECIAL',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        // Literal search '%' should match only CT-100%-SPECIAL
        $response = $this->actingAs($this->operator, 'sanctum')
            ->getJson('/api/shipments?search=%25');

        $response->assertStatus(200);
        $this->assertCount(1, $response->json('data'));
        $this->assertEquals('CT-100%-SPECIAL', $response->json('data.0.shipment_number'));
    }

    public function test_direct_status_change_via_update_is_prohibited(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0030',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        $response = $this->actingAs($this->operator, 'sanctum')
            ->putJson("/api/shipments/{$shipment->id}", [
                'current_status' => 'DELIVERED',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['current_status']);
    }

    public function test_valid_and_invalid_status_transitions(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0040',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        // Valid transition: CREATED -> BOOKED
        $validResponse = $this->actingAs($this->operator, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/status", [
                'status' => 'BOOKED',
                'description' => 'Booking space confirmed.',
            ]);

        $validResponse->assertStatus(200)
            ->assertJsonPath('data.current_status', 'BOOKED');

        // Invalid transition jump: BOOKED -> DELIVERED (Must be rejected)
        $invalidResponse = $this->actingAs($this->operator, 'sanctum')
            ->patchJson("/api/shipments/{$shipment->id}/status", [
                'status' => 'DELIVERED',
            ]);

        $invalidResponse->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_admin_can_soft_delete_shipment_operator_and_customer_cannot(): void
    {
        $shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0050',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(4),
            'estimated_arrival' => now()->addDays(4),
        ]);

        // Customer cannot delete
        $this->actingAs($this->customerA, 'sanctum')
            ->deleteJson("/api/shipments/{$shipment->id}")
            ->assertStatus(403);

        // Operator cannot delete
        $this->actingAs($this->operator, 'sanctum')
            ->deleteJson("/api/shipments/{$shipment->id}")
            ->assertStatus(403);

        // Admin can soft-delete
        $this->actingAs($this->admin, 'sanctum')
            ->deleteJson("/api/shipments/{$shipment->id}")
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Shipment successfully deleted.',
            ]);

        $this->assertSoftDeleted('shipments', ['id' => $shipment->id]);
    }
}
