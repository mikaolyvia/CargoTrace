<?php

namespace Tests\Feature\ExternalApi;

use App\Services\GeocodingService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GeocodingServiceTest extends TestCase
{
    protected GeocodingService $geocodingService;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->geocodingService = new GeocodingService();
    }

    public function test_forward_geocoding_successfully_returns_coordinates(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '-6.1025000',
                    'lon' => '106.8833000',
                    'display_name' => 'Tanjung Priok, North Jakarta, Jakarta, Indonesia',
                ],
            ], 200),
        ]);

        $result = $this->geocodingService->geocode('Tanjung Priok');

        $this->assertNotNull($result);
        $this->assertEquals(-6.1025, $result['latitude']);
        $this->assertEquals(106.8833, $result['longitude']);
        $this->assertStringContainsString('Tanjung Priok', $result['display_name']);
    }

    public function test_forward_geocoding_returns_null_when_empty_query(): void
    {
        Http::fake();

        $result = $this->geocodingService->geocode('   ');

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_forward_geocoding_returns_null_on_api_failure(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response(null, 500),
        ]);

        $result = $this->geocodingService->geocode('Unknown Island');

        $this->assertNull($result);
    }

    public function test_forward_geocoding_uses_cache_on_subsequent_calls(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/search*' => Http::response([
                [
                    'lat' => '1.2902700',
                    'lon' => '103.8519590',
                    'display_name' => 'Singapore',
                ],
            ], 200),
        ]);

        $first = $this->geocodingService->geocode('Singapore');
        $second = $this->geocodingService->geocode('Singapore');

        $this->assertNotNull($first);
        $this->assertEquals($first, $second);
        Http::assertSentCount(1);
    }

    public function test_reverse_geocoding_successfully_returns_display_name(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => Http::response([
                'display_name' => 'Port of Rotterdam, Netherlands',
            ], 200),
        ]);

        $result = $this->geocodingService->reverseGeocode(51.95, 4.14);

        $this->assertNotNull($result);
        $this->assertEquals('Port of Rotterdam, Netherlands', $result);
    }

    public function test_reverse_geocoding_rejects_invalid_coordinates(): void
    {
        Http::fake();

        $result = $this->geocodingService->reverseGeocode(150.0, 200.0);

        $this->assertNull($result);
        Http::assertNothingSent();
    }

    public function test_reverse_geocoding_handles_timeout_gracefully(): void
    {
        Http::fake([
            'https://nominatim.openstreetmap.org/reverse*' => function () {
                throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
            },
        ]);

        $result = $this->geocodingService->reverseGeocode(-6.10, 106.88);

        $this->assertNull($result);
    }
}
