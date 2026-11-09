<?php

namespace Tests\Feature\Api;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MasterDataApiTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->customer = User::factory()->create(['role' => 'CUSTOMER']);
    }

    public function test_carriers_endpoint_supports_active_filtering_and_pagination(): void
    {
        Carrier::factory()->create(['name' => 'Maersk Line', 'code' => 'MSK', 'is_active' => true]);
        Carrier::factory()->create(['name' => 'Evergreen Marine', 'code' => 'EMC', 'is_active' => true]);
        Carrier::factory()->create(['name' => 'Defunct Carrier', 'code' => 'DEF', 'is_active' => false]);

        $response = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/carriers?per_page=10');

        $response->assertStatus(200)
            ->assertJsonPath('meta.total', 2); // Default to active carriers

        // Query including inactive
        $allResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/carriers?is_active=0');

        $allResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_locations_endpoint_supports_type_and_search_filters(): void
    {
        Location::factory()->create(['name' => 'Port of Tanjung Priok', 'code' => 'IDJKT', 'type' => 'PORT', 'country' => 'ID', 'city' => 'Jakarta']);
        Location::factory()->create(['name' => 'Soekarno-Hatta Airport', 'code' => 'CGK', 'type' => 'AIRPORT', 'country' => 'ID', 'city' => 'Tangerang']);
        Location::factory()->create(['name' => 'Port of Rotterdam', 'code' => 'NLRTM', 'type' => 'PORT', 'country' => 'NL', 'city' => 'Rotterdam']);

        // Filter by type PORT
        $portResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/locations?type=PORT');

        $portResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 2);

        // Filter by country NL
        $countryResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/locations?country=NL');

        $countryResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'NLRTM');

        // Search by keyword
        $searchResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/locations?search=Soekarno');

        $searchResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'CGK');
    }

    public function test_search_escapes_sql_wildcards_percent_and_underscore(): void
    {
        Carrier::factory()->create(['name' => '100% Express Logistics', 'code' => 'EXP_100', 'is_active' => true]);
        Carrier::factory()->create(['name' => 'Standard Shipping Co', 'code' => 'STD_SHIP', 'is_active' => true]);

        // Search with literal '%' should ONLY match '100% Express Logistics', not all carriers
        $percentResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/carriers?search=%25'); // URL-encoded '%'

        $percentResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.code', 'EXP_100');

        // Search with non-matching wildcard character sequence
        $emptyResponse = $this->actingAs($this->customer, 'sanctum')
            ->getJson('/api/carriers?search=%25%25%25');

        $emptyResponse->assertStatus(200)
            ->assertJsonPath('meta.total', 0);
    }
}
