<?php

namespace Tests\Feature\Api;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrackingApiTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;
    private User $customerA;
    private User $customerB;
    private Shipment $shipment;
    private Location $origin;
    private Location $checkpoint;
    private Location $destination;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => 'OPERATOR']);
        $this->customerA = User::factory()->create(['role' => 'CUSTOMER']);
        $this->customerB = User::factory()->create(['role' => 'CUSTOMER']);

        $carrier = Carrier::factory()->create(['code' => 'TRACK_CR']);
        $this->origin = Location::factory()->create(['code' => 'ORIGIN_P', 'name' => 'Origin Port', 'latitude' => -6.10, 'longitude' => 106.88]);
        $this->checkpoint = Location::factory()->create(['code' => 'CHECK_P', 'name' => 'Checkpoint Hub', 'latitude' => 1.30, 'longitude' => 103.80]);
        $this->destination = Location::factory()->create(['code' => 'DEST_P', 'name' => 'Destination Port', 'latitude' => 51.90, 'longitude' => 4.40]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-20261110-0100',
            'customer_id' => $this->customerA->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->destination->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'IN_TRANSIT',
            'expected_departure' => now()->subDays(2),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
        ]);
    }

    public function test_customer_can_retrieve_timeline_and_route_points_for_own_shipment(): void
    {
        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'event_type' => 'DEPARTED',
            'event_time' => now()->subDays(2),
            'location_id' => $this->origin->id,
            'latitude' => $this->origin->latitude,
            'longitude' => $this->origin->longitude,
            'description' => 'Vessel departed origin port.',
            'created_by' => $this->operator->id,
        ]);

        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'event_type' => 'IN_TRANSIT',
            'event_time' => now()->subDay(),
            'location_id' => $this->checkpoint->id,
            'latitude' => $this->checkpoint->latitude,
            'longitude' => $this->checkpoint->longitude,
            'description' => 'Transshipment checkpoint reached.',
            'created_by' => $this->operator->id,
        ]);

        $response = $this->actingAs($this->customerA, 'sanctum')
            ->getJson("/api/shipments/{$this->shipment->id}/tracking");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shipment_number', 'CT-20261110-0100')
            ->assertJsonPath('data.current_status', 'IN_TRANSIT');

        $this->assertCount(2, $response->json('data.timeline'));
        $this->assertNotEmpty($response->json('data.route_points'));
    }

    public function test_customer_cannot_view_tracking_of_another_customer(): void
    {
        $response = $this->actingAs($this->customerB, 'sanctum')
            ->getJson("/api/shipments/{$this->shipment->id}/tracking");

        $response->assertStatus(403);
    }

    public function test_operator_can_append_tracking_event(): void
    {
        $payload = [
            'event_type' => 'IN_TRANSIT',
            'location_id' => $this->checkpoint->id,
            'latitude' => 1.30,
            'longitude' => 103.80,
            'description' => 'Vessel traversing Malacca Strait.',
            'weather_condition' => 'Clear',
            'weather_temp_c' => 29.5,
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/tracking", $payload);

        $response->assertStatus(201)
            ->assertJsonPath('data.event_type', 'IN_TRANSIT')
            ->assertJsonPath('data.description', 'Vessel traversing Malacca Strait.')
            ->assertJsonPath('data.weather_condition', 'Clear');

        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'event_type' => 'IN_TRANSIT',
            'created_by' => $this->operator->id,
        ]);
    }

    public function test_customer_cannot_post_tracking_events(): void
    {
        $payload = [
            'event_type' => 'ARRIVED',
            'description' => 'Customer trying to append event',
        ];

        $response = $this->actingAs($this->customerA, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/tracking", $payload);

        $response->assertStatus(403);
    }

    public function test_backdated_intermediate_event_does_not_overwrite_current_location(): void
    {
        // 1. Establish latest event at destination port at 14:00 today
        $now = now();
        $this->shipment->update(['current_location_id' => $this->destination->id]);

        TrackingEvent::create([
            'shipment_id' => $this->shipment->id,
            'event_type' => 'ARRIVED',
            'event_time' => $now,
            'location_id' => $this->destination->id,
            'latitude' => $this->destination->latitude,
            'longitude' => $this->destination->longitude,
            'description' => 'Arrived at destination port.',
            'created_by' => $this->operator->id,
        ]);

        // 2. Post a back-dated event (yesterday) with origin location
        $backdatedPayload = [
            'event_type' => 'IN_TRANSIT',
            'location_id' => $this->origin->id,
            'event_time' => $now->copy()->subDay()->toDateTimeString(),
            'description' => 'Late logged departure inspection from yesterday.',
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/tracking", $backdatedPayload);

        $response->assertStatus(201);

        // 3. Verify event is appended into history (append-only)
        $this->assertDatabaseHas('tracking_events', [
            'shipment_id' => $this->shipment->id,
            'description' => 'Late logged departure inspection from yesterday.',
        ]);

        // 4. Crucial: shipment current_location_id must REMAIN destination, not overwritten by backdated origin
        $this->shipment->refresh();
        $this->assertEquals($this->destination->id, $this->shipment->current_location_id);
    }

    public function test_chronologically_newer_event_updates_current_location(): void
    {
        // Initial location at origin
        $this->shipment->update(['current_location_id' => $this->origin->id]);

        // Post a future/newer event at checkpoint
        $newerPayload = [
            'event_type' => 'ARRIVED',
            'location_id' => $this->checkpoint->id,
            'event_time' => now()->addHours(2)->toDateTimeString(),
            'description' => 'Arrived at transshipment checkpoint hub.',
        ];

        $response = $this->actingAs($this->operator, 'sanctum')
            ->postJson("/api/shipments/{$this->shipment->id}/tracking", $newerPayload);

        $response->assertStatus(201);

        // Verify current_location_id was successfully updated
        $this->shipment->refresh();
        $this->assertEquals($this->checkpoint->id, $this->shipment->current_location_id);
    }
}
