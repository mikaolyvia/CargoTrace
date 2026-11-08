<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class TrackingTest extends TestCase
{
    use RefreshDatabase;

    private ShipmentService $shipmentService;

    private TrackingService $trackingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->shipmentService = app(ShipmentService::class);
        $this->trackingService = app(TrackingService::class);
    }

    public function test_initial_order_created_event_is_automatically_logged_on_create_shipment(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Apparel',
            'expected_departure' => now()->addDays(2)->toDateTimeString(),
            'expected_arrival' => now()->addDays(14)->toDateTimeString(),
        ], $operator);

        $this->assertCount(1, $shipment->trackingEvents);
        $initialEvent = $shipment->trackingEvents->first();
        $this->assertEquals('ORDER_CREATED', $initialEvent->event_type);
        $this->assertEquals($operator->id, $initialEvent->created_by);
    }

    public function test_linear_status_transitions_succeed_from_created_to_delivered(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Automotive Parts',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(10)->toDateTimeString(),
        ], $operator);

        $this->assertEquals('CREATED', $shipment->current_status);

        // 1. CREATED -> BOOKED
        $shipment = $this->shipmentService->transitionTo($shipment, 'BOOKED', [
            'description' => 'Carrier confirmed booking and assigned vessel space.',
        ], $operator);
        $this->assertEquals('BOOKED', $shipment->current_status);

        // 2. BOOKED -> PICKED_UP
        $shipment = $this->shipmentService->transitionTo($shipment, 'PICKED_UP', [
            'description' => 'Driver retrieved cargo at origin terminal.',
        ], $operator);
        $this->assertEquals('PICKED_UP', $shipment->current_status);

        // 3. PICKED_UP -> IN_TRANSIT
        $shipment = $this->shipmentService->transitionTo($shipment, 'IN_TRANSIT', [
            'description' => 'Shipment departed origin port and is currently in transit.',
        ], $operator);
        $this->assertEquals('IN_TRANSIT', $shipment->current_status);

        // 4. IN_TRANSIT -> DELIVERED
        $deliveredTime = now()->addDays(9);
        $shipment = $this->shipmentService->transitionTo($shipment, 'DELIVERED', [
            'event_time' => $deliveredTime,
            'description' => 'Consignment handed over to consignee at destination.',
        ], $operator);
        $this->assertEquals('DELIVERED', $shipment->current_status);
        $this->assertNotNull($shipment->actual_arrival);
        $this->assertEquals($dest->id, $shipment->current_location_id);

        // Verify total events: ORDER_CREATED + 4 transitions = 5 events
        $this->assertCount(5, $shipment->trackingEvents);
    }

    public function test_invalid_status_transition_jumping_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'General Cargo',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);

        // Attempting to jump directly from CREATED to DELIVERED
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid status transition from [CREATED] to [DELIVERED]');

        $this->shipmentService->transitionTo($shipment, 'DELIVERED', [], $operator);
    }

    public function test_backward_status_transition_is_rejected(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Textiles',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);

        $shipment = $this->shipmentService->transitionTo($shipment, 'BOOKED', [], $operator);

        // Attempting to revert from BOOKED back to CREATED
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Invalid status transition from [BOOKED] to [CREATED]');

        $this->shipmentService->transitionTo($shipment, 'CREATED', [], $operator);
    }

    public function test_terminal_status_cannot_be_transitioned_after_delivered(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Coffee Beans',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);

        $this->shipmentService->transitionTo($shipment, 'BOOKED', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'PICKED_UP', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'IN_TRANSIT', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'DELIVERED', [], $operator);

        // Now shipment is DELIVERED. Any transition attempt must fail!
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('DELIVERED is a terminal status');

        $this->shipmentService->transitionTo($shipment, 'IN_TRANSIT', [], $operator);
    }

    public function test_tracking_events_are_append_only_and_cannot_be_updated_or_deleted(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Raw Cotton',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);

        $event = $shipment->trackingEvents->first();
        $this->assertNotNull($event);

        // 1. Attempting to update existing event must throw RuntimeException
        try {
            $event->update(['description' => 'Fraudulent description']);
            $this->fail('Expected RuntimeException was not thrown on event update.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('immutable (append-only)', $e->getMessage());
        }

        // 2. Attempting to delete existing event must throw RuntimeException
        try {
            $event->delete();
            $this->fail('Expected RuntimeException was not thrown on event delete.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('immutable (append-only)', $e->getMessage());
        }
    }

    public function test_intermediate_events_do_not_alter_macro_status(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();
        $intermediatePort = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Pharmaceuticals',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(15)->toDateTimeString(),
        ], $operator);

        $this->shipmentService->transitionTo($shipment, 'BOOKED', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'PICKED_UP', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'IN_TRANSIT', [], $operator);

        $this->assertEquals('IN_TRANSIT', $shipment->current_status);

        // Record intermediate ARRIVED at transit port
        $intermediateEvent = $this->trackingService->recordIntermediateEvent($shipment, 'ARRIVED', [
            'location_id' => $intermediatePort->id,
            'description' => 'Vessel anchored at intermediate bunkering hub.',
        ], $operator);

        $shipment->refresh();
        $this->assertEquals('IN_TRANSIT', $shipment->current_status, 'Macro status must remain IN_TRANSIT');
        $this->assertEquals($intermediatePort->id, $shipment->current_location_id, 'Current location should update to transit port');
        $this->assertEquals('ARRIVED', $intermediateEvent->event_type);
    }

    public function test_timeline_retrieves_chronologically_ordered_events(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Steel Coils',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(5)->toDateTimeString(),
        ], $operator);

        $this->shipmentService->transitionTo($shipment, 'BOOKED', [
            'event_time' => now()->addHours(2),
        ], $operator);

        $this->shipmentService->transitionTo($shipment, 'PICKED_UP', [
            'event_time' => now()->addHours(4),
        ], $operator);

        $timelineAsc = $this->trackingService->getTimeline($shipment, 'asc');
        $this->assertCount(3, $timelineAsc);
        $this->assertEquals('ORDER_CREATED', $timelineAsc[0]->event_type);
        $this->assertEquals('BOOKING_CONFIRMED', $timelineAsc[1]->event_type);
        $this->assertEquals('PICKED_UP', $timelineAsc[2]->event_type);

        $timelineDesc = $this->trackingService->getTimeline($shipment, 'desc');
        $this->assertEquals('PICKED_UP', $timelineDesc[0]->event_type);
        $this->assertEquals('ORDER_CREATED', $timelineDesc[2]->event_type);
    }

    public function test_map_route_points_include_origin_checkpoints_and_destination(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->create(['latitude' => -6.10, 'longitude' => 106.88]);
        $dest = Location::factory()->create(['latitude' => 51.95, 'longitude' => 4.14]);

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Semiconductors',
            'expected_departure' => now()->addDays(1)->toDateTimeString(),
            'expected_arrival' => now()->addDays(10)->toDateTimeString(),
        ], $operator);

        // Add waypoint with coordinates
        $this->trackingService->recordIntermediateEvent($shipment, 'IN_TRANSIT', [
            'latitude' => 1.26,
            'longitude' => 103.82,
            'description' => 'Waypoint off Singapore strait.',
        ], $operator);

        $routePoints = $this->trackingService->getMapRoutePoints($shipment);

        $this->assertCount(3, $routePoints);
        $this->assertEquals('ORIGIN', $routePoints[0]['type']);
        $this->assertEquals(-6.10, $routePoints[0]['lat']);
        $this->assertEquals(106.88, $routePoints[0]['lng']);

        $this->assertEquals('CHECKPOINT', $routePoints[1]['type']);
        $this->assertEquals(1.26, $routePoints[1]['lat']);
        $this->assertEquals(103.82, $routePoints[1]['lng']);

        $this->assertEquals('DESTINATION', $routePoints[2]['type']);
        $this->assertEquals(51.95, $routePoints[2]['lat']);
        $this->assertEquals(4.14, $routePoints[2]['lng']);
    }
}
