<?php

namespace Tests\Feature\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class AttendanceReportTest extends TestCase
{
    use DatabaseTransactions;

    protected User $admin;
    protected User $employee1;
    protected User $employee2;
    protected WorkLocation $location;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => Role::ADMIN,
            'is_active' => true,
        ]);

        $this->location = WorkLocation::factory()->create(['name' => 'HQ Jakarta']);

        $this->employee1 = User::factory()->create([
            'name' => 'Alice Worker',
            'email' => 'alice@example.com',
            'role' => Role::EMPLOYEE,
            'work_location_id' => $this->location->id,
        ]);

        $this->employee2 = User::factory()->create([
            'name' => 'Bob Worker',
            'email' => 'bob@example.com',
            'role' => Role::EMPLOYEE,
            'work_location_id' => $this->location->id,
        ]);
    }

    public function test_admin_can_view_attendance_reports(): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $this->employee1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.reports.index'));

        $response->assertStatus(200);
        $response->assertSee('Alice Worker');
        $response->assertSee('HQ Jakarta');
    }

    public function test_admin_can_filter_attendance_reports_by_user(): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $this->employee1->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $this->employee2->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.reports.index', [
            'user_id' => $this->employee1->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('alice@example.com');
        $response->assertDontSee('bob@example.com');
    }

    public function test_guest_and_employee_cannot_access_reports(): void
    {
        // Guest
        $responseGuest = $this->get(route('admin.reports.index'));
        $responseGuest->assertRedirect(route('admin.login'));

        // Employee
        $responseEmp = $this->actingAs($this->employee1, 'web')->get(route('admin.reports.index'));
        $responseEmp->assertStatus(403);
    }

    public function test_admin_can_export_csv_and_formula_injection_is_sanitized(): void
    {
        // Malicious user name attempting CSV Formula Injection: =cmd|' /C calc'!A0
        $maliciousUser = User::factory()->create([
            'name' => '=1+2',
            'email' => '@malicious@example.com',
            'role' => Role::EMPLOYEE,
            'work_location_id' => $this->location->id,
        ]);

        AttendanceRecord::factory()->create([
            'user_id' => $maliciousUser->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
        ]);

        $response = $this->actingAs($this->admin, 'web')->get(route('admin.reports.export', [
            'user_id' => $maliciousUser->id,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();

        // Safe CSV must prefix formula triggers (=, @) with a single quote
        $this->assertStringContainsString("'=1+2", $content, 'Expected formula =1+2 to be prefixed with single quote in CSV output');
        $this->assertStringContainsString("'@malicious@example.com", $content, 'Expected @ prefix to be sanitized with single quote');
    }
}
