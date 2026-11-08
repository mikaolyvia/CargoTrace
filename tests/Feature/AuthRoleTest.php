<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthRoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_seeder_creates_three_roles_correctly(): void
    {
        $this->seed(UserSeeder::class);

        $admin = User::where('email', 'admin@cargotrace.com')->first();
        $operator = User::where('email', 'operator@cargotrace.com')->first();
        $customer = User::where('email', 'customer@cargotrace.com')->first();

        $this->assertNotNull($admin);
        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isOperator());
        $this->assertFalse($admin->isCustomer());
        $this->assertTrue($admin->hasRole('ADMIN'));

        $this->assertNotNull($operator);
        $this->assertTrue($operator->isOperator());
        $this->assertFalse($operator->isAdmin());
        $this->assertTrue($operator->hasRole(['ADMIN', 'OPERATOR']));

        $this->assertNotNull($customer);
        $this->assertTrue($customer->isCustomer());
        $this->assertFalse($customer->isAdmin());
        $this->assertFalse($customer->isOperator());
        $this->assertTrue($customer->hasRole('CUSTOMER'));
    }

    public function test_user_can_generate_sanctum_api_token(): void
    {
        $user = User::factory()->create([
            'role' => 'CUSTOMER',
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        $this->assertNotEmpty($token);
        $this->assertCount(1, $user->tokens);
    }
}
