<?php

namespace Tests\Feature\ExternalApi;

use App\Services\WeatherService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherServiceTest extends TestCase
{
    private WeatherService $weatherService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        Config::set('services.open_meteo.enabled', true);
        $this->weatherService = app(WeatherService::class);
    }

    public function test_api_success_returns_temperature_and_mapped_weather_condition(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'latitude' => -6.2,
                'longitude' => 106.85,
                'current' => [
                    'time' => '2026-11-10T02:00',
                    'interval' => 900,
                    'temperature_2m' => 28.5,
                    'weather_code' => 0, // Clear sky
                ],
            ], 200),
        ]);

        $result = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);

        $this->assertNotNull($result);
        $this->assertEquals('Clear sky', $result['weather_condition']);
        $this->assertEquals(28.5, $result['weather_temp_c']);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'https://api.open-meteo.com/v1/forecast')
                && $request['current'] === 'temperature_2m,weather_code';
        });
    }

    public function test_wmo_weather_codes_are_mapped_correctly(): void
    {
        $testCases = [
            0 => 'Clear sky',
            2 => 'Partly cloudy',
            3 => 'Overcast',
            45 => 'Fog',
            51 => 'Light drizzle',
            63 => 'Moderate rain',
            65 => 'Heavy rain',
            71 => 'Slight snow fall',
            80 => 'Slight rain showers',
            95 => 'Thunderstorm',
            999 => 'Weather condition (999)', // Unmapped fallback
        ];

        foreach ($testCases as $code => $expectedDescription) {
            $this->assertEquals($expectedDescription, $this->weatherService->mapWeatherCode($code));
        }
    }

    public function test_api_http_error_returns_null_without_crashing(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'error' => true,
                'reason' => 'Internal server error',
            ], 500),
        ]);

        $result = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);

        $this->assertNull($result);
    }

    public function test_api_timeout_or_connection_failure_returns_null_without_crashing(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => function () {
                throw new ConnectionException('Connection timed out after 3000ms');
            },
        ]);

        $result = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);

        $this->assertNull($result);
    }

    public function test_incomplete_or_invalid_api_response_returns_null(): void
    {
        // Missing 'temperature_2m' in current payload
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'weather_code' => 1,
                ],
            ], 200),
        ]);

        $result = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);

        $this->assertNull($result);
    }

    public function test_invalid_coordinates_do_not_trigger_http_request(): void
    {
        Http::fake();

        // Latitude > 90 is invalid
        $result1 = $this->weatherService->getCurrentWeather(95.0, 100.0);
        $this->assertNull($result1);

        // Longitude > 180 is invalid
        $result2 = $this->weatherService->getCurrentWeather(10.0, 200.0);
        $this->assertNull($result2);

        Http::assertNothingSent();
    }

    public function test_successful_weather_result_is_cached(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::sequence()
                ->push([
                    'current' => [
                        'temperature_2m' => 31.0,
                        'weather_code' => 2, // Partly cloudy
                    ],
                ], 200)
                ->push([
                    'current' => [
                        'temperature_2m' => 99.0, // Should never be fetched because of cache
                        'weather_code' => 95,
                    ],
                ], 200),
        ]);

        // First call - triggers HTTP request and caches
        $firstCall = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);
        $this->assertNotNull($firstCall);
        $this->assertEquals(31.0, $firstCall['weather_temp_c']);
        $this->assertEquals('Partly cloudy', $firstCall['weather_condition']);

        // Second call with close coordinates (rounds to same ~1.1km key) - served from cache
        $secondCall = $this->weatherService->getCurrentWeather(-6.2090, 106.8450);
        $this->assertNotNull($secondCall);
        $this->assertEquals(31.0, $secondCall['weather_temp_c']);

        // Assert HTTP request was made only once
        Http::assertSentCount(1);
    }

    public function test_different_coordinates_do_not_share_same_cache(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::sequence()
                ->push([
                    'current' => [
                        'temperature_2m' => 29.0,
                        'weather_code' => 1,
                    ],
                ], 200)
                ->push([
                    'current' => [
                        'temperature_2m' => 14.5,
                        'weather_code' => 61,
                    ],
                ], 200),
        ]);

        // Jakarta coordinates
        $jkt = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);
        $this->assertEquals(29.0, $jkt['weather_temp_c']);

        // Rotterdam coordinates
        $rtm = $this->weatherService->getCurrentWeather(51.9244, 4.4777);
        $this->assertEquals(14.5, $rtm['weather_temp_c']);

        // Both calls should hit HTTP separately
        Http::assertSentCount(2);
    }

    public function test_service_does_not_call_api_when_disabled(): void
    {
        Config::set('services.open_meteo.enabled', false);
        Http::fake();

        $result = $this->weatherService->getCurrentWeather(-6.2088, 106.8456);

        $this->assertNull($result);
        Http::assertNothingSent();
    }
}
