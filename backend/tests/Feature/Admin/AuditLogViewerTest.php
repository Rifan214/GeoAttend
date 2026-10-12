<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AuditLogViewerTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $this->employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_audit_logs(): void
    {
        AuditLog::create([
            'actor_user_id' => $this->admin->id,
            'action' => 'admin.test_action',
            'metadata' => ['key' => 'sample_value'],
            'ip_address' => '127.0.0.1',
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.audit-logs.index'));

        $response->assertStatus(200);
        $response->assertSee('admin.test_action');
        $response->assertSee('sample_value');
    }

    public function test_audit_logs_can_be_filtered_by_action(): void
    {
        AuditLog::create([
            'actor_user_id' => $this->admin->id,
            'action' => 'auth.login_success',
            'metadata' => ['detail' => 'login_event_abc'],
        ]);

        AuditLog::create([
            'actor_user_id' => $this->admin->id,
            'action' => 'attendance.check_in_rejected',
            'metadata' => ['detail' => 'rejected_event_xyz'],
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.audit-logs.index', [
            'action' => 'auth.login_success',
        ]));

        $response->assertStatus(200);
        $response->assertSee('login_event_abc');
        $response->assertDontSee('rejected_event_xyz');
    }

    public function test_guest_and_employee_cannot_view_audit_logs(): void
    {
        // Guest redirect
        $responseGuest = $this->get(route('admin.audit-logs.index'));
        $responseGuest->assertRedirect(route('admin.login'));

        // Employee forbidden
        $responseEmp = $this->actingAs($this->employee, 'web')->get(route('admin.audit-logs.index'));
        $responseEmp->assertStatus(403);
    }

    public function test_no_destructive_endpoints_exist_for_audit_logs(): void
    {
        // Ensure no DELETE or PUT endpoints exist on audit-logs
        $responseDelete = $this->actingAs($this->admin, 'web')->delete('/admin/audit-logs/1');
        $responseDelete->assertNotFound();

        $responsePut = $this->actingAs($this->admin, 'web')->put('/admin/audit-logs/1', []);
        $responsePut->assertNotFound();
    }
}
