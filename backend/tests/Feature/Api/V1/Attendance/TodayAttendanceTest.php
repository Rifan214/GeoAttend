<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TodayAttendanceTest extends TestCase
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
            'is_active' => true,
        ]);

        $this->schedule = WorkSchedule::factory()->create([
            'work_location_id' => $this->location->id,
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->token = $this->user->createToken('MobileApp')->plainTextToken;
    }

    public function test_today_returns_correct_state_before_check_in(): void
    {
        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'has_checked_in' => false,
                    'has_checked_out' => false,
                    'attendance' => null,
                ],
                'message' => 'Success',
            ]);

        $this->assertNotNull($response->json('data.assigned_location'));
        $this->assertNotNull($response->json('data.schedule'));
        $this->assertNotNull($response->json('data.server_time'));
    }

    public function test_today_returns_correct_state_after_check_in(): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_in_at' => Carbon::today()->setTime(8, 5, 0),
            'check_out_at' => null,
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Simulated time during workday (10:00 AM)
        Carbon::setTestNow(Carbon::today()->setTime(10, 0, 0));

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'has_checked_in' => true,
                    'has_checked_out' => false,
                    'attendance' => [
                        'status' => 'present',
                    ],
                ],
            ]);

        Carbon::setTestNow();
    }

    public function test_today_returns_correct_state_after_check_out(): void
    {
        AttendanceRecord::factory()->checkedOut()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJson([
                'data' => [
                    'has_checked_in' => true,
                    'has_checked_out' => true,
                ],
            ]);
    }

    public function test_today_evaluates_incomplete_status_after_workday_ends_without_checkout(): void
    {
        AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_in_at' => Carbon::today()->setTime(8, 0, 0),
            'check_out_at' => null,
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Simulated time: 18:30 (workday ended at 17:00)
        Carbon::setTestNow(Carbon::today()->setTime(18, 30, 0));

        $response = $this->withHeader('Authorization', "Bearer {$this->token}")
            ->getJson('/api/v1/attendance/today');

        $response->assertOk()
            ->assertJsonPath('data.attendance.status', 'incomplete');

        Carbon::setTestNow();
    }

    public function test_today_requires_authentication(): void
    {
        $response = $this->getJson('/api/v1/attendance/today');

        $response->assertStatus(401);
    }
}
