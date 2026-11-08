<?php

namespace Tests\Feature;

use App\Models\Carrier;
use Database\Seeders\CarrierSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CarrierTest extends TestCase
{
    use RefreshDatabase;

    public function test_carrier_seeder_populates_required_global_carriers(): void
    {
        $this->seed(CarrierSeeder::class);

        $this->assertDatabaseHas('carriers', [
            'code' => 'MSK',
            'name' => 'A.P. Moller - Maersk',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('carriers', [
            'code' => 'EMC',
            'name' => 'Evergreen Marine Corporation',
        ]);

        $this->assertDatabaseHas('carriers', [
            'code' => 'SQC',
            'name' => 'Singapore Airlines Cargo',
        ]);

        $this->assertDatabaseHas('carriers', [
            'code' => 'DHL',
            'name' => 'DHL Global Forwarding',
        ]);
    }

    public function test_carrier_code_must_be_unique(): void
    {
        Carrier::factory()->create([
            'code' => 'CMA',
            'name' => 'CMA CGM Group',
        ]);

        $this->expectException(QueryException::class);

        Carrier::factory()->create([
            'code' => 'CMA',
            'name' => 'Duplicate CMA Carrier',
        ]);
    }

    public function test_carrier_soft_delete_preserves_historical_data(): void
    {
        $carrier = Carrier::factory()->create([
            'code' => 'ONE',
            'name' => 'Ocean Network Express',
        ]);

        $carrierId = $carrier->id;
        $carrier->delete();

        // Must not exist in standard active queries
        $this->assertNull(Carrier::find($carrierId));

        // Must still exist in withTrashed queries
        $this->assertNotNull(Carrier::withTrashed()->find($carrierId));
        $this->assertNotNull(Carrier::withTrashed()->find($carrierId)->deleted_at);
    }

    public function test_carrier_active_scope_filters_inactive_records(): void
    {
        Carrier::factory()->create(['code' => 'ACT', 'is_active' => true]);
        Carrier::factory()->inactive()->create(['code' => 'INA']);

        $activeCarriers = Carrier::active()->get();

        $this->assertTrue($activeCarriers->contains('code', 'ACT'));
        $this->assertFalse($activeCarriers->contains('code', 'INA'));
    }
}
