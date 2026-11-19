<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\Shipment;
use App\Models\TrackingEvent;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class TrackingEventSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds rich, chronological tracking events for all demo shipments.
     */
    public function run(): void
    {
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $shipments = Shipment::with(['origin', 'destination', 'vessel'])->get();

        foreach ($shipments as $shipment) {
            // If already has tracking events, skip to avoid duplicates
            if ($shipment->trackingEvents()->count() > 0) {
                continue;
            }

            $departure = $shipment->expected_departure ?? Carbon::now()->subDays(5);
            $originLoc = $shipment->origin;
            $destLoc = $shipment->destination;
            $vessel = $shipment->vessel;

            // 1. ORDER_CREATED (Always present)
            TrackingEvent::create([
                'shipment_id' => $shipment->id,
                'location_id' => $originLoc->id,
                'created_by' => $operator->id,
                'event_type' => 'ORDER_CREATED',
                'event_time' => $departure->copy()->subDays(2),
                'description' => "Electronic booking order and container allocation initiated for {$shipment->cargo_type}.",
                'latitude' => $originLoc->latitude,
                'longitude' => $originLoc->longitude,
                'weather_condition' => 'Clear Sky',
                'weather_temp_c' => 29.5,
            ]);

            if (in_array($shipment->current_status, ['BOOKED', 'PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'EXCEPTION'])) {
                // 2. BOOKING_CONFIRMED
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $originLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'BOOKING_CONFIRMED',
                    'event_time' => $departure->copy()->subDays(1),
                    'description' => "Ocean slot confirmed on {$shipment->carrier?->name}. Bill of lading issued.",
                    'latitude' => $originLoc->latitude,
                    'longitude' => $originLoc->longitude,
                    'weather_condition' => 'Scattered Clouds',
                    'weather_temp_c' => 30.0,
                ]);
            }

            if (in_array($shipment->current_status, ['PICKED_UP', 'IN_TRANSIT', 'DELIVERED', 'EXCEPTION'])) {
                // 3. PICKED_UP / GATED IN
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $originLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'PICKED_UP',
                    'event_time' => $departure->copy()->subHours(6),
                    'description' => "Container {$shipment->container_number} gated in and inspected at {$originLoc->name} container terminal.",
                    'latitude' => $originLoc->latitude,
                    'longitude' => $originLoc->longitude,
                    'weather_condition' => 'Partly Cloudy',
                    'weather_temp_c' => 28.0,
                ]);
            }

            if (in_array($shipment->current_status, ['IN_TRANSIT', 'DELIVERED', 'EXCEPTION'])) {
                // 4. LOADED ON VESSEL & DEPARTED (IN_TRANSIT)
                $waypointLat = $vessel ? $vessel->last_latitude : ($originLoc->latitude + $destLoc->latitude) / 2;
                $waypointLng = $vessel ? $vessel->last_longitude : ($originLoc->longitude + $destLoc->longitude) / 2;

                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => null,
                    'created_by' => $operator->id,
                    'event_type' => 'IN_TRANSIT',
                    'event_time' => $departure->copy()->addHours(12),
                    'description' => "Vessel {$vessel?->name} underway on international maritime lane. Telemetry active at speed {$vessel?->last_speed_knots} knots.",
                    'latitude' => $waypointLat,
                    'longitude' => $waypointLng,
                    'weather_condition' => 'Ocean Swell & Moderate Breeze',
                    'weather_temp_c' => 26.5,
                ]);
            }

            if ($shipment->current_status === 'DELIVERED') {
                // 5. ARRIVED & UNLOADED (DELIVERED)
                $actual = $shipment->actual_arrival ?? Carbon::now()->subDays(1);
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => $destLoc->id,
                    'created_by' => $operator->id,
                    'event_type' => 'DELIVERED',
                    'event_time' => $actual,
                    'description' => "Container successfully discharged and cleared customs release at {$destLoc->name}.",
                    'latitude' => $destLoc->latitude,
                    'longitude' => $destLoc->longitude,
                    'weather_condition' => 'Light Sea Breeze',
                    'weather_temp_c' => 21.0,
                ]);
            }
        }
    }
}
