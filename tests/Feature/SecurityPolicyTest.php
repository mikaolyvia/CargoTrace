<?php

namespace Tests\Feature;

use App\Models\Carrier;
use App\Models\Location;
use App\Models\Shipment;
use App\Models\ShipmentException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Register temporary test routes to verify RoleMiddleware behavior
        Route::get('/_test/admin-only', function () {
            return response()->json(['success' => true, 'message' => 'admin passed']);
        })->middleware(['auth:sanctum', 'role:ADMIN']);

        Route::get('/_test/ops-or-admin', function () {
            return response()->json(['success' => true, 'message' => 'ops or admin passed']);
        })->middleware(['auth:sanctum', 'role:ADMIN,OPERATOR']);
    }

    public function test_role_middleware_blocks_unauthenticated_request(): void
    {
        $response = $this->getJson('/_test/admin-only');

        $response->assertStatus(401);
    }

    public function test_role_middleware_blocks_unauthorized_role_with_consistent_json(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);

        $response = $this->actingAs($customer, 'sanctum')->getJson('/_test/admin-only');

        $response->assertStatus(403)
            ->assertJson([
                'success' => false,
                'message' => 'This action is unauthorized.',
            ]);
    }

    public function test_role_middleware_allows_authorized_single_role(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN']);

        $response = $this->actingAs($admin, 'sanctum')->getJson('/_test/admin-only');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'admin passed',
            ]);
    }

    public function test_role_middleware_allows_authorized_multiple_roles(): void
    {
        $operator = User::factory()->create(['role' => 'OPERATOR']);

        $response = $this->actingAs($operator, 'sanctum')->getJson('/_test/ops-or-admin');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'ops or admin passed',
            ]);
    }

    public function test_shipment_policy_allows_customer_to_view_only_own_shipment(): void
    {
        $customer1 = User::factory()->create(['role' => 'CUSTOMER']);
        $customer2 = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $carrier = Carrier::first() ?? Carrier::factory()->create();
        $origin = Location::first() ?? Location::factory()->create();
        $dest = Location::skip(1)->first() ?? Location::factory()->create();

        $shipment1 = Shipment::create([
            'shipment_number' => 'CT-TEST-0001',
            'customer_id' => $customer1->id,
            'created_by' => $operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'GENERAL',
            'total_weight_kg' => 100,
            'total_volume_cbm' => 1,
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
        ]);

        // Customer 1 can view own shipment
        $this->assertTrue(Gate::forUser($customer1)->allows('view', $shipment1));

        // Customer 2 CANNOT view Customer 1 shipment (Anti-IDOR)
        $this->assertFalse(Gate::forUser($customer2)->allows('view', $shipment1));

        // Operator and Admin can view
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $this->assertTrue(Gate::forUser($operator)->allows('view', $shipment1));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $shipment1));

        // Clean up
        $shipment1->forceDelete();
        $customer1->delete();
        $customer2->delete();
        $operator->delete();
        $admin->delete();
    }

    public function test_shipment_policy_creation_and_deletion_permissions(): void
    {
        $customer = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $carrier = Carrier::first() ?? Carrier::factory()->create();
        $origin = Location::first() ?? Location::factory()->create();
        $dest = Location::skip(1)->first() ?? Location::factory()->create();

        $shipment = Shipment::create([
            'shipment_number' => 'CT-TEST-0002',
            'customer_id' => $customer->id,
            'created_by' => $operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'GENERAL',
            'total_weight_kg' => 100,
            'total_volume_cbm' => 1,
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
        ]);

        // Create: Operator and Admin allowed, Customer denied
        $this->assertTrue(Gate::forUser($admin)->allows('create', Shipment::class));
        $this->assertTrue(Gate::forUser($operator)->allows('create', Shipment::class));
        $this->assertFalse(Gate::forUser($customer)->allows('create', Shipment::class));

        // Delete: ONLY Admin allowed, Operator & Customer denied
        $this->assertTrue(Gate::forUser($admin)->allows('delete', $shipment));
        $this->assertFalse(Gate::forUser($operator)->allows('delete', $shipment));
        $this->assertFalse(Gate::forUser($customer)->allows('delete', $shipment));

        // Transition: Admin & Operator allowed, Customer denied
        $this->assertTrue(Gate::forUser($admin)->allows('transition', $shipment));
        $this->assertTrue(Gate::forUser($operator)->allows('transition', $shipment));
        $this->assertFalse(Gate::forUser($customer)->allows('transition', $shipment));

        // Clean up
        $shipment->forceDelete();
        $customer->delete();
        $operator->delete();
        $admin->delete();
    }

    public function test_shipment_exception_policy_customer_isolation(): void
    {
        $customer1 = User::factory()->create(['role' => 'CUSTOMER']);
        $customer2 = User::factory()->create(['role' => 'CUSTOMER']);
        $operator = User::factory()->create(['role' => 'OPERATOR']);
        $admin = User::factory()->create(['role' => 'ADMIN']);
        $carrier = Carrier::first() ?? Carrier::factory()->create();
        $origin = Location::first() ?? Location::factory()->create();
        $dest = Location::skip(1)->first() ?? Location::factory()->create();

        $shipment = Shipment::create([
            'shipment_number' => 'CT-TEST-0003',
            'customer_id' => $customer1->id,
            'created_by' => $operator->id,
            'carrier_id' => $carrier->id,
            'origin_id' => $origin->id,
            'destination_id' => $dest->id,
            'cargo_type' => 'GENERAL',
            'total_weight_kg' => 100,
            'total_volume_cbm' => 1,
            'current_status' => 'CREATED',
            'expected_departure' => now()->addDay(),
            'expected_arrival' => now()->addDays(5),
            'estimated_arrival' => now()->addDays(5),
        ]);

        $exception = ShipmentException::create([
            'shipment_id' => $shipment->id,
            'exception_type' => 'WEATHER_DELAY',
            'severity' => 'MEDIUM',
            'status' => 'OPEN',
            'impact_hours' => 6,
            'description' => 'Storm delay test',
            'detected_at' => now(),
            'reported_by' => $operator->id,
        ]);

        // Customer 1 owns the shipment -> allowed to view exception
        $this->assertTrue(Gate::forUser($customer1)->allows('view', $exception));

        // Customer 2 does NOT own the shipment -> forbidden to view exception
        $this->assertFalse(Gate::forUser($customer2)->allows('view', $exception));

        // Operator and Admin can view
        $this->assertTrue(Gate::forUser($operator)->allows('view', $exception));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $exception));

        // Only Admin/Operator can investigate and resolve
        $this->assertTrue(Gate::forUser($operator)->allows('investigate', $exception));
        $this->assertTrue(Gate::forUser($operator)->allows('resolve', $exception));
        $this->assertFalse(Gate::forUser($customer1)->allows('investigate', $exception));
        $this->assertFalse(Gate::forUser($customer1)->allows('resolve', $exception));

        // Clean up
        $exception->delete();
        $shipment->forceDelete();
        $customer1->delete();
        $customer2->delete();
        $operator->delete();
        $admin->delete();
    }
}
