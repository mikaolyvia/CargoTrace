<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds primary demo accounts for Admin, Logistics Operator, and distinct Customers
     * to enforce strict multi-tenant customer isolation and role-based access control.
     */
    public function run(): void
    {
        // 1. Admin Demo Account (Full system oversight, audit logs, carrier configs)
        User::updateOrCreate(
            ['email' => 'admin@cargotrace.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'role' => 'ADMIN',
                'phone' => '+628110000001',
                'email_verified_at' => now(),
            ]
        );

        // 2. Logistics Operator Demo Account (Fleet dispatch, AIS radar, exception resolution)
        User::updateOrCreate(
            ['email' => 'operator@cargotrace.com'],
            [
                'name' => 'Logistics Dispatcher',
                'password' => Hash::make('password'),
                'role' => 'OPERATOR',
                'phone' => '+628110000002',
                'email_verified_at' => now(),
            ]
        );

        // 3. Primary Customer Account (Customer 1 - Owns specific container shipments)
        User::updateOrCreate(
            ['email' => 'customer@cargotrace.com'],
            [
                'name' => 'PT Global Niaga Nusantara',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'phone' => '+628110000003',
                'email_verified_at' => now(),
            ]
        );

        // 4. Secondary Customer Account (Customer 2 - Separate tenant for strict isolation)
        User::updateOrCreate(
            ['email' => 'client.maritime@cargotrace.com'],
            [
                'name' => 'PT Samudera Perkasa Logistik',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'phone' => '+628110000004',
                'email_verified_at' => now(),
            ]
        );

        // 5. Tertiary Customer Account (Customer 3 - Separate tenant)
        User::updateOrCreate(
            ['email' => 'asia.freight@cargotrace.com'],
            [
                'name' => 'Trans-Pacific Freight Ltd',
                'password' => Hash::make('password'),
                'role' => 'CUSTOMER',
                'phone' => '+628110000005',
                'email_verified_at' => now(),
            ]
        );
    }
}
