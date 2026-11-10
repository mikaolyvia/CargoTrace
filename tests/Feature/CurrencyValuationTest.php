<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\User;
use App\Services\CurrencyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrencyValuationTest extends TestCase
{
    use RefreshDatabase;

    protected User $operator;
    protected Carrier $carrier;
    protected Location $jakarta;
    protected Location $rotterdam;
    protected Location $singapore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create(['role' => 'OPERATOR']);
        $customer = User::factory()->create(['role' => 'CUSTOMER']);

        $this->carrier = Carrier::factory()->create(['name' => 'Maersk Line', 'code' => 'MSK']);

        $this->jakarta = Location::create([
            'code' => 'IDJKT',
            'name' => 'Port of Tanjung Priok',
            'type' => 'PORT',
            'city' => 'Jakarta',
            'country' => 'Indonesia',
            'currency_code' => 'IDR',
            'currency_symbol' => 'Rp',
            'latitude' => -6.1,
            'longitude' => 106.88,
        ]);

        $this->rotterdam = Location::create([
            'code' => 'NLRTM',
            'name' => 'Port of Rotterdam',
            'type' => 'PORT',
            'city' => 'Rotterdam',
            'country' => 'Netherlands',
            'currency_code' => 'EUR',
            'currency_symbol' => '€',
            'latitude' => 51.95,
            'longitude' => 4.14,
        ]);

        $this->singapore = Location::create([
            'code' => 'SGSIN',
            'name' => 'Port of Singapore',
            'type' => 'PORT',
            'city' => 'Singapore',
            'country' => 'Singapore',
            'currency_code' => 'SGD',
            'currency_symbol' => 'S$',
            'latitude' => 1.26,
            'longitude' => 103.82,
        ]);

        // Create shipment with items and prices
        $shipment = Shipment::create([
            'shipment_number' => 'CT-VAL-001',
            'customer_id' => $customer->id,
            'created_by' => $this->operator->id,
            'carrier_id' => $this->carrier->id,
            'origin_id' => $this->jakarta->id,
            'destination_id' => $this->rotterdam->id,
            'cargo_type' => 'Precision Machinery',
            'currency' => 'USD',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(20),
            'estimated_arrival' => now()->addDays(20),
            'current_status' => 'IN_TRANSIT',
        ]);

        $shipment->items()->createMany([
            [
                'item_name' => 'CNC Milling Machine',
                'hs_code' => '8459.61.00',
                'quantity' => 2,
                'weight_kg' => 250.0,
                'unit_price' => 10000.0,
                'total_price' => 20000.0,
            ],
            [
                'item_name' => 'Precision Cutter Heads',
                'hs_code' => '8466.93.00',
                'quantity' => 10,
                'weight_kg' => 15.0,
                'unit_price' => 500.0,
                'total_price' => 5000.0,
            ],
        ]);

        $shipment->recalculateTotals();
    }

    public function test_currency_service_converts_rates_correctly(): void
    {
        $service = app(CurrencyService::class);

        $idr = $service->convert(100.0, 'USD', 'IDR');
        $this->assertGreaterThan(1000000, $idr);

        $eur = $service->convert(100.0, 'USD', 'EUR');
        $this->assertGreaterThan(50, $eur);

        $formatted = $service->format(1000000, 'IDR');
        $this->assertStringContainsString('Rp', $formatted);

        $formattedEur = $service->format(500.5, 'EUR');
        $this->assertStringContainsString('€', $formattedEur);
    }

    public function test_shipment_recalculates_total_declared_value_from_items(): void
    {
        $shipment = Shipment::where('shipment_number', 'CT-VAL-001')->first();

        $this->assertEquals(25000.0, (float) $shipment->total_declared_value);
        $this->assertEquals('USD', $shipment->currency);
    }

    public function test_tracking_map_supports_regional_search_and_currency_conditioning(): void
    {
        $response = $this->get('/tracking-map?region=Rotterdam');

        $response->assertStatus(200);
        $response->assertSee('CT-VAL-001', false);
        $response->assertSee('Rotterdam', false);
        $response->assertSee('Valuasi Kargo', false);
        $response->assertSee('Mata Uang Negara Tujuan', false);
        $response->assertSee('EUR', false);
    }
}
