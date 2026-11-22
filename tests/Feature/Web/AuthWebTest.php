<?php

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@cargotrace.com',
            'role' => 'ADMIN',
            'password' => bcrypt('password'),
        ]);

        $this->operator = User::factory()->create([
            'email' => 'operator@cargotrace.com',
            'role' => 'OPERATOR',
            'password' => bcrypt('password'),
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer@cargotrace.com',
            'role' => 'CUSTOMER',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_guest_can_view_login_page(): void
    {
        $response = $this->get(route('login'));

        $response->assertStatus(200);
        $response->assertSee('CargoTrace');
        $response->assertSee('admin@cargotrace.com');
        $response->assertSee('operator@cargotrace.com');
        $response->assertSee('customer@cargotrace.com');
        $response->assertSee('Masuk ke CargoTrace');
    }

    public function test_authenticated_user_visiting_login_page_is_redirected_to_dashboard(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('login'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('info');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $response = $this->post(route('login.post'), [
            'email' => 'operator@cargotrace.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($this->operator);
        $response->assertSessionHas('success');
    }

    public function test_user_cannot_login_with_invalid_password(): void
    {
        $response = $this->from(route('login'))->post(route('login.post'), [
            'email' => 'operator@cargotrace.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_logout_and_session_is_cleared(): void
    {
        $this->actingAs($this->admin);

        $response = $this->post(route('logout'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
        $response->assertSessionHas('success');
    }

    public function test_switch_role_logs_out_current_user_and_redirects_to_login_for_password_verification(): void
    {
        // Login as customer first
        $this->actingAs($this->customer);
        $this->assertAuthenticatedAs($this->customer);

        // Attempt switch to Admin
        $response = $this->post(route('auth.switch_role', 'admin'));

        // Current session must be logged out and NOT automatically logged in as Admin!
        $this->assertGuest();
        $response->assertRedirect(route('login', ['email' => 'admin@cargotrace.com']));
        $response->assertSessionHas('info');
    }

    public function test_switch_role_with_invalid_role_returns_error(): void
    {
        $this->actingAs($this->operator);

        $response = $this->from(route('dashboard'))->post(route('auth.switch_role', 'hacker'));

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('error');
    }
}
