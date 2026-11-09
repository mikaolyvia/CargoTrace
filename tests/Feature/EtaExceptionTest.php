<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\EtaService;
use App\Services\ExceptionService;
use App\Services\ShipmentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EtaExceptionTest extends TestCase
{
    use RefreshDatabase;

    private EtaService $etaService;
    private ExceptionService $exceptionService;
    private ShipmentService $shipmentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->etaService = app(EtaService::class);
        $this->exceptionService = app(ExceptionService::class);
        $this->shipmentService = app(ShipmentService::class);

        // Ensure default thresholds in test database
        SystemSetting::setValue('delay_threshold_low', '6.0');
        SystemSetting::setValue('delay_threshold_medium', '24.0');
    }

    public function test_eta_service_correctly_calculates_delay_severity_levels(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $expectedArrival = Carbon::parse('2026-11-20 12:00:00');

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Electronics',
            'expected_departure' => '2026-11-10 12:00:00',
            'expected_arrival' => $expectedArrival->toDateTimeString(),
        ], $operator);

        // Case 1: On-time (ETA == Expected) -> NONE
        $calc1 = $this->etaService->calculateDelay($shipment);
        $this->assertFalse($calc1['is_delayed']);
        $this->assertEquals(0.00, $calc1['delay_hours']);
        $this->assertEquals('NONE', $calc1['delay_severity']);

        // Case 2: 4 hours delay (<= 6.0) -> LOW
        $shipment->estimated_arrival = $expectedArrival->copy()->addHours(4);
        $calc2 = $this->etaService->calculateDelay($shipment);
        $this->assertTrue($calc2['is_delayed']);
        $this->assertEquals(4.00, $calc2['delay_hours']);
        $this->assertEquals('LOW', $calc2['delay_severity']);

        // Case 3: 14 hours delay (6 < h <= 24) -> MEDIUM
        $shipment->estimated_arrival = $expectedArrival->copy()->addHours(14);
        $calc3 = $this->etaService->calculateDelay($shipment);
        $this->assertTrue($calc3['is_delayed']);
        $this->assertEquals(14.00, $calc3['delay_hours']);
        $this->assertEquals('MEDIUM', $calc3['delay_severity']);

        // Case 4: 30 hours delay (> 24.0) -> HIGH
        $shipment->estimated_arrival = $expectedArrival->copy()->addHours(30);
        $calc4 = $this->etaService->calculateDelay($shipment);
        $this->assertTrue($calc4['is_delayed']);
        $this->assertEquals(30.00, $calc4['delay_hours']);
        $this->assertEquals('HIGH', $calc4['delay_severity']);
    }

    public function test_delivered_shipment_uses_actual_arrival_for_delay_calculation(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $expectedArrival = Carbon::parse('2026-11-20 12:00:00');
        $actualArrival = Carbon::parse('2026-11-20 20:00:00'); // 8 hours late

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Apparel',
            'expected_departure' => '2026-11-10 12:00:00',
            'expected_arrival' => $expectedArrival->toDateTimeString(),
        ], $operator);

        $this->shipmentService->transitionTo($shipment, 'BOOKED', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'PICKED_UP', [], $operator);
        $this->shipmentService->transitionTo($shipment, 'IN_TRANSIT', [], $operator);
        $shipment = $this->shipmentService->transitionTo($shipment, 'DELIVERED', [
            'event_time' => $actualArrival,
        ], $operator);

        $this->assertEquals('DELIVERED', $shipment->current_status);
        $calc = $this->etaService->calculateDelay($shipment);

        // Baseline must be actual_arrival (8 hours late -> MEDIUM)
        $this->assertTrue($calc['is_delayed']);
        $this->assertEquals(8.00, $calc['delay_hours']);
        $this->assertEquals('MEDIUM', $calc['delay_severity']);
    }

    public function test_exception_reporting_adjusts_eta_and_logs_tracking_event_transactionally(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $expectedArrival = Carbon::parse('2026-11-20 12:00:00');

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Textiles',
            'expected_departure' => '2026-11-10 12:00:00',
            'expected_arrival' => $expectedArrival->toDateTimeString(),
        ], $operator);

        $initialEta = $shipment->estimated_arrival->copy();

        // Report CUSTOMS_HOLD with 10 hours impact
        $exception = $this->exceptionService->reportException($shipment, [
            'exception_type' => 'CUSTOMS_HOLD',
            'severity' => 'MEDIUM',
            'impact_hours' => 10.0,
            'description' => 'Container flagged for physical customs inspection.',
        ], $operator);

        $shipment->refresh();

        $this->assertNotNull($exception);
        $this->assertEquals('OPEN', $exception->status);
        $this->assertEquals(10.0, $exception->impact_hours);
        $this->assertNotNull($exception->tracking_event_id);

        // Verify ETA pushed by exactly 10 hours
        $this->assertEquals($initialEta->addHours(10)->toDateTimeString(), $shipment->estimated_arrival->toDateTimeString());
        $this->assertTrue($shipment->is_delayed);
        $this->assertEquals(10.00, $shipment->delay_hours);
        $this->assertEquals('MEDIUM', $shipment->delay_severity);

        // Verify tracking event created
        $this->assertDatabaseHas('tracking_events', [
            'id' => $exception->tracking_event_id,
            'event_type' => 'EXCEPTION',
        ]);
    }

    public function test_delta_based_eta_adjustment_prevents_double_counting_on_update(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::factory()->create();
        $origin = Location::factory()->port()->create();
        $dest = Location::factory()->port()->create();

        $expectedArrival = Carbon::parse('2026-11-20 12:00:00');

        $shipment = $this->shipmentService->createShipment([
            'customer_id' => $customer->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'Raw Materials',
            'expected_departure' => '2026-11-10 12:00:00',
            'expected_arrival' => $expectedArrival->toDateTimeString(),
        ], $operator);

        $initialEta = $shipment->estimated_arrival->copy();

        // Initial impact: 10 hours
        $exception = $this->exceptionService->reportException($shipment, [
            'exception_type' => 'PORT_CONGESTION',
            'severity' => 'MEDIUM',
            'impact_hours' => 10.0,
            'description' => 'Berth congestion at transshipment port.',
        ], $operator);

        $shipment->refresh();
        $this->assertEquals($initialEta->copy()->addHours(10)->toDateTimeString(), $shipment->estimated_arrival->toDateTimeString());

        // Update impact from 10 hours to 16 hours (+6 hours delta)
        $this->exceptionService->updateException($exception, [
            'impact_hours' => 16.0,
            'description' => 'Extended berth congestion advisory.',
        ], $operator);

        $shipment->refresh();
        // Total delay should be 16 hours (NOT 10 + 16 = 26 hours!)
        $this->assertEquals($initialEta->copy()->addHours(16)->toDateTimeString(), $shipment->estimated_arrival->toDateTimeString());
        $this->assertEquals(16.00, $shipment->delay_hours);

        // Update without changing impact hours (e.g. description only) -> ETA must remain unchanged
        $this->exceptionService->updateException($exception, [
            'description' => 'Vessel scheduled to dock within 12 hours.',
        ], $operator);

        $shipment->refresh();
        $this->assertEquals($initialEta->copy()->addHours(16)->toDateTimeString(), $shipment->estimated_arrival->toDateTimeString());
    }

    public function test_exception_investigation_and_resolution_workflow(): void
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
            'cargo_type' => 'Machinery',
            'expected_departure' => '2026-11-10 12:00:00',
            'expected_arrival' => '2026-11-20 12:00:00',
        ], $operator);

        $exception = $this->exceptionService->reportException($shipment, [
            'exception_type' => 'MISROUTED',
            'severity' => 'HIGH',
            'impact_hours' => 20.0,
            'description' => 'Container incorrectly staged in wrong yard sector.',
        ], $operator);

        $this->assertEquals('OPEN', $exception->status);

        // Investigate
        $exception = $this->exceptionService->investigateException($exception, $operator, 'Terminal yard supervisor reviewing CCTV logs.');
        $this->assertEquals('INVESTIGATING', $exception->status);

        // Resolve with explicit ETA correction (recovered 4 hours faster than estimated)
        $initialEta = $shipment->refresh()->estimated_arrival->copy();
        $exception = $this->exceptionService->resolveException($exception, [
            'resolution_notes' => 'Container located, scanned, and re-staged at active departure berth.',
            'eta_correction_hours' => -4.0,
        ], $operator);

        $this->assertEquals('RESOLVED', $exception->status);
        $this->assertNotNull($exception->resolved_at);
        $this->assertEquals($operator->id, $exception->resolved_by);
        $this->assertEquals('Container located, scanned, and re-staged at active departure berth.', $exception->resolution_notes);

        // Verify ETA was adjusted by -4 hours from the corrected explicit delta
        $shipment->refresh();
        $this->assertEquals($initialEta->subHours(4)->toDateTimeString(), $shipment->estimated_arrival->toDateTimeString());
    }
}
