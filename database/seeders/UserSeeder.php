<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 3 primary demo accounts (Admin, Logistics Operator, Customer)
     * as required by the CargoTrace UAS project specification.
     */
    public function run(): void
    {
        // 1. Admin Demo Account
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

        // 2. Logistics Operator Demo Account
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

        // 3. Customer Demo Account
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
    }
}
