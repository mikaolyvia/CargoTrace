<?php

namespace App\Console\Commands;

use App\Models\TrackingEvent;
use App\Models\User;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Console\Command;

class SimulateAisTelemetryCommand extends Command
{
    protected $signature = 'ais:simulate-telemetry {--delta=0.02 : Position advancement delta in degrees}';
    protected $description = 'Simulate real-time AIS vessel position advancement and generate telemetry tracking events for active maritime fleet.';

    public function handle(): int
    {
        $vessels = Vessel::where('status', 'UNDERWAY')->get();
        $operator = User::where('role', 'OPERATOR')->first() ?? User::first();
        $updated = 0;

        foreach ($vessels as $vessel) {
            $headingRad = deg2rad($vessel->last_heading_degrees ?? 270);
            $speedFactor = (($vessel->last_speed_knots ?? 16) / 20) * 0.02;

            $dLat = cos($headingRad) * $speedFactor;
            $dLng = sin($headingRad) * $speedFactor;

            $newLat = round($vessel->last_latitude + $dLat, 7);
            $newLng = round($vessel->last_longitude + $dLng, 7);

            $vessel->update([
                'last_latitude' => $newLat,
                'last_longitude' => $newLng,
                'last_position_at' => Carbon::now(),
            ]);

            // Append tracking event to active IN_TRANSIT shipments carried by this vessel
            foreach ($vessel->shipments()->where('current_status', 'IN_TRANSIT')->get() as $shipment) {
                TrackingEvent::create([
                    'shipment_id' => $shipment->id,
                    'location_id' => null,
                    'created_by' => $operator->id,
                    'event_type' => 'IN_TRANSIT',
                    'event_time' => Carbon::now(),
                    'description' => "Pembaruan telemetri AIS otomatis: Kapal {$vessel->name} melaju pada koordinat {$newLat}°, {$newLng}° dengan kecepatan {$vessel->last_speed_knots} knot.",
                    'latitude' => $newLat,
                    'longitude' => $newLng,
                    'weather_condition' => 'Moderate Breeze & Gentle Swell',
                    'weather_temp_c' => 27.5,
                ]);
            }

            $updated++;
        }

        $this->info("Berhasil memperbarui telemetri AIS simulasi untuk {$updated} kapal armada.");
        return Command::SUCCESS;
    }
}
