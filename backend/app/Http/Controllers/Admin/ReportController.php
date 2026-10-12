<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AttendanceRecord;
use App\Models\User;
use App\Models\WorkLocation;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    public function index(Request $request): View
    {
        $query = $this->buildReportQuery($request);

        $records = $query->paginate(15)->withQueryString();

        $records->getCollection()->transform(function ($record) {
            $record->evaluated_status = $this->attendanceService->evaluateStatus(
                $record,
                $record->workLocation?->activeSchedule
            );

            return $record;
        });

        $users = User::orderBy('name')->get();
        $locations = WorkLocation::orderBy('name')->get();

        return view('admin.reports.index', [
            'records' => $records,
            'users' => $users,
            'locations' => $locations,
            'filters' => $request->only(['from', 'to', 'user_id', 'location_id', 'status']),
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'user_id' => ['nullable', 'exists:users,id'],
            'location_id' => ['nullable', 'exists:work_locations,id'],
            'status' => ['nullable', 'string'],
        ]);

        $query = $this->buildReportQuery($request);

        $filename = 'laporan-presensi-' . Carbon::now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));

            // CSV Header
            fputcsv($handle, [
                'ID',
                'Tanggal',
                'Nama Karyawan',
                'Email',
                'Lokasi Kerja',
                'Jam Check-in',
                'Akurasi Check-in (m)',
                'Jarak Check-in (m)',
                'Jam Check-out',
                'Akurasi Check-out (m)',
                'Jarak Check-out (m)',
                'Status',
            ]);

            $query->chunk(100, function ($records) use ($handle) {
                foreach ($records as $record) {
                    $status = $this->attendanceService->evaluateStatus(
                        $record,
                        $record->workLocation?->activeSchedule
                    );

                    fputcsv($handle, [
                        $this->sanitizeCsvValue($record->id),
                        $this->sanitizeCsvValue($record->attendance_date?->toDateString()),
                        $this->sanitizeCsvValue($record->user?->name ?? '-'),
                        $this->sanitizeCsvValue($record->user?->email ?? '-'),
                        $this->sanitizeCsvValue($record->workLocation?->name ?? '-'),
                        $this->sanitizeCsvValue($record->check_in_at?->format('H:i:s') ?? '-'),
                        $this->sanitizeCsvValue($record->check_in_accuracy_meters ?? '-'),
                        $this->sanitizeCsvValue($record->check_in_distance_meters ?? '-'),
                        $this->sanitizeCsvValue($record->check_out_at?->format('H:i:s') ?? '-'),
                        $this->sanitizeCsvValue($record->check_out_accuracy_meters ?? '-'),
                        $this->sanitizeCsvValue($record->check_out_distance_meters ?? '-'),
                        $this->sanitizeCsvValue(strtoupper($status->value)),
                    ]);
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function buildReportQuery(Request $request): Builder
    {
        $query = AttendanceRecord::with(['user', 'workLocation.activeSchedule'])
            ->orderBy('attendance_date', 'desc')
            ->orderBy('id', 'desc');

        if ($from = $request->input('from')) {
            $query->whereDate('attendance_date', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->whereDate('attendance_date', '<=', $to);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($locationId = $request->input('location_id')) {
            $query->where('work_location_id', $locationId);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        return $query;
    }

    /**
     * Sanitize values to prevent CSV formula injection attacks in spreadsheet viewers.
     */
    protected function sanitizeCsvValue(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        $string = (string) $value;

        // If string begins with unsafe characters (=, +, -, @), prefix with a single quote
        if (preg_match('/^[=\+\-@\t\r]/', $string)) {
            return "'" . $string;
        }

        return $string;
    }
}
