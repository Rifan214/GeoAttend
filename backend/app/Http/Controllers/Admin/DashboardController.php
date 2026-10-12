<?php

namespace App\Http\Controllers\Admin;

use App\Enums\AttendanceStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(): View
    {
        $today = Carbon::today()->toDateString();

        $activeEmployeesCount = User::where('role', Role::EMPLOYEE)
            ->where('is_active', true)
            ->count();

        $activeLocationsCount = WorkLocation::where('is_active', true)->count();

        $todayRecords = AttendanceRecord::with('workLocation.activeSchedule', 'user')
            ->where('attendance_date', $today)
            ->get();

        $todayTotal = $todayRecords->count();
        $presentCount = 0;
        $lateCount = 0;
        $incompleteCount = 0;

        foreach ($todayRecords as $record) {
            $status = $this->attendanceService->evaluateStatus(
                $record,
                $record->workLocation?->activeSchedule
            );

            if ($status === AttendanceStatus::PRESENT) {
                $presentCount++;
            } elseif ($status === AttendanceStatus::LATE) {
                $lateCount++;
            } elseif ($status === AttendanceStatus::INCOMPLETE) {
                $incompleteCount++;
            }
        }

        $recentRecords = AttendanceRecord::with('user', 'workLocation.activeSchedule')
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($record) {
                $record->evaluated_status = $this->attendanceService->evaluateStatus(
                    $record,
                    $record->workLocation?->activeSchedule
                );

                return $record;
            });

        return view('admin.dashboard', [
            'activeEmployeesCount' => $activeEmployeesCount,
            'activeLocationsCount' => $activeLocationsCount,
            'todayTotal' => $todayTotal,
            'presentCount' => $presentCount,
            'lateCount' => $lateCount,
            'incompleteCount' => $incompleteCount,
            'recentRecords' => $recentRecords,
            'serverTime' => Carbon::now(),
        ]);
    }
}
