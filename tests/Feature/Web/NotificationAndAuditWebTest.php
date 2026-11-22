<?php

namespace Tests\Feature\Web;

use App\Models\AuditLog;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationAndAuditWebTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $operator;
    protected User $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin_notif_test@cargotrace.com',
            'role' => 'ADMIN',
            'password' => bcrypt('password'),
        ]);

        $this->operator = User::factory()->create([
            'email' => 'operator_notif_test@cargotrace.com',
            'role' => 'OPERATOR',
            'password' => bcrypt('password'),
        ]);

        $this->customer = User::factory()->create([
            'email' => 'customer_notif_test@cargotrace.com',
            'role' => 'CUSTOMER',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_user_can_view_notifications_page(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Test Kargo Berlayar',
            'message' => 'Kontainer Anda sedang menuju pelabuhan Rotterdam.',
            'type' => 'INFO',
        ]);

        $response = $this->actingAs($this->customer)->get(route('notifications.index'));

        $response->assertStatus(200);
        $response->assertSee('Pusat Notifikasi');
        $response->assertSee('Test Kargo Berlayar');
        $response->assertSee('Kontainer Anda sedang menuju pelabuhan Rotterdam.');
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $notif = Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Test Kargo Tiba',
            'message' => 'Kontainer telah tiba di dermaga.',
            'type' => 'SUCCESS',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)->post(route('notifications.read', $notif->id));

        $response->assertRedirect();
        $this->assertNotNull($notif->fresh()->read_at);
    }

    public function test_user_cannot_mark_other_users_notification(): void
    {
        $notifOther = Notification::create([
            'user_id' => $this->operator->id,
            'title' => 'Operator Alert',
            'message' => 'Vessel delay alert.',
            'type' => 'WARNING',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)->post(route('notifications.read', $notifOther->id));

        $response->assertStatus(403);
        $this->assertNull($notifOther->fresh()->read_at);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Notif 1',
            'message' => 'Msg 1',
            'type' => 'INFO',
            'read_at' => null,
        ]);

        Notification::create([
            'user_id' => $this->customer->id,
            'title' => 'Notif 2',
            'message' => 'Msg 2',
            'type' => 'WARNING',
            'read_at' => null,
        ]);

        $response = $this->actingAs($this->customer)->post(route('notifications.read_all'));

        $response->assertRedirect();
        $this->assertEquals(0, Notification::where('user_id', $this->customer->id)->unread()->count());
    }

    public function test_admin_and_operator_can_view_audit_logs(): void
    {
        AuditLog::create([
            'user_id' => $this->admin->id,
            'action' => 'SYSTEM_CONFIG_UPDATED',
            'entity_type' => 'SystemSetting',
            'entity_id' => 1,
            'ip_address' => '127.0.0.1',
        ]);

        // Admin Access
        $responseAdmin = $this->actingAs($this->admin)->get(route('audit.index'));
        $responseAdmin->assertStatus(200);
        $responseAdmin->assertSee('Jejak Audit');
        $responseAdmin->assertSee('SYSTEM_CONFIG_UPDATED');

        // Operator Access
        $responseOperator = $this->actingAs($this->operator)->get(route('audit.index'));
        $responseOperator->assertStatus(200);
        $responseOperator->assertSee('Jejak Audit');
        $responseOperator->assertSee('SYSTEM_CONFIG_UPDATED');
        $responseOperator->assertStatus(200);
        $responseOperator->assertSee('SYSTEM_CONFIG_UPDATED');
    }

    public function test_customer_is_forbidden_from_viewing_audit_logs(): void
    {
        $response = $this->actingAs($this->customer)->get(route('audit.index'));

        // Customer access must be rejected with 403 Forbidden to protect sensitive operational audit trail
        $response->assertStatus(403);
    }
}
