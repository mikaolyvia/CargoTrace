<?php

namespace Database\Seeders;

use App\Models\Carrier;
use Illuminate\Database\Seeder;

class CarrierSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds major international carriers as required by CargoTrace specification.
     */
    public function run(): void
    {
        $carriers = [
            [
                'code' => 'MSK',
                'name' => 'A.P. Moller - Maersk',
                'contact_email' => 'support@maersk.com',
                'contact_phone' => '+45 3363 3363',
                'is_active' => true,
            ],
            [
                'code' => 'EMC',
                'name' => 'Evergreen Marine Corporation',
                'contact_email' => 'operations@evergreen-marine.com',
                'contact_phone' => '+886 2 2505 7766',
                'is_active' => true,
            ],
            [
                'code' => 'SQC',
                'name' => 'Singapore Airlines Cargo',
                'contact_email' => 'cargo_support@singaporeair.com.sg',
                'contact_phone' => '+65 6542 5588',
                'is_active' => true,
            ],
            [
                'code' => 'DHL',
                'name' => 'DHL Global Forwarding',
                'contact_email' => 'customer.service@dhl.com',
                'contact_phone' => '+49 228 1820',
                'is_active' => true,
            ],
            [
                'code' => 'MSC',
                'name' => 'Mediterranean Shipping Company (MSC)',
                'contact_email' => 'geneva.info@msc.com',
                'contact_phone' => '+41 22 703 8888',
                'is_active' => true,
            ],
        ];

        foreach ($carriers as $carrier) {
            Carrier::updateOrCreate(
                ['code' => $carrier['code']],
                $carrier
            );
        }
    }
}
