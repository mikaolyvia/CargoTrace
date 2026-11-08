<?php

namespace Tests\Feature;

use App\Models\Location;
use Database\Seeders\LocationSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_location_seeder_populates_major_ports_and_airports(): void
    {
        $this->seed(LocationSeeder::class);

        $this->assertDatabaseHas('locations', [
            'code' => 'IDJKT',
            'name' => 'Port of Tanjung Priok',
            'type' => 'PORT',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
        ]);

        $this->assertDatabaseHas('locations', [
            'code' => 'SGSIN',
            'name' => 'Port of Singapore',
            'type' => 'PORT',
        ]);

        $this->assertDatabaseHas('locations', [
            'code' => 'NLRTM',
            'name' => 'Port of Rotterdam',
            'type' => 'PORT',
        ]);

        $this->assertDatabaseHas('locations', [
            'code' => 'CGK',
            'name' => 'Soekarno-Hatta Air Cargo Terminal',
            'type' => 'AIRPORT',
        ]);
    }

    public function test_location_code_must_be_unique(): void
    {
        Location::factory()->create([
            'code' => 'SUB',
            'name' => 'Port of Tanjung Perak',
        ]);

        $this->expectException(QueryException::class);

        Location::factory()->create([
            'code' => 'SUB',
            'name' => 'Duplicate Location',
        ]);
    }

    public function test_to_lat_lng_helper_returns_valid_leaflet_coordinates(): void
    {
        $location = Location::factory()->create([
            'latitude' => -6.1018,
            'longitude' => 106.8833,
        ]);

        $coords = $location->toLatLng();

        $this->assertIsArray($coords);
        $this->assertCount(2, $coords);
        $this->assertEquals(-6.1018, $coords[0]);
        $this->assertEquals(106.8833, $coords[1]);
    }

    public function test_scope_of_type_filters_locations_correctly(): void
    {
        Location::factory()->port()->create(['code' => 'PRT01']);
        Location::factory()->airport()->create(['code' => 'AIR01']);

        $ports = Location::ofType('PORT')->get();
        $airports = Location::ofType('AIRPORT')->get();

        $this->assertTrue($ports->contains('code', 'PRT01'));
        $this->assertFalse($ports->contains('code', 'AIR01'));

        $this->assertTrue($airports->contains('code', 'AIR01'));
        $this->assertFalse($airports->contains('code', 'PRT01'));
    }

    public function test_location_soft_delete_preserves_records_for_archival(): void
    {
        $location = Location::factory()->create(['code' => 'ARCH1']);
        $locationId = $location->id;

        $location->delete();

        $this->assertNull(Location::find($locationId));
        $this->assertNotNull(Location::withTrashed()->find($locationId));
        $this->assertNotNull(Location::withTrashed()->find($locationId)->deleted_at);
    }
}
