<?php

namespace Database\Seeders;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Vessel;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ShipmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 24 diverse, realistic maritime container shipments with comprehensive statuses,
     * valuations, items, carriers, and delay classifications.
     */
    public function run(): void
    {
        $customer = User::where('email', 'customer@cargotrace.com')->first();
        $operator = User::where('email', 'operator@cargotrace.com')->first();

        $maersk = Carrier::where('code', 'MSK')->first();
        $evergreen = Carrier::where('code', 'EMC')->first();
        $msc = Carrier::where('code', 'MSC')->first();
        $dhl = Carrier::where('code', 'DHL')->first();
        $sqc = Carrier::where('code', 'SQC')->first();

        $vesselMadrid = Vessel::where('imo_number', '9778791')->first();
        $vesselGiven = Vessel::where('imo_number', '9811000')->first();
        $vesselGulsun = Vessel::where('imo_number', '9839438')->first();
        $vesselMunich = Vessel::where('imo_number', '9778806')->first();
        $vesselOscar = Vessel::where('imo_number', '9703291')->first();

        $jakartaPort = Location::where('code', 'IDJKT')->first();
        $singaporePort = Location::where('code', 'SGSIN')->first();
        $rotterdamPort = Location::where('code', 'NLRTM')->first();
        $shanghaiPort = Location::where('code', 'CNSHA')->first();
        $hamburgPort = Location::where('code', 'DEHAM')->first();
        $tokyoPort = Location::where('code', 'JPTYO')->first();

        $shipmentsData = [
            // ================= CREATED (3 Shipments) =================
            [
                'number' => 'CT-20261109-0001',
                'container' => 'MSKU-7819201',
                'carrier' => $maersk,
                'vessel' => $vesselMadrid,
                'origin' => $jakartaPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Industrial Machinery',
                'status' => 'CREATED',
                'expected_departure' => Carbon::now()->addDays(2),
                'expected_arrival' => Carbon::now()->addDays(24),
                'estimated_arrival' => Carbon::now()->addDays(24),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'CNC Milling Machine Parts', 'hs_code' => '8459.61.00', 'quantity' => 10, 'weight_kg' => 150.00, 'unit_price' => 4500.00, 'total_price' => 45000.00, 'l' => 80, 'w' => 60, 'h' => 50],
                    ['item_name' => 'High-Pressure Hydraulic Cylinders', 'hs_code' => '8412.21.00', 'quantity' => 20, 'weight_kg' => 45.00, 'unit_price' => 850.00, 'total_price' => 17000.00, 'l' => 60, 'w' => 40, 'h' => 30],
                ],
            ],

            // ================= IN_TRANSIT (9 Shipments) =================
            [
                'number' => 'CT-20261109-0002',
                'container' => 'EGLV-3104928',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $singaporePort,
                'destination' => $hamburgPort,
                'cargo_type' => 'Consumer Electronics',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(10),
                'expected_arrival' => Carbon::now()->addDays(8),
                'estimated_arrival' => Carbon::now()->addDays(8)->addHours(18), // 18 hours delayed (MEDIUM)
                'actual_arrival' => null,
                'is_delayed' => true,
                'delay_hours' => 18.00,
                'delay_severity' => 'MEDIUM',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Lithium-ion Battery Modules', 'hs_code' => '8507.60.00', 'quantity' => 100, 'weight_kg' => 5.50, 'unit_price' => 280.00, 'total_price' => 28000.00, 'l' => 30, 'w' => 20, 'h' => 15],
                    ['item_name' => 'Smart Solar Inverters', 'hs_code' => '8504.40.00', 'quantity' => 40, 'weight_kg' => 12.00, 'unit_price' => 1250.00, 'total_price' => 50000.00, 'l' => 45, 'w' => 35, 'h' => 25],
                ],
            ],
            [
                'number' => 'CT-20261109-0003',
                'container' => 'MSCU-8291043',
                'carrier' => $msc,
                'vessel' => $vesselGulsun,
                'origin' => $jakartaPort,
                'destination' => $singaporePort,
                'cargo_type' => 'Precision Semiconductor Wafers',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(1),
                'expected_arrival' => Carbon::now()->addHours(20),
                'estimated_arrival' => Carbon::now()->addHours(20),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Cleanroom Vacuum-Sealed Wafer Containers', 'hs_code' => '3818.00.00', 'quantity' => 5, 'weight_kg' => 22.00, 'unit_price' => 7200.00, 'total_price' => 36000.00, 'l' => 50, 'w' => 50, 'h' => 40],
                ],
            ],
            [
                'number' => 'CT-20261109-0004',
                'container' => 'MSKU-9921402',
                'carrier' => $maersk,
                'vessel' => $vesselMunich,
                'origin' => $jakartaPort,
                'destination' => $shanghaiPort,
                'cargo_type' => 'Automotive Transmission Assemblies',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(3),
                'expected_arrival' => Carbon::now()->addDays(5),
                'estimated_arrival' => Carbon::now()->addDays(5),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Automatic Gearbox Assemblies', 'hs_code' => '8708.40.11', 'quantity' => 15, 'weight_kg' => 85.00, 'unit_price' => 3200.00, 'total_price' => 48000.00, 'l' => 70, 'w' => 50, 'h' => 60],
                ],
            ],
            [
                'number' => 'CT-20261109-0005',
                'container' => 'EGLV-8820145',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $shanghaiPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Photovoltaic Solar Modules',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(12),
                'expected_arrival' => Carbon::now()->addDays(10),
                'estimated_arrival' => Carbon::now()->addDays(11)->addHours(8), // 32 hours delay (HIGH)
                'actual_arrival' => null,
                'is_delayed' => true,
                'delay_hours' => 32.00,
                'delay_severity' => 'HIGH',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Bifacial Monocrystalline Solar Panels', 'hs_code' => '8541.43.00', 'quantity' => 120, 'weight_kg' => 24.50, 'unit_price' => 195.00, 'total_price' => 23400.00, 'l' => 220, 'w' => 110, 'h' => 4],
                ],
            ],
            [
                'number' => 'CT-20261109-0006',
                'container' => 'MSCU-1092834',
                'carrier' => $msc,
                'vessel' => $vesselGulsun,
                'origin' => $singaporePort,
                'destination' => $tokyoPort,
                'cargo_type' => 'Medical Diagnostic Imaging Systems',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(2),
                'expected_arrival' => Carbon::now()->addDays(6),
                'estimated_arrival' => Carbon::now()->addDays(6),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Portable Ultrasound Transducer Units', 'hs_code' => '9018.12.00', 'quantity' => 8, 'weight_kg' => 45.00, 'unit_price' => 9500.00, 'total_price' => 76000.00, 'l' => 90, 'w' => 60, 'h' => 70],
                ],
            ],
            [
                'number' => 'CT-20261109-0007',
                'container' => 'MSKU-4401928',
                'carrier' => $maersk,
                'vessel' => $vesselMadrid,
                'origin' => $shanghaiPort,
                'destination' => $hamburgPort,
                'cargo_type' => 'Robotic Automation Arms',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(8),
                'expected_arrival' => Carbon::now()->addDays(12),
                'estimated_arrival' => Carbon::now()->addDays(12),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => '6-Axis Industrial Robotic Manipulators', 'hs_code' => '8479.50.00', 'quantity' => 4, 'weight_kg' => 310.00, 'unit_price' => 22000.00, 'total_price' => 88000.00, 'l' => 120, 'w' => 100, 'h' => 140],
                ],
            ],
            [
                'number' => 'CT-20261109-0008',
                'container' => 'DHLU-6712039',
                'carrier' => $dhl,
                'vessel' => $vesselMunich,
                'origin' => $jakartaPort,
                'destination' => $singaporePort,
                'cargo_type' => 'Specialty Chemicals & Polymers',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(1),
                'expected_arrival' => Carbon::now()->addDays(1),
                'estimated_arrival' => Carbon::now()->addDays(1),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Polyethylene Terephthalate Resin Drums', 'hs_code' => '3907.61.00', 'quantity' => 50, 'weight_kg' => 200.00, 'unit_price' => 320.00, 'total_price' => 16000.00, 'l' => 60, 'w' => 60, 'h' => 90],
                ],
            ],

            // ================= DELIVERED (6 Shipments) =================
            [
                'number' => 'CT-20261109-0009',
                'container' => 'MSCU-3029182',
                'carrier' => $msc,
                'vessel' => $vesselOscar,
                'origin' => $jakartaPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Organic Arabica Green Coffee Beans',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(28),
                'expected_arrival' => Carbon::now()->subDays(3),
                'estimated_arrival' => Carbon::now()->subDays(3),
                'actual_arrival' => Carbon::now()->subDays(3)->addHours(4), // 4h delay (LOW)
                'is_delayed' => true,
                'delay_hours' => 4.00,
                'delay_severity' => 'LOW',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Sumatra Mandheling Grade 1 Coffee Beans', 'hs_code' => '0901.11.00', 'quantity' => 300, 'weight_kg' => 60.00, 'unit_price' => 380.00, 'total_price' => 114000.00, 'l' => 70, 'w' => 50, 'h' => 30],
                ],
            ],
            [
                'number' => 'CT-20261109-0010',
                'container' => 'MSKU-8192034',
                'carrier' => $maersk,
                'vessel' => $vesselOscar,
                'origin' => $singaporePort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Aircraft Turbofan Avionics',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(25),
                'expected_arrival' => Carbon::now()->subDays(2),
                'estimated_arrival' => Carbon::now()->subDays(2),
                'actual_arrival' => Carbon::now()->subDays(2), // On schedule
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Flight Management Computer Modules', 'hs_code' => '8803.30.00', 'quantity' => 6, 'weight_kg' => 18.00, 'unit_price' => 14500.00, 'total_price' => 87000.00, 'l' => 40, 'w' => 40, 'h' => 35],
                ],
            ],
            [
                'number' => 'CT-20261109-0011',
                'container' => 'EGLV-4910293',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $shanghaiPort,
                'destination' => $hamburgPort,
                'cargo_type' => 'Fiber Optic Telecommunication Cable',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(30),
                'expected_arrival' => Carbon::now()->subDays(5),
                'estimated_arrival' => Carbon::now()->subDays(5),
                'actual_arrival' => Carbon::now()->subDays(5),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Armored Submarine Fiber Optic Spools', 'hs_code' => '8544.70.00', 'quantity' => 12, 'weight_kg' => 450.00, 'unit_price' => 3900.00, 'total_price' => 46800.00, 'l' => 120, 'w' => 120, 'h' => 100],
                ],
            ],
            [
                'number' => 'CT-20261109-0012',
                'container' => 'SQCU-1192834',
                'carrier' => $sqc,
                'vessel' => $vesselGulsun,
                'origin' => $jakartaPort,
                'destination' => $singaporePort,
                'cargo_type' => 'Pharmaceutical Vaccines (Cold-Chain)',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(5),
                'expected_arrival' => Carbon::now()->subDays(3),
                'estimated_arrival' => Carbon::now()->subDays(3),
                'actual_arrival' => Carbon::now()->subDays(3),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Temperature-Monitored Vaccine Vials', 'hs_code' => '3002.41.00', 'quantity' => 50, 'weight_kg' => 8.00, 'unit_price' => 1800.00, 'total_price' => 90000.00, 'l' => 40, 'w' => 30, 'h' => 30],
                ],
            ],
            [
                'number' => 'CT-20261109-0013',
                'container' => 'MSKU-6629104',
                'carrier' => $maersk,
                'vessel' => $vesselMadrid,
                'origin' => $shanghaiPort,
                'destination' => $tokyoPort,
                'cargo_type' => 'Precision Optical Lenses',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(7),
                'expected_arrival' => Carbon::now()->subDays(2),
                'estimated_arrival' => Carbon::now()->subDays(2),
                'actual_arrival' => Carbon::now()->subDays(2),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Coated Quartz Stepper Projection Lenses', 'hs_code' => '9001.90.00', 'quantity' => 15, 'weight_kg' => 12.00, 'unit_price' => 4200.00, 'total_price' => 63000.00, 'l' => 30, 'w' => 30, 'h' => 20],
                ],
            ],
            [
                'number' => 'CT-20261109-0014',
                'container' => 'MSCU-5510294',
                'carrier' => $msc,
                'vessel' => $vesselOscar,
                'origin' => $jakartaPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Refined Sustainable Palm Oil',
                'status' => 'DELIVERED',
                'expected_departure' => Carbon::now()->subDays(26),
                'expected_arrival' => Carbon::now()->subDays(1),
                'estimated_arrival' => Carbon::now()->subDays(1),
                'actual_arrival' => Carbon::now()->subDays(1),
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'RSPO-Certified RBD Palm Olein Flexitanks', 'hs_code' => '1511.90.20', 'quantity' => 2, 'weight_kg' => 21000.00, 'unit_price' => 24000.00, 'total_price' => 48000.00, 'l' => 600, 'w' => 240, 'h' => 240],
                ],
            ],

            // ================= BOOKED (3 Shipments) =================
            [
                'number' => 'CT-20261109-0015',
                'container' => 'MSKU-2291048',
                'carrier' => $maersk,
                'vessel' => $vesselMunich,
                'origin' => $jakartaPort,
                'destination' => $shanghaiPort,
                'cargo_type' => 'Raw Nickel Matte & EV Cathodes',
                'status' => 'BOOKED',
                'expected_departure' => Carbon::now()->addDays(2),
                'expected_arrival' => Carbon::now()->addDays(10),
                'estimated_arrival' => Carbon::now()->addDays(10),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Battery-Grade Refined Nickel Briquettes', 'hs_code' => '7502.10.00', 'quantity' => 20, 'weight_kg' => 1000.00, 'unit_price' => 16500.00, 'total_price' => 330000.00, 'l' => 100, 'w' => 100, 'h' => 80],
                ],
            ],
            [
                'number' => 'CT-20261109-0016',
                'container' => 'EGLV-9938102',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $singaporePort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Commercial Kitchen Equipment',
                'status' => 'BOOKED',
                'expected_departure' => Carbon::now()->addDays(3),
                'expected_arrival' => Carbon::now()->addDays(22),
                'estimated_arrival' => Carbon::now()->addDays(22),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Stainless Steel Induction Ranges', 'hs_code' => '8419.81.00', 'quantity' => 14, 'weight_kg' => 95.00, 'unit_price' => 2100.00, 'total_price' => 29400.00, 'l' => 80, 'w' => 70, 'h' => 85],
                ],
            ],
            [
                'number' => 'CT-20261109-0017',
                'container' => 'SQCU-7719284',
                'carrier' => $sqc,
                'vessel' => $vesselGulsun,
                'origin' => $shanghaiPort,
                'destination' => $tokyoPort,
                'cargo_type' => 'High-Precision Machine Bearings',
                'status' => 'BOOKED',
                'expected_departure' => Carbon::now()->addDays(1),
                'expected_arrival' => Carbon::now()->addDays(5),
                'estimated_arrival' => Carbon::now()->addDays(5),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Ceramic Deep Groove Ball Bearings', 'hs_code' => '8482.10.00', 'quantity' => 200, 'weight_kg' => 1.20, 'unit_price' => 95.00, 'total_price' => 19000.00, 'l' => 15, 'w' => 15, 'h' => 10],
                ],
            ],

            // ================= PICKED_UP (3 Shipments) =================
            [
                'number' => 'CT-20261109-0018',
                'container' => 'MSKU-5102938',
                'carrier' => $maersk,
                'vessel' => $vesselMunich,
                'origin' => $jakartaPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Apparel & Technical Sportswear',
                'status' => 'PICKED_UP',
                'expected_departure' => Carbon::now()->addHours(12),
                'expected_arrival' => Carbon::now()->addDays(20),
                'estimated_arrival' => Carbon::now()->addDays(20),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Waterproof Breathable Sailing Jackets', 'hs_code' => '6201.40.00', 'quantity' => 500, 'weight_kg' => 0.85, 'unit_price' => 75.00, 'total_price' => 37500.00, 'l' => 60, 'w' => 40, 'h' => 40],
                ],
            ],
            [
                'number' => 'CT-20261109-0019',
                'container' => 'EGLV-8839201',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $singaporePort,
                'destination' => $hamburgPort,
                'cargo_type' => 'Industrial Server Rack Hardware',
                'status' => 'PICKED_UP',
                'expected_departure' => Carbon::now()->addHours(18),
                'expected_arrival' => Carbon::now()->addDays(19),
                'estimated_arrival' => Carbon::now()->addDays(19),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => '42U Enterprise Datacenter Blade Enclosures', 'hs_code' => '8473.30.00', 'quantity' => 10, 'weight_kg' => 110.00, 'unit_price' => 4800.00, 'total_price' => 48000.00, 'l' => 100, 'w' => 60, 'h' => 200],
                ],
            ],
            [
                'number' => 'CT-20261109-0020',
                'container' => 'MSCU-1284920',
                'carrier' => $msc,
                'vessel' => $vesselGulsun,
                'origin' => $shanghaiPort,
                'destination' => $singaporePort,
                'cargo_type' => 'Smart Home IoT Automation Hubs',
                'status' => 'PICKED_UP',
                'expected_departure' => Carbon::now()->addHours(8),
                'expected_arrival' => Carbon::now()->addDays(5),
                'estimated_arrival' => Carbon::now()->addDays(5),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Zigbee 3.0 Wireless Gateway Controllers', 'hs_code' => '8517.62.00', 'quantity' => 800, 'weight_kg' => 0.25, 'unit_price' => 35.00, 'total_price' => 28000.00, 'l' => 20, 'w' => 15, 'h' => 10],
                ],
            ],

            // ================= CREATED (2 Shipments) =================
            [
                'number' => 'CT-20261109-0021',
                'container' => 'MSKU-1029384',
                'carrier' => $maersk,
                'vessel' => $vesselMadrid,
                'origin' => $jakartaPort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Custom Handcrafted Teak Furniture',
                'status' => 'CREATED',
                'expected_departure' => Carbon::now()->addDays(4),
                'expected_arrival' => Carbon::now()->addDays(25),
                'estimated_arrival' => Carbon::now()->addDays(25),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Solid Teak Dining Table Sets', 'hs_code' => '9403.60.00', 'quantity' => 8, 'weight_kg' => 90.00, 'unit_price' => 1800.00, 'total_price' => 14400.00, 'l' => 200, 'w' => 100, 'h' => 75],
                ],
            ],
            [
                'number' => 'CT-20261109-0022',
                'container' => 'EGLV-7710293',
                'carrier' => $evergreen,
                'vessel' => $vesselGiven,
                'origin' => $shanghaiPort,
                'destination' => $hamburgPort,
                'cargo_type' => 'Automotive Headlamp Assemblies',
                'status' => 'CREATED',
                'expected_departure' => Carbon::now()->addDays(5),
                'expected_arrival' => Carbon::now()->addDays(24),
                'estimated_arrival' => Carbon::now()->addDays(24),
                'actual_arrival' => null,
                'is_delayed' => false,
                'delay_hours' => 0.00,
                'delay_severity' => 'NONE',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Matrix Adaptive LED Projector Headlamps', 'hs_code' => '8512.20.00', 'quantity' => 100, 'weight_kg' => 4.50, 'unit_price' => 340.00, 'total_price' => 34000.00, 'l' => 50, 'w' => 35, 'h' => 30],
                ],
            ],

            // ================= IN_TRANSIT with Disruption/Exception (2 Shipments) =================
            [
                'number' => 'CT-20261109-0023',
                'container' => 'MSCU-9018273',
                'carrier' => $msc,
                'vessel' => $vesselGulsun,
                'origin' => $singaporePort,
                'destination' => $rotterdamPort,
                'cargo_type' => 'Specialized Battery Storage Chemistry',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(4),
                'expected_arrival' => Carbon::now()->addDays(15),
                'estimated_arrival' => Carbon::now()->addDays(16)->addHours(4), // 28h delay (HIGH)
                'actual_arrival' => null,
                'is_delayed' => true,
                'delay_hours' => 28.00,
                'delay_severity' => 'HIGH',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'Solid-State Battery Electrolyte Material', 'hs_code' => '2836.91.00', 'quantity' => 25, 'weight_kg' => 50.00, 'unit_price' => 2900.00, 'total_price' => 72500.00, 'l' => 60, 'w' => 50, 'h' => 40],
                ],
            ],
            [
                'number' => 'CT-20261109-0024',
                'container' => 'MSKU-8829103',
                'carrier' => $maersk,
                'vessel' => $vesselMadrid,
                'origin' => $jakartaPort,
                'destination' => $tokyoPort,
                'cargo_type' => 'Industrial Robotics Controllers',
                'status' => 'IN_TRANSIT',
                'expected_departure' => Carbon::now()->subDays(3),
                'expected_arrival' => Carbon::now()->addDays(7),
                'estimated_arrival' => Carbon::now()->addDays(7)->addHours(10), // 10h delay (MEDIUM)
                'actual_arrival' => null,
                'is_delayed' => true,
                'delay_hours' => 10.00,
                'delay_severity' => 'MEDIUM',
                'currency' => 'USD',
                'items' => [
                    ['item_name' => 'High-Speed CNC Motion Controllers', 'hs_code' => '8537.10.00', 'quantity' => 12, 'weight_kg' => 15.00, 'unit_price' => 4100.00, 'total_price' => 49200.00, 'l' => 40, 'w' => 30, 'h' => 25],
                ],
            ],
        ];

        $customer1 = User::where('email', 'customer@cargotrace.com')->first();
        $customer2 = User::where('email', 'client.maritime@cargotrace.com')->first() ?? $customer1;
        $customer3 = User::where('email', 'asia.freight@cargotrace.com')->first() ?? $customer1;

        foreach ($shipmentsData as $index => $data) {
            // Assign distinct customer ownership for strict RBAC multi-tenant data isolation:
            // Customer 1 (PT Global Niaga Nusantara - customer@cargotrace.com) owns 7 shipments.
            // Customer 2 (PT Samudera Perkasa) owns 8 shipments.
            // Customer 3 (Trans-Pacific Freight) owns 9 shipments.
            $assignedCustomer = match ($index) {
                0, 1, 2, 6, 10, 14, 19 => $customer1,
                3, 4, 7, 8, 12, 15, 17, 21 => $customer2,
                default => $customer3,
            };

            $shipment = Shipment::updateOrCreate(
                ['shipment_number' => $data['number']],
                [
                    'customer_id' => $assignedCustomer->id,
                    'created_by' => $operator->id,
                    'carrier_id' => $data['carrier']?->id,
                    'vessel_id' => $data['vessel']?->id,
                    'container_number' => $data['container'],
                    'origin_id' => $data['origin']->id,
                    'destination_id' => $data['destination']->id,
                    'current_location_id' => $data['origin']->id,
                    'cargo_type' => $data['cargo_type'],
                    'current_status' => $data['status'],
                    'expected_departure' => $data['expected_departure'],
                    'expected_arrival' => $data['expected_arrival'],
                    'estimated_arrival' => $data['estimated_arrival'],
                    'actual_arrival' => $data['actual_arrival'],
                    'is_delayed' => $data['is_delayed'],
                    'delay_hours' => $data['delay_hours'],
                    'delay_severity' => $data['delay_severity'],
                    'currency' => $data['currency'],
                ]
            );

            $shipment->items()->delete();
            foreach ($data['items'] as $it) {
                $shipment->items()->create([
                    'item_name' => $it['item_name'],
                    'hs_code' => $it['hs_code'],
                    'quantity' => $it['quantity'],
                    'weight_kg' => $it['weight_kg'],
                    'unit_price' => $it['unit_price'],
                    'total_price' => $it['total_price'],
                    'length_cm' => $it['l'],
                    'width_cm' => $it['w'],
                    'height_cm' => $it['h'],
                ]);
            }

            $shipment->recalculateTotals();
        }
    }
}
