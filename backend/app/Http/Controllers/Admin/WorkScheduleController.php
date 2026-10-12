<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateWorkScheduleRequest;
use App\Models\AuditLog;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkScheduleController extends Controller
{
    public function index(): View
    {
        $schedules = WorkSchedule::with('workLocation')
            ->orderBy('work_location_id')
            ->paginate(15);

        return view('admin.schedules.index', [
            'schedules' => $schedules,
        ]);
    }

    public function edit(WorkSchedule $schedule): View
    {
        return view('admin.schedules.edit', [
            'schedule' => $schedule->load('workLocation'),
        ]);
    }

    public function update(UpdateWorkScheduleRequest $request, WorkSchedule $schedule): RedirectResponse
    {
        $schedule->update([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'late_tolerance_minutes' => $request->late_tolerance_minutes,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.schedule_updated',
            'subject_type' => WorkSchedule::class,
            'subject_id' => $schedule->id,
            'metadata' => [
                'name' => $schedule->name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'late_tolerance_minutes' => $schedule->late_tolerance_minutes,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.schedules.index')
            ->with('success', "Jadwal kerja {$schedule->name} berhasil diperbarui.");
    }
}
