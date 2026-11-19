<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Vessel;
use Illuminate\Database\Seeder;

class VesselSeeder extends Seeder
{
    /**
     * Seed major global container vessels operating on international maritime trade routes.
     */
    public function run(): void
    {
        $maersk = Carrier::where('code', 'MSK')->first();
        $evergreen = Carrier::where('code', 'EMC')->first();
        $msc = Carrier::where('code', 'MSC')->first();
        $dhl = Carrier::where('code', 'DHL')->first();

        $vessels = [
            [
                'carrier_id' => $maersk?->id,
                'name' => 'Madrid Maersk',
                'imo_number' => '9778791',
                'mmsi' => '219836000',
                'flag_country' => 'Denmark',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 20568,
                'status' => 'UNDERWAY',
                'last_latitude' => 5.8500000,
                'last_longitude' => 80.5500000, // Indian Ocean maritime lane
                'last_speed_knots' => 18.5,
                'last_heading_degrees' => 275.0,
                'last_position_at' => now()->subMinutes(12),
            ],
            [
                'carrier_id' => $evergreen?->id,
                'name' => 'Ever Given',
                'imo_number' => '9811000',
                'mmsi' => '353136000',
                'flag_country' => 'Panama',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 20124,
                'status' => 'UNDERWAY',
                'last_latitude' => 12.5000000,
                'last_longitude' => 44.0000000, // Bab-el-Mandeb Strait
                'last_speed_knots' => 17.2,
                'last_heading_degrees' => 315.0,
                'last_position_at' => now()->subMinutes(25),
            ],
            [
                'carrier_id' => $msc?->id,
                'name' => 'MSC Gülsün',
                'imo_number' => '9839438',
                'mmsi' => '353083000',
                'flag_country' => 'Panama',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 23756,
                'status' => 'UNDERWAY',
                'last_latitude' => 1.2500000,
                'last_longitude' => 104.1000000, // Malacca / Singapore Strait
                'last_speed_knots' => 14.8,
                'last_heading_degrees' => 60.0,
                'last_position_at' => now()->subMinutes(8),
            ],
            [
                'carrier_id' => $maersk?->id,
                'name' => 'Munich Maersk',
                'imo_number' => '9778806',
                'mmsi' => '219838000',
                'flag_country' => 'Denmark',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 20568,
                'status' => 'UNDERWAY',
                'last_latitude' => -5.9200000,
                'last_longitude' => 106.1500000, // Sunda Strait / Java Sea
                'last_speed_knots' => 16.4,
                'last_heading_degrees' => 245.0,
                'last_position_at' => now()->subMinutes(5),
            ],
            [
                'carrier_id' => $msc?->id,
                'name' => 'MSC Oscar',
                'imo_number' => '9703291',
                'mmsi' => '355906000',
                'flag_country' => 'Panama',
                'vessel_type' => 'CONTAINER_SHIP',
                'capacity_teu' => 19224,
                'status' => 'MOORED',
                'last_latitude' => 51.9547000,
                'last_longitude' => 4.1486000, // Berth at Rotterdam Port
                'last_speed_knots' => 0.0,
                'last_heading_degrees' => 90.0,
                'last_position_at' => now()->subMinutes(45),
            ],
        ];

        foreach ($vessels as $data) {
            Vessel::updateOrCreate(
                ['imo_number' => $data['imo_number']],
                $data
            );
        }
    }
}
