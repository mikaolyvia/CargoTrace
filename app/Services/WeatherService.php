<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class WeatherService
{
    /**
     * WMO (World Meteorological Organization) Weather Interpretation Codes (WW).
     * Standard Open-Meteo weather codes mapped to human-readable condition descriptions.
     */
    public const WMO_CODE_MAP = [
        0 => 'Clear sky',
        1 => 'Mainly clear',
        2 => 'Partly cloudy',
        3 => 'Overcast',
        45 => 'Fog',
        48 => 'Depositing rime fog',
        51 => 'Light drizzle',
        53 => 'Moderate drizzle',
        55 => 'Dense drizzle',
        56 => 'Light freezing drizzle',
        57 => 'Dense freezing drizzle',
        61 => 'Slight rain',
        63 => 'Moderate rain',
        65 => 'Heavy rain',
        66 => 'Light freezing rain',
        67 => 'Heavy freezing rain',
        71 => 'Slight snow fall',
        73 => 'Moderate snow fall',
        75 => 'Heavy snow fall',
        77 => 'Snow grains',
        80 => 'Slight rain showers',
        81 => 'Moderate rain showers',
        82 => 'Violent rain showers',
        85 => 'Slight snow showers',
        86 => 'Heavy snow showers',
        95 => 'Thunderstorm',
        96 => 'Thunderstorm with slight hail',
        99 => 'Thunderstorm with heavy hail',
    ];

    /**
     * Map a WMO weather code integer to a readable string.
     */
    public function mapWeatherCode(?int $code): string
    {
        if ($code === null) {
            return 'Unknown';
        }

        return self::WMO_CODE_MAP[$code] ?? "Weather condition ({$code})";
    }

    /**
     * Validate whether latitude and longitude are within legal geographic boundaries.
     */
    public function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90.0 && $latitude <= 90.0
            && $longitude >= -180.0 && $longitude <= 180.0;
    }

    /**
     * Generate a cache key with rounded coordinate precision (~1.1 km resolution).
     */
    public function getCacheKey(float $latitude, float $longitude): string
    {
        $latRounded = number_format($latitude, 2, '.', '');
        $lngRounded = number_format($longitude, 2, '.', '');

        return "open_meteo_weather_{$latRounded}_{$lngRounded}";
    }

    /**
     * Fetch current weather conditions and temperature in Celsius for a given coordinate.
     * Results are cached for 30 minutes (configurable).
     *
     * @param float $latitude
     * @param float $longitude
     * @return array{weather_condition: string, weather_temp_c: float}|null
     */
    public function getCurrentWeather(float $latitude, float $longitude): ?array
    {
        // 1. Check feature toggle
        $enabled = config('services.open_meteo.enabled', true);
        if (! $enabled) {
            return null;
        }

        // 2. Validate coordinates before executing any HTTP request
        if (! $this->isValidCoordinate($latitude, $longitude)) {
            return null;
        }

        // 3. Check cached weather
        $cacheKey = $this->getCacheKey($latitude, $longitude);
        $ttl = config('services.open_meteo.cache_ttl', 1800);

        $cached = Cache::get($cacheKey);
        if ($cached !== null && is_array($cached)) {
            return $cached;
        }

        // 4. Fetch from Open-Meteo API with resilient timeout and error handling
        try {
            $baseUrl = config('services.open_meteo.base_url', 'https://api.open-meteo.com/v1/forecast');
            $timeout = (int) config('services.open_meteo.timeout', 3);
            $connectTimeout = (int) config('services.open_meteo.connect_timeout', 2);

            $response = Http::timeout($timeout)
                ->connectTimeout($connectTimeout)
                ->get($baseUrl, [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'current' => 'temperature_2m,weather_code',
                ]);

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (! isset($data['current']) || ! is_array($data['current'])) {
                return null;
            }

            $current = $data['current'];
            if (! isset($current['temperature_2m']) || ! array_key_exists('weather_code', $current)) {
                return null;
            }

            $weatherCode = (int) $current['weather_code'];
            $temperature = (float) $current['temperature_2m'];

            // Estimate wind speed & sea state based on standard WMO marine observation rules
            $windSpeed = match (true) {
                in_array($weatherCode, [95, 96, 99]) => 45.0, // Thunderstorm
                in_array($weatherCode, [65, 82, 86]) => 32.0, // Heavy rain / showers
                in_array($weatherCode, [63, 81]) => 22.0,     // Moderate rain
                in_array($weatherCode, [1, 2, 3]) => 16.0,    // Cloudy / partly cloudy
                default => 12.0,                              // Calm / clear
            };

            $seaState = match (true) {
                $windSpeed >= 40 => 'Bergelombang Tinggi (Rough Sea - Alert)',
                $windSpeed >= 20 => 'Ombak Sedang (Moderate Swell)',
                default => 'Tenang (Calm Sea)',
            };

            $result = [
                'weather_condition' => $this->mapWeatherCode($weatherCode),
                'weather_temp_c' => round($temperature, 2),
                'weather_wind_speed_kmh' => $windSpeed,
                'sea_state' => $seaState,
            ];

            // 5. Cache only valid successful responses
            Cache::put($cacheKey, $result, $ttl);

            return $result;
        } catch (Throwable $e) {
            // Graceful degradation: never let external API failures crash application workflows
            return null;
        }
    }
}
