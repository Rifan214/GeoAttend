<?php

namespace Tests\Feature\Api\V1\Attendance;

use App\Enums\AttendanceStatus;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class TimezoneAndScheduleTest extends TestCase
{
    use DatabaseTransactions;

    protected User $user;
    protected WorkLocation $location;
    protected WorkSchedule $schedule;
    protected AttendanceService $service;

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
            'late_tolerance_minutes' => 15,
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'work_location_id' => $this->location->id,
            'is_active' => true,
        ]);

        $this->service = app(AttendanceService::class);
    }

    public function test_incomplete_status_evaluated_for_yesterdays_uncompleted_attendance(): void
    {
        $yesterday = Carbon::yesterday()->toDateString();

        $record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => $yesterday,
            'check_in_at' => Carbon::yesterday()->setTime(8, 0, 0),
            'check_out_at' => null,
            'status' => AttendanceStatus::PRESENT,
        ]);

        $evaluated = $this->service->evaluateStatus($record, $this->schedule);

        $this->assertEquals(AttendanceStatus::INCOMPLETE, $evaluated);
    }

    public function test_incomplete_status_when_schedule_parameter_is_null_but_workday_ended(): void
    {
        $record = AttendanceRecord::factory()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'check_in_at' => Carbon::today()->setTime(8, 0, 0),
            'check_out_at' => null,
            'status' => AttendanceStatus::PRESENT,
        ]);

        // Simulated time: 19:00 (workday ended at 17:00)
        Carbon::setTestNow(Carbon::today()->setTime(19, 0, 0));

        // Pass null for schedule to test fallback to location's active schedule
        $evaluated = $this->service->evaluateStatus($record, null);

        $this->assertEquals(AttendanceStatus::INCOMPLETE, $evaluated);

        Carbon::setTestNow();
    }

    public function test_completed_attendance_preserves_present_status_even_after_workday_ends(): void
    {
        $record = AttendanceRecord::factory()->checkedOut()->create([
            'user_id' => $this->user->id,
            'work_location_id' => $this->location->id,
            'attendance_date' => Carbon::today()->toDateString(),
            'status' => AttendanceStatus::PRESENT,
        ]);

        Carbon::setTestNow(Carbon::today()->setTime(21, 0, 0));

        $evaluated = $this->service->evaluateStatus($record, $this->schedule);

        $this->assertEquals(AttendanceStatus::PRESENT, $evaluated);

        Carbon::setTestNow();
    }
}
