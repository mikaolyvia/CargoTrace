<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    protected string $baseUrl;
    protected int $timeout;
    protected int $connectTimeout;
    protected int $cacheTtl;
    protected bool $enabled;
    protected string $userAgent;

    public function __construct()
    {
        $this->baseUrl = config('services.geocoding.base_url', 'https://nominatim.openstreetmap.org');
        $this->timeout = (int) config('services.geocoding.timeout', 3);
        $this->connectTimeout = (int) config('services.geocoding.connect_timeout', 2);
        $this->cacheTtl = (int) config('services.geocoding.cache_ttl', 86400); // 24 hours
        $this->enabled = (bool) config('services.geocoding.enabled', true);
        $this->userAgent = (string) config('services.geocoding.user_agent', 'CargoTrace-Platform/1.0 (contact@cargotrace.test)');
    }

    /**
     * Forward geocode: convert a location name / address query into coordinates.
     *
     * @param string $query Location query (e.g. "Tanjung Priok, Jakarta")
     * @return array{latitude: float, longitude: float, display_name: string}|null
     */
    public function geocode(string $query): ?array
    {
        $query = trim($query);
        if ($query === '' || ! $this->enabled) {
            return null;
        }

        $cacheKey = 'geocoding:forward:' . md5(mb_strtolower($query));

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($query) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->get(rtrim($this->baseUrl, '/') . '/search', [
                    'q' => $query,
                    'format' => 'jsonv2',
                    'limit' => 1,
                ]);

                if (! $response->successful()) {
                    Log::warning('GeocodingService HTTP search failed', [
                        'status' => $response->status(),
                        'query' => $query,
                    ]);
                    return null;
                }

                $data = $response->json();
                if (! is_array($data) || empty($data) || ! isset($data[0]['lat'], $data[0]['lon'])) {
                    return null;
                }

                $first = $data[0];

                return [
                    'latitude' => round((float) $first['lat'], 7),
                    'longitude' => round((float) $first['lon'], 7),
                    'display_name' => (string) ($first['display_name'] ?? $query),
                ];
            } catch (Exception $e) {
                Log::warning('GeocodingService forward geocoding exception: ' . $e->getMessage(), [
                    'query' => $query,
                ]);
                return null;
            }
        });
    }

    /**
     * Reverse geocode: convert coordinates into a human-readable address / location name.
     *
     * @param float $latitude
     * @param float $longitude
     * @return string|null
     */
    public function reverseGeocode(float $latitude, float $longitude): ?string
    {
        if (! $this->isValidCoordinate($latitude, $longitude) || ! $this->enabled) {
            return null;
        }

        $roundedLat = round($latitude, 5);
        $roundedLng = round($longitude, 5);
        $cacheKey = "geocoding:reverse:{$roundedLat}:{$roundedLng}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($roundedLat, $roundedLng) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => $this->userAgent,
                    'Accept' => 'application/json',
                ])
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->get(rtrim($this->baseUrl, '/') . '/reverse', [
                    'lat' => $roundedLat,
                    'lon' => $roundedLng,
                    'format' => 'jsonv2',
                ]);

                if (! $response->successful()) {
                    Log::warning('GeocodingService HTTP reverse failed', [
                        'status' => $response->status(),
                        'lat' => $roundedLat,
                        'lng' => $roundedLng,
                    ]);
                    return null;
                }

                $data = $response->json();
                if (! is_array($data) || empty($data['display_name'])) {
                    return null;
                }

                return (string) $data['display_name'];
            } catch (Exception $e) {
                Log::warning('GeocodingService reverse geocoding exception: ' . $e->getMessage(), [
                    'lat' => $roundedLat,
                    'lng' => $roundedLng,
                ]);
                return null;
            }
        });
    }

    /**
     * Validate whether latitude and longitude are within standard geographic bounds.
     */
    public function isValidCoordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90.0
            && $latitude <= 90.0
            && $longitude >= -180.0
            && $longitude <= 180.0;
    }
}
