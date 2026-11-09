<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds initial system configurations, including configurable delay thresholds
     * as required by the CargoTrace project specification.
     */
    public function run(): void
    {
        $settings = [
            [
                'key' => 'delay_threshold_low',
                'value' => '6.0',
                'description' => 'Maximum delay hours classified as LOW severity (default: 6.0 hours).',
            ],
            [
                'key' => 'delay_threshold_medium',
                'value' => '24.0',
                'description' => 'Maximum delay hours classified as MEDIUM severity (default: 24.0 hours). Values above this are HIGH.',
            ],
            [
                'key' => 'enable_weather_enrichment',
                'value' => '1',
                'description' => 'Enable or disable automated weather data fetching from Open-Meteo external API.',
            ],
            [
                'key' => 'enable_geocoding_lookup',
                'value' => '1',
                'description' => 'Enable or disable OpenStreetMap Nominatim geocoding lookup.',
            ],
        ];

        foreach ($settings as $setting) {
            SystemSetting::setValue($setting['key'], $setting['value'], $setting['description']);
        }
    }
}
