<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_succeeds_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'email' => 'operator@cargotrace.com',
            'password' => Hash::make('Secret123!'),
            'role' => 'OPERATOR',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'operator@cargotrace.com',
            'password' => 'Secret123!',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Login successful.',
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'email' => 'operator@cargotrace.com',
                        'role' => 'OPERATOR',
                    ],
                    'token_type' => 'Bearer',
                ],
            ]);

        $this->assertNotEmpty($response->json('data.token'));
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@cargotrace.com',
            'password' => Hash::make('ValidPassword123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'admin@cargotrace.com',
            'password' => 'WrongPassword',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'The provided credentials do not match our records.',
            ]);
    }

    public function test_unauthenticated_request_is_blocked_with_consistent_json(): void
    {
        $response = $this->getJson('/api/user');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Unauthenticated.',
            ]);
    }

    public function test_authenticated_user_can_retrieve_profile(): void
    {
        $user = User::factory()->create(['role' => 'CUSTOMER']);

        $response = $this->actingAs($user, 'sanctum')->getJson('/api/user');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'role' => 'CUSTOMER',
                ],
            ]);
    }

    public function test_logout_revokes_token_successfully(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Logged out successfully.',
            ]);

        // Reset the auth guard cache in test runner
        $this->app['auth']->forgetGuards();

        // Attempting to access protected endpoint using the revoked token must fail with 401
        $subsequent = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/user');

        $subsequent->assertStatus(401);
    }
}
