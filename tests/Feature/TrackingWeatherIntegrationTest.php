<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\TrackingService;
use App\Services\WeatherService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TrackingWeatherIntegrationTest extends TestCase
{
    private TrackingService $trackingService;
    private Shipment $shipment;
    private User $operator;
    private Location $origin;
    private Location $checkpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate');
        Cache::flush();
        \Illuminate\Support\Facades\Config::set('services.open_meteo.enabled', true);

        $this->trackingService = app(TrackingService::class);
        $this->operator = User::factory()->create(['role' => 'OPERATOR']);
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $carrier = Carrier::factory()->create();

        $this->origin = Location::factory()->create([
            'name' => 'Port of Tanjung Priok',
            'latitude' => -6.1012,
            'longitude' => 106.8833,
        ]);

        $this->checkpoint = Location::factory()->create([
            'name' => 'Port of Singapore',
            'latitude' => 1.2902,
            'longitude' => 103.8519,
        ]);

        $this->shipment = Shipment::create([
            'shipment_number' => 'CT-WEATHER-001',
            'customer_id' => $customer->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $this->origin->id,
            'destination_id' => $this->checkpoint->id,
            'current_location_id' => $this->origin->id,
            'cargo_type' => 'GENERAL',
            'current_status' => 'IN_TRANSIT',
            'expected_departure' => now()->subDay(),
            'expected_arrival' => now()->addDays(3),
            'estimated_arrival' => now()->addDays(3),
        ]);
    }

    public function test_record_event_auto_fetches_weather_when_coordinates_provided(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 29.5,
                    'weather_code' => 0, // Clear sky
                ],
            ], 200),
        ]);

        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'IN_TRANSIT',
            'latitude' => 1.2902,
            'longitude' => 103.8519,
            'description' => 'Passing Singapore Strait checkpoint.',
        ], $this->operator);

        $this->assertEquals('Clear sky', $event->weather_condition);
        $this->assertEquals(29.5, $event->weather_temp_c);

        $this->assertDatabaseHas('tracking_events', [
            'id' => $event->id,
            'weather_condition' => 'Clear sky',
            'weather_temp_c' => 29.5,
        ]);
    }

    public function test_record_event_auto_fetches_weather_from_location_snapshot(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 27.0,
                    'weather_code' => 61, // Slight rain
                ],
            ], 200),
        ]);

        // Provide location_id without explicit coordinates
        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'ARRIVED',
            'location_id' => $this->checkpoint->id,
            'description' => 'Arrived at checkpoint hub.',
        ], $this->operator);

        $this->assertEquals('Slight rain', $event->weather_condition);
        $this->assertEquals(27.0, $event->weather_temp_c);
        $this->assertEquals($this->checkpoint->latitude, (float) $event->latitude);
        $this->assertEquals($this->checkpoint->longitude, (float) $event->longitude);
    }

    public function test_record_event_preserves_manually_supplied_weather_data(): void
    {
        Http::fake(); // If called, fake is available

        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'IN_TRANSIT',
            'latitude' => -6.1012,
            'longitude' => 106.8833,
            'weather_condition' => 'Manual Cyclone Warning',
            'weather_temp_c' => 19.5,
            'description' => 'Severe weather logged by captain.',
        ], $this->operator);

        // Neither condition nor temperature should be overwritten by automated API
        $this->assertEquals('Manual Cyclone Warning', $event->weather_condition);
        $this->assertEquals(19.5, $event->weather_temp_c);

        // Weather API must NOT be called since all weather attributes were manually provided
        Http::assertNothingSent();
    }

    public function test_record_event_preserves_partial_manual_weather(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 32.0,
                    'weather_code' => 2, // Partly cloudy
                ],
            ], 200),
        ]);

        // Manual condition is provided, but temperature is left null
        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'IN_TRANSIT',
            'latitude' => -6.1012,
            'longitude' => 106.8833,
            'weather_condition' => 'Hazy Sunny',
            'description' => 'Manual condition, auto temp.',
        ], $this->operator);

        // Condition is preserved, temperature is auto-fetched
        $this->assertEquals('Hazy Sunny', $event->weather_condition);
        $this->assertEquals(32.0, $event->weather_temp_c);
    }

    public function test_record_event_succeeds_when_weather_api_fails_or_times_out(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => function () {
                throw new ConnectionException('Network timeout connecting to Open-Meteo');
            },
        ]);

        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'IN_TRANSIT',
            'latitude' => -6.1012,
            'longitude' => 106.8833,
            'description' => 'Normal checkpoint, weather API timed out.',
        ], $this->operator);

        // Tracking event MUST be successfully saved even when weather service fails
        $this->assertNotNull($event);
        $this->assertDatabaseHas('tracking_events', [
            'id' => $event->id,
            'description' => 'Normal checkpoint, weather API timed out.',
        ]);
        $this->assertNull($event->weather_condition);
        $this->assertNull($event->weather_temp_c);
    }

    public function test_record_event_does_not_call_weather_api_without_coordinates(): void
    {
        Http::fake();

        // Event recorded with no location_id and no latitude/longitude
        $event = $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'IN_TRANSIT',
            'description' => 'Offshore transit with no coordinates specified.',
        ], $this->operator);

        $this->assertNotNull($event);
        Http::assertNothingSent();
    }

    public function test_backdated_intermediate_event_preserves_chronological_location_protection(): void
    {
        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => Http::response([
                'current' => [
                    'temperature_2m' => 28.0,
                    'weather_code' => 1,
                ],
            ], 200),
        ]);

        $now = now();
        $this->shipment->update(['current_location_id' => $this->checkpoint->id]);

        // Establish latest event at checkpoint (now)
        $this->trackingService->recordEvent($this->shipment, [
            'event_type' => 'ARRIVED',
            'event_time' => $now,
            'location_id' => $this->checkpoint->id,
            'description' => 'Latest arrival at checkpoint.',
        ], $this->operator);

        // Record backdated intermediate event at origin (yesterday)
        $backdatedEvent = $this->trackingService->recordIntermediateEvent($this->shipment, 'IN_TRANSIT', [
            'event_time' => $now->copy()->subDay(),
            'location_id' => $this->origin->id,
            'description' => 'Late logged transit from yesterday.',
        ], $this->operator);

        // Event was recorded with weather
        $this->assertNotNull($backdatedEvent);
        $this->assertEquals(28.0, $backdatedEvent->weather_temp_c);

        // Crucial: current_location_id must REMAIN checkpoint (protection from P11 intact)
        $this->shipment->refresh();
        $this->assertEquals($this->checkpoint->id, $this->shipment->current_location_id);
    }

    public function test_intermediate_event_calls_weather_api_strictly_once_even_when_api_fails(): void
    {
        $callCount = 0;

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => function () use (&$callCount) {
                $callCount++;
                return Http::response(['error' => true], 500);
            },
        ]);

        $event = $this->trackingService->recordIntermediateEvent($this->shipment, 'IN_TRANSIT', [
            'latitude' => 1.2902,
            'longitude' => 103.8519,
            'description' => 'Failing weather test',
        ], $this->operator);

        // Crucial: API must only be called ONCE, not duplicated inside DB transaction
        $this->assertEquals(1, $callCount);
        Http::assertSentCount(1);

        // Event must still be successfully saved
        $this->assertNotNull($event);
        $this->assertDatabaseHas('tracking_events', [
            'id' => $event->id,
            'description' => 'Failing weather test',
        ]);
        $this->assertNull($event->weather_condition);
        $this->assertNull($event->weather_temp_c);
    }

    public function test_weather_http_request_executes_at_transaction_level_zero(): void
    {
        $executedLevel = null;

        Http::fake([
            'https://api.open-meteo.com/v1/forecast*' => function () use (&$executedLevel) {
                $executedLevel = \Illuminate\Support\Facades\DB::transactionLevel();
                return Http::response([
                    'current' => [
                        'temperature_2m' => 26.0,
                        'weather_code' => 0,
                    ],
                ], 200);
            },
        ]);

        $event = $this->trackingService->recordIntermediateEvent($this->shipment, 'IN_TRANSIT', [
            'latitude' => 1.2902,
            'longitude' => 103.8519,
            'location_id' => $this->checkpoint->id,
            'description' => 'Testing transaction level during HTTP call',
        ], $this->operator);

        $this->assertNotNull($event);
        $this->assertSame(0, $executedLevel, 'Weather API request must occur when DB::transactionLevel() is strictly 0');
    }
}
