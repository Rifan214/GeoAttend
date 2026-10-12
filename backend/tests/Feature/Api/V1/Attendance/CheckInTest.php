<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected WorkLocation $location;
    protected WorkSchedule $schedule;
    protected string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->location = WorkLocation::factory()->create([
            'latitude' => -6.1753924,
            'longitude' => 106.8271528,
            'radius_meters' => 100,
            'max_accuracy_meters' => 50,
            'is_active' => true,
        ]);

        $this->schedule = WorkSchedule::factory()->create([
            'work_location_id' => $this->location->id,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->token = $this->user->createToken('MobileApp')->plainTextToken;
    }

    public function test_check_in_success_inside_geofence_and_sets_present_status(): void
    {
        // Set time before 08:15:00 (on-time)
        Carbon::setTestNow(Carbon::today()->setTime(8, 10, 0));

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 15.0,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'status' => 'present',
                    'attendance_date' => Carbon::today()->toDateString(),
                    'check_in_accuracy_meters' => 15.0,
                    'check_in_distance_meters' => 0.0,
                ],
                'message' => 'Check-in berhasil',
            ]);

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => AttendanceStatus::PRESENT->value,
        ]);

        Carbon::setTestNow();
    }

    public function test_check_in_after_late_tolerance_sets_late_status(): void
    {
        // 08:00:00 + 15 min tolerance = 08:15:00 deadline. Test at 08:20:00
        Carbon::setTestNow(Carbon::today()->setTime(8, 20, 0));

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.status', 'late');

        $this->assertDatabaseHas('attendance_records', [
            'user_id' => $this->user->id,
            'status' => AttendanceStatus::LATE->value,
        ]);

        Carbon::setTestNow();
    }

    public function test_check_in_rejected_when_outside_geofence_with_422(): void
    {
        // Bundaran HI (~2.2km away from Monas)
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.195022,
                'longitude' => 106.823055,
                'accuracy_meters' => 15.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);

        $this->assertDatabaseMissing('attendance_records', [
            'user_id' => $this->user->id,
            'attendance_date' => Carbon::today()->toDateString(),
        ]);
    }

    public function test_check_in_rejected_when_accuracy_exceeds_threshold_with_422(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 85.0, // > 50m max accuracy
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['location']);
    }

    public function test_check_in_rejected_when_duplicate_on_same_day_with_409(): void
    {
        // First check-in
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ])
            ->assertStatus(201);

        // Second check-in on the same day
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(409)
            ->assertJson([
                'message' => 'Anda sudah melakukan check-in hari ini.',
            ]);
    }

    public function test_check_in_rejected_when_user_has_no_assigned_work_location(): void
    {
        $unassignedUser = User::factory()->create([
            'work_location_id' => null,
            'is_active' => true,
        ]);
        $unassignedToken = $unassignedUser->createToken('App')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$unassignedToken}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_location']);
    }

    public function test_check_in_rejected_when_work_location_is_inactive(): void
    {
        $this->location->update(['is_active' => false]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 10.0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['work_location']);
    }

    public function test_check_in_creates_audit_log_entry(): void
    {
        $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', [
                'latitude' => -6.1753924,
                'longitude' => 106.8271528,
                'accuracy_meters' => 12.0,
            ])
            ->assertStatus(201);

        $this->assertDatabaseHas('audit_logs', [
            'actor_user_id' => $this->user->id,
            'action' => 'attendance.check_in',
        ]);
    }

    public function test_check_in_validates_required_fields(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->postJson('/api/v1/attendance/check-in', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['latitude', 'longitude', 'accuracy_meters']);
    }
}
