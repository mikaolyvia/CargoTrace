<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\ShipmentService;
use App\Services\TrackingService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrackingEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds realistic chronological tracking events for demo shipments.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $shipmentService = app(ShipmentService::class);
        $trackingService = app(TrackingService::class);

        $jakartaPort = Location::where('code', 'IDJKT')->first();
        $singaporePort = Location::where('code', 'SGSIN')->first();
        $jakartaAirport = Location::where('code', 'CGK')->first();
        $changiAirport = Location::where('code', 'SIN')->first();

        // 1. Shipment 1: Jakarta -> Rotterdam (Just created)
        $shipment1 = Shipment::where('shipment_number', 'CT-20261109-0001')->first();
        if ($shipment1 && $shipment1->trackingEvents()->count() === 0) {
            $trackingService->recordEvent($shipment1, [
                'event_type' => 'ORDER_CREATED',
                'event_time' => Carbon::now()->subHours(6),
                'location_id' => $jakartaPort->id,
                'description' => 'Electronic cargo booking manifest generated at Jakarta port terminal.',
                'weather_condition' => 'Scattered Clouds',
                'weather_temp_c' => 31.0,
            ], $operator);
        }

        // 2. Shipment 2: Singapore -> Hamburg (Progressed to PICKED_UP)
        $shipment2 = Shipment::where('shipment_number', 'CT-20261109-0002')->first();
        if ($shipment2 && $shipment2->trackingEvents()->count() === 0) {
            // Event 1: ORDER_CREATED
            $trackingService->recordEvent($shipment2, [
                'event_type' => 'ORDER_CREATED',
                'event_time' => Carbon::now()->subDays(2),
                'location_id' => $singaporePort->id,
                'description' => 'Container booking order initialized in Singapore hub.',
            ], $operator);

            // Event 2: BOOKED (State Transition)
            $shipmentService->transitionTo($shipment2, 'BOOKED', [
                'event_type' => 'BOOKING_CONFIRMED',
                'event_time' => Carbon::now()->subDays(1)->subHours(12),
                'location_id' => $singaporePort->id,
                'description' => 'Space allocated on Evergreen feeder vessel. Slot confirmed.',
            ], $operator);

            // Event 3: PICKED_UP (State Transition)
            $shipmentService->transitionTo($shipment2, 'PICKED_UP', [
                'event_type' => 'PICKED_UP',
                'event_time' => Carbon::now()->subHours(8),
                'location_id' => $singaporePort->id,
                'description' => 'Cargo received and scanned at Singapore Container Terminal Gate 3.',
                'weather_condition' => 'Light Rain',
                'weather_temp_c' => 28.5,
            ], $operator);
        }

        // 3. Shipment 3: Jakarta Port -> Singapore Port (Progressed to IN_TRANSIT with Ocean Waypoint)
        $shipment3 = Shipment::where('shipment_number', 'CT-20261109-0003')->first();
        $jakartaPort = Location::where('code', 'IDJKT')->first();

        if ($shipment3 && $shipment3->trackingEvents()->count() === 0) {
            // Event 1: ORDER_CREATED
            $trackingService->recordEvent($shipment3, [
                'event_type' => 'ORDER_CREATED',
                'event_time' => Carbon::now()->subHours(18),
                'location_id' => $jakartaPort?->id,
                'description' => 'Ocean bill of lading issued for containerized semiconductor cargo.',
            ], $operator);

            // Event 2: BOOKED
            $shipmentService->transitionTo($shipment3, 'BOOKED', [
                'event_type' => 'BOOKING_CONFIRMED',
                'event_time' => Carbon::now()->subHours(14),
                'location_id' => $jakartaPort?->id,
                'description' => 'Container booking confirmed on MSC Gülsün feeder service.',
            ], $operator);

            // Event 3: PICKED_UP
            $shipmentService->transitionTo($shipment3, 'PICKED_UP', [
                'event_type' => 'PICKED_UP',
                'event_time' => Carbon::now()->subHours(8),
                'location_id' => $jakartaPort?->id,
                'description' => '40ft container loaded and lashed on vessel deck at Tanjung Priok Berth 102.',
                'weather_condition' => 'Clear Sky',
                'weather_temp_c' => 30.0,
            ], $operator);

            // Event 4: IN_TRANSIT (DEPARTED)
            $shipmentService->transitionTo($shipment3, 'IN_TRANSIT', [
                'event_type' => 'DEPARTED',
                'event_time' => Carbon::now()->subHours(3),
                'location_id' => $jakartaPort?->id,
                'description' => 'Vessel departed Tanjung Priok Port, heading north into Java Sea lane.',
            ], $operator);

            // Event 5: Intermediate Maritime Checkpoint (Does NOT change macro status)
            $trackingService->recordIntermediateEvent($shipment3, 'IN_TRANSIT', [
                'event_time' => Carbon::now()->subHours(1),
                'latitude' => -1.2500000,
                'longitude' => 105.1000000,
                'description' => 'Vessel underway using engine, transiting Bangka Strait shipping corridor. Speed 15.4 knots.',
                'weather_condition' => 'Fair Sea Conditions',
                'weather_temp_c' => 29.5,
            ], $operator);
        }
    }
}
