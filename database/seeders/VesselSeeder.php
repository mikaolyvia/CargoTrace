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
                'last_longitude' => 80.5500000, // Indian Ocean lane south of Sri Lanka towards Bab-el-Mandeb
                'last_speed_knots' => 18.5,
                'last_heading_degrees' => 282.0, // West-Northwest towards Red Sea / Europe
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
                'last_longitude' => 44.0000000, // Bab-el-Mandeb Strait chokepoint
                'last_speed_knots' => 17.2,
                'last_heading_degrees' => 328.0, // North-Northwest entering Red Sea towards Suez Canal
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
                'last_latitude' => 1.8500000,
                'last_longitude' => 104.9000000, // South China Sea exiting Singapore Strait towards East Asia
                'last_speed_knots' => 15.4,
                'last_heading_degrees' => 46.0, // Northeast towards Tokyo / Taiwan Strait
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
                'last_latitude' => -1.8000000,
                'last_longitude' => 107.5000000, // Karimata Strait northbound from Jakarta towards Shanghai
                'last_speed_knots' => 16.8,
                'last_heading_degrees' => 32.0, // North-Northeast heading towards Shanghai
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
                'last_longitude' => 4.1486000, // Berth at Port of Rotterdam
                'last_speed_knots' => 0.0,
                'last_heading_degrees' => 0.0, // Moored at terminal quay
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
