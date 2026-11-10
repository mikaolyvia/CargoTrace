<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds initial realistic maritime & air shipments connecting customers, carriers, and ports.
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@cargotrace.com')->first();
        $operator = User::where('email', 'operator@cargotrace.com')->first();

        $maersk = Carrier::where('code', 'MSK')->first();
        $evergreen = Carrier::where('code', 'EMC')->first();
        $msc = Carrier::where('code', 'MSC')->first();

        $vesselMadrid = \App\Models\Vessel::where('imo_number', '9778791')->first();
        $vesselGiven = \App\Models\Vessel::where('imo_number', '9811000')->first();
        $vesselGulsun = \App\Models\Vessel::where('imo_number', '9839438')->first();

        $jakartaPort = Location::where('code', 'IDJKT')->first();
        $singaporePort = Location::where('code', 'SGSIN')->first();
        $rotterdamPort = Location::where('code', 'NLRTM')->first();
        $hamburgPort = Location::where('code', 'DEHAM')->first();

        // Shipment 1: Jakarta -> Rotterdam (Maritime Container)
        $shipment1 = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-20261109-0001'],
            [
                'customer_id' => $customer->id,
                'created_by' => $operator->id,
                'carrier_id' => $maersk->id,
                'vessel_id' => $vesselMadrid?->id,
                'container_number' => 'MSKU-7819201',
                'origin_id' => $jakartaPort->id,
                'destination_id' => $rotterdamPort->id,
                'current_location_id' => $jakartaPort->id,
                'cargo_type' => 'Industrial Machinery',
                'current_status' => 'CREATED',
                'expected_departure' => Carbon::now()->addDays(2),
                'expected_arrival' => Carbon::now()->addDays(24),
                'estimated_arrival' => Carbon::now()->addDays(24),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
            ]
        );

        $shipment1->items()->delete();
        $shipment1->items()->createMany([
            [
                'item_name' => 'CNC Milling Machine Parts',
                'hs_code' => '8459.61.00',
                'quantity' => 10,
                'weight_kg' => 150.00,
                'unit_price' => 4500.00,
                'total_price' => 45000.00,
                'length_cm' => 80.00,
                'width_cm' => 60.00,
                'height_cm' => 50.00,
            ],
            [
                'item_name' => 'High-Pressure Hydraulic Cylinders',
                'hs_code' => '8412.21.00',
                'quantity' => 20,
                'weight_kg' => 45.00,
                'unit_price' => 850.00,
                'total_price' => 17000.00,
                'length_cm' => 60.00,
                'width_cm' => 40.00,
                'height_cm' => 30.00,
            ],
        ]);
        $shipment1->currency = 'USD';
        $shipment1->save();
        $shipment1->recalculateTotals();

        // Shipment 2: Singapore -> Hamburg (Maritime Cargo)
        $shipment2 = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-20261109-0002'],
            [
                'customer_id' => $customer->id,
                'created_by' => $operator->id,
                'carrier_id' => $evergreen->id,
                'vessel_id' => $vesselGiven?->id,
                'container_number' => 'EGLV-3104928',
                'origin_id' => $singaporePort->id,
                'destination_id' => $hamburgPort->id,
                'current_location_id' => $singaporePort->id,
                'cargo_type' => 'Consumer Electronics',
                'current_status' => 'CREATED',
                'expected_departure' => Carbon::now()->addDays(1),
                'expected_arrival' => Carbon::now()->addDays(20),
                'estimated_arrival' => Carbon::now()->addDays(20),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
            ]
        );

        $shipment2->items()->delete();
        $shipment2->items()->createMany([
            [
                'item_name' => 'Lithium-ion Battery Modules',
                'hs_code' => '8507.60.00',
                'quantity' => 100,
                'weight_kg' => 5.50,
                'unit_price' => 280.00,
                'total_price' => 28000.00,
                'length_cm' => 30.00,
                'width_cm' => 20.00,
                'height_cm' => 15.00,
            ],
            [
                'item_name' => 'Smart Solar Inverters',
                'hs_code' => '8504.40.00',
                'quantity' => 40,
                'weight_kg' => 12.00,
                'unit_price' => 1250.00,
                'total_price' => 50000.00,
                'length_cm' => 45.00,
                'width_cm' => 35.00,
                'height_cm' => 25.00,
            ],
        ]);
        $shipment2->currency = 'USD';
        $shipment2->save();
        $shipment2->recalculateTotals();

        // Shipment 3: Jakarta -> Singapore (Ocean Feeder Liner)
        $shipment3 = Shipment::updateOrCreate(
            ['shipment_number' => 'CT-20261109-0003'],
            [
                'customer_id' => $customer->id,
                'created_by' => $operator->id,
                'carrier_id' => $msc->id,
                'vessel_id' => $vesselGulsun?->id,
                'container_number' => 'MSCU-8291043',
                'origin_id' => $jakartaPort->id,
                'destination_id' => $singaporePort->id,
                'current_location_id' => $jakartaPort->id,
                'cargo_type' => 'Precision Semiconductor Wafers',
                'current_status' => 'CREATED',
                'expected_departure' => Carbon::now()->addHours(12),
                'expected_arrival' => Carbon::now()->addHours(48),
                'estimated_arrival' => Carbon::now()->addHours(48),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
            ]
        );

        $shipment3->items()->delete();
        $shipment3->items()->createMany([
            [
                'item_name' => 'Cleanroom Vacuum-Sealed Wafer Containers',
                'hs_code' => '3818.00.00',
                'quantity' => 5,
                'weight_kg' => 22.00,
                'unit_price' => 7200.00,
                'total_price' => 36000.00,
                'length_cm' => 50.00,
                'width_cm' => 50.00,
                'height_cm' => 40.00,
            ],
        ]);
        $shipment3->currency = 'USD';
        $shipment3->save();
        $shipment3->recalculateTotals();
    }
}
