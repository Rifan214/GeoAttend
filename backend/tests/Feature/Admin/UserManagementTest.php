<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkLocation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected WorkLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $this->location = WorkLocation::factory()->create([
            'is_active' => true,
        ]);
    }

    public function test_admin_can_view_users_index(): void
    {
        $employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'name' => 'Budi Santoso',
            'work_location_id' => $this->location->id,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.users.index'));

        $response->assertStatus(200);
        $response->assertSee('Budi Santoso');
        $response->assertSee($this->location->name);
    }

    public function test_admin_can_search_users(): void
    {
        $targetUser = User::factory()->create([
            'name' => 'UniqueEmployeeXYZ',
            'email' => 'unique_xyz@example.com',
            'role' => Role::EMPLOYEE,
        ]);

        $otherUser = User::factory()->create([
            'name' => 'OtherWorker',
            'email' => 'other_worker@example.com',
            'role' => Role::EMPLOYEE,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.users.index', [
            'search' => 'UniqueEmployeeXYZ',
        ]));

        $response->assertStatus(200);
        $response->assertSee('UniqueEmployeeXYZ');
        $response->assertDontSee('OtherWorker');
    }

    public function test_admin_can_create_new_employee(): void
    {
        $response = $this->actingAs($this->admin, 'web')->post(route('admin.users.store'), [
            'name' => 'Karyawan Baru',
            'email' => 'karyawan_baru@example.com',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => Role::EMPLOYEE->value,
            'work_location_id' => $this->location->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'email' => 'karyawan_baru@example.com',
            'role' => Role::EMPLOYEE->value,
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->admin->id,
            'action' => 'admin.user_created',
        ]);
    }

    public function test_create_employee_validates_unique_email(): void
    {
        $existing = User::factory()->create(['email' => 'dupe@example.com']);

        $response = $this->actingAs($this->admin, 'web')
            ->from(route('admin.users.create'))
            ->post(route('admin.users.store'), [
                'name' => 'Dupe Test',
                'email' => 'dupe@example.com',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
                'role' => Role::EMPLOYEE->value,
            ]);

        $response->assertRedirect(route('admin.users.create'));
        $response->assertSessionHasErrors('email');
    }

    public function test_admin_can_update_employee(): void
    {
        $employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'name' => 'Old Name',
        ]);

        $response = $this->actingAs($this->admin, 'web')->put(route('admin.users.update', $employee), [
            'name' => 'New Name Updated',
            'email' => $employee->email,
            'role' => Role::EMPLOYEE->value,
            'work_location_id' => $this->location->id,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('admin.users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'New Name Updated',
            'work_location_id' => $this->location->id,
        ]);
    }

    public function test_admin_can_toggle_employee_active_status_preserving_attendance_records(): void
    {
        $employee = User::factory()->create([
            'role' => Role::EMPLOYEE,
            'is_active' => true,
            'work_location_id' => $this->location->id,
        ]);

        $attendance = AttendanceRecord::factory()->create([
            'user_id' => $employee->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => now()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.users.toggle-status', $employee));

        $response->assertStatus(302);
        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'is_active' => false,
        ]);

        // Attendance history must be preserved intact
        $this->assertDatabaseHas('attendance_records', [
            'id' => $attendance->id,
            'user_id' => $employee->id,
        ]);
    }

    public function test_admin_cannot_demote_their_own_role(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->put(route('admin.users.update', $this->admin), [
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'role' => Role::EMPLOYEE->value,
                'is_active' => '1',
            ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'role' => Role::ADMIN->value,
        ]);
    }

    public function test_admin_cannot_deactivate_their_own_account(): void
    {
        $response = $this->actingAs($this->admin, 'web')
            ->post(route('admin.users.toggle-status', $this->admin));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', [
            'id' => $this->admin->id,
            'is_active' => true,
        ]);
    }
}
