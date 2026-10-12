<?php

namespace App\Services;

use App\Enums\AttendanceStatus;
use App\Exceptions\AttendanceConflictException;
use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\WorkLocation;
use App\Models\WorkSchedule;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function __construct(
        protected HaversineService $haversineService
    ) {}

    /**
     * Get today's attendance summary for a user, including incomplete status evaluation.
     */
    public function getTodayAttendance(User $user): array
    {
        $today = Carbon::today()->toDateString();
        $record = AttendanceRecord::with('workLocation.activeSchedule')
            ->where('user_id', $user->id)
            ->where('attendance_date', $today)
            ->first();

        $workLocation = $user->workLocation;
        $schedule = $workLocation?->activeSchedule;

        $evaluatedStatus = null;
        if ($record) {
            $evaluatedStatus = $this->evaluateStatus($record, $schedule);
        }

        return [
            'has_checked_in' => $record !== null && $record->check_in_at !== null,
            'has_checked_out' => $record !== null && $record->check_out_at !== null,
            'attendance' => $record ? $this->formatRecordPayload($record, $evaluatedStatus) : null,
            'assigned_location' => $workLocation && $workLocation->is_active ? [
                'id' => $workLocation->id,
                'name' => $workLocation->name,
                'address' => $workLocation->address,
                'latitude' => (float) $workLocation->latitude,
                'longitude' => (float) $workLocation->longitude,
                'radius_meters' => (int) $workLocation->radius_meters,
                'max_accuracy_meters' => (int) $workLocation->max_accuracy_meters,
            ] : null,
            'schedule' => $schedule && $schedule->is_active ? [
                'id' => $schedule->id,
                'name' => $schedule->name,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
                'late_tolerance_minutes' => (int) $schedule->late_tolerance_minutes,
            ] : null,
            'server_time' => Carbon::now()->toIso8601String(),
        ];
    }

    /**
     * Process employee check-in.
     *
     * @throws ValidationException
     * @throws AttendanceConflictException
     */
    public function checkIn(
        User $user,
        float $latitude,
        float $longitude,
        float $accuracy,
        ?string $ipAddress = null
    ): AttendanceRecord {
        $location = $this->resolveActiveWorkLocation($user);
        $schedule = $this->resolveActiveSchedule($location);

        $geofence = $this->haversineService->validateLocation(
            $latitude,
            $longitude,
            $accuracy,
            $location
        );

        if (! $geofence['is_valid']) {
            $this->logAudit(
                $user,
                'attendance.check_in_rejected',
                null,
                null,
                [
                    'reason' => $geofence['error_message'],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_meters' => $accuracy,
                    'distance_meters' => $geofence['distance_meters'],
                ],
                $ipAddress
            );

            throw ValidationException::withMessages([
                'location' => [$geofence['error_message']],
            ]);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();

        try {
            return DB::transaction(function () use (
                $user,
                $location,
                $schedule,
                $today,
                $currentTime,
                $latitude,
                $longitude,
                $accuracy,
                $geofence,
                $ipAddress
            ) {
                $existing = AttendanceRecord::where('user_id', $user->id)
                    ->where('attendance_date', $today)
                    ->lockForUpdate()
                    ->first();

                if ($existing) {
                    throw new AttendanceConflictException('Anda sudah melakukan check-in hari ini.');
                }

                $status = $this->calculateCheckInStatus($currentTime, $schedule);

                $record = AttendanceRecord::create([
                    'user_id' => $user->id,
                    'work_location_id' => $location->id,
                    'attendance_date' => $today,
                    'check_in_at' => $currentTime,
                    'check_in_latitude' => $latitude,
                    'check_in_longitude' => $longitude,
                    'check_in_accuracy_meters' => $accuracy,
                    'check_in_distance_meters' => $geofence['distance_meters'],
                    'status' => $status,
                ]);

                $this->logAudit(
                    $user,
                    'attendance.check_in',
                    AttendanceRecord::class,
                    $record->id,
                    [
                        'status' => $status->value,
                        'distance_meters' => $geofence['distance_meters'],
                        'accuracy_meters' => $accuracy,
                    ],
                    $ipAddress
                );

                return $record->load('workLocation');
            });
        } catch (AttendanceConflictException $e) {
            $this->logAudit(
                $user,
                'attendance.check_in_rejected',
                null,
                null,
                [
                    'reason' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_meters' => $accuracy,
                    'distance_meters' => $geofence['distance_meters'],
                ],
                $ipAddress
            );

            throw $e;
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            $this->logAudit(
                $user,
                'attendance.check_in_rejected',
                null,
                null,
                [
                    'reason' => 'Konkurensi presensi: duplikasi record terdeteksi pada tanggal yang sama.',
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_meters' => $accuracy,
                    'distance_meters' => $geofence['distance_meters'],
                ],
                $ipAddress
            );

            throw new AttendanceConflictException('Anda sudah melakukan check-in hari ini.');
        }
    }

    /**
     * Process employee check-out.
     *
     * @throws ValidationException
     * @throws AttendanceConflictException
     */
    public function checkOut(
        User $user,
        float $latitude,
        float $longitude,
        float $accuracy,
        ?string $ipAddress = null
    ): AttendanceRecord {
        $location = $this->resolveActiveWorkLocation($user);

        $geofence = $this->haversineService->validateLocation(
            $latitude,
            $longitude,
            $accuracy,
            $location
        );

        if (! $geofence['is_valid']) {
            $this->logAudit(
                $user,
                'attendance.check_out_rejected',
                null,
                null,
                [
                    'reason' => $geofence['error_message'],
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_meters' => $accuracy,
                    'distance_meters' => $geofence['distance_meters'],
                ],
                $ipAddress
            );

            throw ValidationException::withMessages([
                'location' => [$geofence['error_message']],
            ]);
        }

        $today = Carbon::today()->toDateString();
        $currentTime = Carbon::now();

        try {
            return DB::transaction(function () use (
                $user,
                $today,
                $currentTime,
                $latitude,
                $longitude,
                $accuracy,
                $geofence,
                $ipAddress
            ) {
                $record = AttendanceRecord::where('user_id', $user->id)
                    ->where('attendance_date', $today)
                    ->lockForUpdate()
                    ->first();

                if (! $record || ! $record->check_in_at) {
                    throw new AttendanceConflictException('Belum ada catatan check-in hari ini. Anda harus melakukan check-in terlebih dahulu.');
                }

                if ($record->check_out_at !== null) {
                    throw new AttendanceConflictException('Anda sudah melakukan check-out hari ini.');
                }

                $record->update([
                    'check_out_at' => $currentTime,
                    'check_out_latitude' => $latitude,
                    'check_out_longitude' => $longitude,
                    'check_out_accuracy_meters' => $accuracy,
                    'check_out_distance_meters' => $geofence['distance_meters'],
                ]);

                $this->logAudit(
                    $user,
                    'attendance.check_out',
                    AttendanceRecord::class,
                    $record->id,
                    [
                        'distance_meters' => $geofence['distance_meters'],
                        'accuracy_meters' => $accuracy,
                    ],
                    $ipAddress
                );

                return $record->load('workLocation');
            });
        } catch (AttendanceConflictException $e) {
            $this->logAudit(
                $user,
                'attendance.check_out_rejected',
                null,
                null,
                [
                    'reason' => $e->getMessage(),
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy_meters' => $accuracy,
                    'distance_meters' => $geofence['distance_meters'],
                ],
                $ipAddress
            );

            throw $e;
        }
    }

    /**
     * Retrieve attendance history for an employee with pagination.
     */
    public function getHistory(
        User $user,
        ?string $from = null,
        ?string $to = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = AttendanceRecord::with('workLocation.activeSchedule')
            ->where('user_id', $user->id);

        if ($from) {
            $query->whereDate('attendance_date', '>=', $from);
        }

        if ($to) {
            $query->whereDate('attendance_date', '<=', $to);
        }

        $paginator = $query->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($perPage);

        $paginator->getCollection()->transform(function (AttendanceRecord $record) {
            $schedule = $record->workLocation?->activeSchedule;
            $record->evaluated_status = $this->evaluateStatus($record, $schedule);

            return $record;
        });

        return $paginator;
    }

    /**
     * Evaluate attendance status (including incomplete condition).
     */
    public function evaluateStatus(AttendanceRecord $record, ?WorkSchedule $schedule = null): AttendanceStatus
    {
        if ($record->check_in_at && $record->check_out_at === null) {
            $today = Carbon::today();
            $recordDate = Carbon::parse($record->attendance_date);

            // If attendance date has already passed, definitely incomplete
            if ($recordDate->lt($today)) {
                return AttendanceStatus::INCOMPLETE;
            }

            // If it's today, check if schedule end time has passed
            $targetSchedule = $schedule ?: $record->workLocation?->activeSchedule ?: $record->workLocation?->workSchedules()->where('is_active', true)->first();
            if ($targetSchedule) {
                $nowTime = Carbon::now()->format('H:i:s');
                if ($nowTime > $targetSchedule->end_time) {
                    return AttendanceStatus::INCOMPLETE;
                }
            }
        }

        return $record->status instanceof AttendanceStatus
            ? $record->status
            : AttendanceStatus::from((string) $record->status);
    }

    /**
     * Determine present vs late status based on schedule start time and tolerance.
     */
    protected function calculateCheckInStatus(Carbon $checkInTime, WorkSchedule $schedule): AttendanceStatus
    {
        $startTime = Carbon::parse($schedule->start_time);
        $lateThreshold = $startTime->copy()->addMinutes((int) $schedule->late_tolerance_minutes);

        $checkInTimeOnly = Carbon::parse($checkInTime->format('H:i:s'));

        if ($checkInTimeOnly->gt($lateThreshold)) {
            return AttendanceStatus::LATE;
        }

        return AttendanceStatus::PRESENT;
    }

    /**
     * Resolve and validate user's active work location.
     *
     * @throws ValidationException
     */
    protected function resolveActiveWorkLocation(User $user): WorkLocation
    {
        $location = $user->workLocation;

        if (! $location || ! $location->is_active) {
            throw ValidationException::withMessages([
                'work_location' => ['Lokasi kerja belum ditetapkan atau sedang tidak aktif.'],
            ]);
        }

        return $location;
    }

    /**
     * Resolve and validate active schedule for the location.
     *
     * @throws ValidationException
     */
    protected function resolveActiveSchedule(WorkLocation $location): WorkSchedule
    {
        $schedule = $location->activeSchedule;

        if (! $schedule || ! $schedule->is_active) {
            throw ValidationException::withMessages([
                'work_schedule' => ['Jadwal kerja aktif belum dikonfigurasi untuk lokasi kerja ini.'],
            ]);
        }

        return $schedule;
    }

    /**
     * Format a record with evaluated status and location summary.
     */
    protected function formatRecordPayload(AttendanceRecord $record, ?AttendanceStatus $evaluatedStatus = null): array
    {
        return [
            'id' => $record->id,
            'attendance_date' => $record->attendance_date instanceof Carbon ? $record->attendance_date->toDateString() : (string) $record->attendance_date,
            'status' => ($evaluatedStatus ?? $record->status)->value,
            'check_in_at' => $record->check_in_at?->toIso8601String(),
            'check_in_latitude' => $record->check_in_latitude ? (float) $record->check_in_latitude : null,
            'check_in_longitude' => $record->check_in_longitude ? (float) $record->check_in_longitude : null,
            'check_in_accuracy_meters' => $record->check_in_accuracy_meters ? (float) $record->check_in_accuracy_meters : null,
            'check_in_distance_meters' => $record->check_in_distance_meters ? (float) $record->check_in_distance_meters : null,
            'check_out_at' => $record->check_out_at?->toIso8601String(),
            'check_out_latitude' => $record->check_out_latitude ? (float) $record->check_out_latitude : null,
            'check_out_longitude' => $record->check_out_longitude ? (float) $record->check_out_longitude : null,
            'check_out_accuracy_meters' => $record->check_out_accuracy_meters ? (float) $record->check_out_accuracy_meters : null,
            'check_out_distance_meters' => $record->check_out_distance_meters ? (float) $record->check_out_distance_meters : null,
            'work_location' => $record->workLocation ? [
                'id' => $record->workLocation->id,
                'name' => $record->workLocation->name,
            ] : null,
        ];
    }

    /**
     * Safely record an audit log entry.
     */
    protected function logAudit(
        User $user,
        string $action,
        ?string $subjectType,
        ?int $subjectId,
        array $metadata = [],
        ?string $ipAddress = null
    ): void {
        AuditLog::create([
            'actor_user_id' => $user->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
            'ip_address' => $ipAddress,
        ]);
    }
}
