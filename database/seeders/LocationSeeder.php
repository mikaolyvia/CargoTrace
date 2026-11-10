<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds major international ports, airports, and hubs with precise coordinates
     * and local currency for Leaflet.js mapping, routing, and valuation.
     */
    public function run(): void
    {
        $locations = [
            [
                'code' => 'IDJKT',
                'name' => 'Port of Tanjung Priok',
                'type' => 'PORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'currency_code' => 'IDR',
                'currency_symbol' => 'Rp',
                'latitude' => -6.1018000,
                'longitude' => 106.8833000,
            ],
            [
                'code' => 'SGSIN',
                'name' => 'Port of Singapore',
                'type' => 'PORT',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'currency_code' => 'SGD',
                'currency_symbol' => 'S$',
                'latitude' => 1.2644000,
                'longitude' => 103.8222000,
            ],
            [
                'code' => 'NLRTM',
                'name' => 'Port of Rotterdam',
                'type' => 'PORT',
                'city' => 'Rotterdam',
                'country' => 'Netherlands',
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'latitude' => 51.9547000,
                'longitude' => 4.1486000,
            ],
            [
                'code' => 'CNSHA',
                'name' => 'Port of Shanghai',
                'type' => 'PORT',
                'city' => 'Shanghai',
                'country' => 'China',
                'currency_code' => 'CNY',
                'currency_symbol' => '¥',
                'latitude' => 31.2304000,
                'longitude' => 121.4737000,
            ],
            [
                'code' => 'DEHAM',
                'name' => 'Port of Hamburg',
                'type' => 'PORT',
                'city' => 'Hamburg',
                'country' => 'Germany',
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'latitude' => 53.5320000,
                'longitude' => 9.9700000,
            ],
            [
                'code' => 'JPTYO',
                'name' => 'Port of Tokyo',
                'type' => 'PORT',
                'city' => 'Tokyo',
                'country' => 'Japan',
                'currency_code' => 'JPY',
                'currency_symbol' => '¥',
                'latitude' => 35.6190000,
                'longitude' => 139.7890000,
            ],
            [
                'code' => 'CGK',
                'name' => 'Soekarno-Hatta Air Cargo Terminal',
                'type' => 'AIRPORT',
                'city' => 'Jakarta',
                'country' => 'Indonesia',
                'currency_code' => 'IDR',
                'currency_symbol' => 'Rp',
                'latitude' => -6.1275000,
                'longitude' => 106.6537000,
            ],
            [
                'code' => 'SIN',
                'name' => 'Changi Air Cargo Complex',
                'type' => 'AIRPORT',
                'city' => 'Singapore',
                'country' => 'Singapore',
                'currency_code' => 'SGD',
                'currency_symbol' => 'S$',
                'latitude' => 1.3644000,
                'longitude' => 103.9915000,
            ],
            [
                'code' => 'AMS',
                'name' => 'Amsterdam Schiphol Logistics Hub',
                'type' => 'AIRPORT',
                'city' => 'Amsterdam',
                'country' => 'Netherlands',
                'currency_code' => 'EUR',
                'currency_symbol' => '€',
                'latitude' => 52.3105000,
                'longitude' => 4.7683000,
            ],
        ];

        foreach ($locations as $loc) {
            Location::updateOrCreate(
                ['code' => $loc['code']],
                $loc
            );
        }
    }
}
