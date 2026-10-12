<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreWorkLocationRequest;
use App\Http\Requests\Admin\UpdateWorkLocationRequest;
use App\Models\AuditLog;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WorkLocationController extends Controller
{
    public function index(): View
    {
        $locations = WorkLocation::withCount(['users', 'workSchedules'])
            ->orderBy('id', 'desc')
            ->paginate(15);

        return view('admin.locations.index', [
            'locations' => $locations,
        ]);
    }

    public function create(): View
    {
        return view('admin.locations.create');
    }

    public function store(StoreWorkLocationRequest $request): RedirectResponse
    {
        $location = WorkLocation::create([
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
            'max_accuracy_meters' => $request->max_accuracy_meters,
            'is_active' => $request->boolean('is_active', true),
        ]);

        // Create initial default schedule if configured
        WorkSchedule::create([
            'work_location_id' => $location->id,
            'name' => $request->input('schedule_name') ?: 'Jadwal Reguler ' . $location->name,
            'start_time' => $request->input('start_time') ?: '08:00',
            'end_time' => $request->input('end_time') ?: '17:00',
            'late_tolerance_minutes' => (int) $request->input('late_tolerance_minutes', 15),
            'is_active' => true,
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.location_created',
            'subject_type' => WorkLocation::class,
            'subject_id' => $location->id,
            'metadata' => [
                'name' => $location->name,
                'radius_meters' => $location->radius_meters,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', "Lokasi kerja {$location->name} berhasil dibuat beserta jadwal default.");
    }

    public function edit(WorkLocation $location): View
    {
        return view('admin.locations.edit', [
            'location' => $location,
        ]);
    }

    public function update(UpdateWorkLocationRequest $request, WorkLocation $location): RedirectResponse
    {
        $location->update([
            'name' => $request->name,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'radius_meters' => $request->radius_meters,
            'max_accuracy_meters' => $request->max_accuracy_meters,
            'is_active' => $request->boolean('is_active', true),
        ]);

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.location_updated',
            'subject_type' => WorkLocation::class,
            'subject_id' => $location->id,
            'metadata' => [
                'name' => $location->name,
                'radius_meters' => $location->radius_meters,
            ],
            'ip_address' => $request->ip(),
        ]);

        return redirect()->route('admin.locations.index')
            ->with('success', "Data lokasi kerja {$location->name} berhasil diperbarui.");
    }

    public function toggleStatus(Request $request, WorkLocation $location): RedirectResponse
    {
        $location->is_active = ! $location->is_active;
        $location->save();

        $statusText = $location->is_active ? 'diaktifkan' : 'dinonaktifkan';

        AuditLog::create([
            'actor_user_id' => Auth::id(),
            'action' => 'admin.location_status_toggled',
            'subject_type' => WorkLocation::class,
            'subject_id' => $location->id,
            'metadata' => [
                'is_active' => $location->is_active,
            ],
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', "Lokasi kerja {$location->name} berhasil {$statusText}.");
    }
}
