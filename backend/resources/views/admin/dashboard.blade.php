@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page_title', 'Ringkasan Sistem')

@section('content')
<div class="space-y-6">
    <!-- Top Stats Banner -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-sm">
        <div>
            <h2 class="text-lg font-bold text-slate-800">Selamat datang, {{ auth()->user()->name }}!</h2>
            <p class="text-sm text-slate-500 mt-1">Pemantauan presensi geofence real-time per {{ $serverTime->translatedFormat('l, d F Y') }}.</p>
        </div>
        <div class="flex items-center gap-2 self-start sm:self-auto px-3.5 py-1.5 rounded-lg bg-slate-100 text-slate-600 text-xs font-medium">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>Waktu Server: <strong class="text-slate-800">{{ $serverTime->format('H:i:s T') }}</strong></span>
        </div>
    </div>

    <!-- Metric Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
        <!-- Karyawan Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Karyawan Aktif</span>
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-800">{{ number_format($activeEmployeesCount) }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">Pegawai terdaftar</span>
            </div>
        </div>

        <!-- Lokasi Aktif -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Lokasi Kantor</span>
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-800">{{ number_format($activeLocationsCount) }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">Geofence aktif</span>
            </div>
        </div>

        <!-- Presensi Hari Ini (Total) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Total Presensi</span>
                <span class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-slate-800">{{ number_format($todayTotal) }}</span>
                <span class="text-xs text-slate-400 block mt-0.5">Hari ini tercatat</span>
            </div>
        </div>

        <!-- Tepat Waktu (Present) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-emerald-600 uppercase tracking-wider">Hadir Tepat Waktu</span>
                <span class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-emerald-700">{{ number_format($presentCount) }}</span>
                <span class="text-xs text-emerald-600/70 block mt-0.5">Present</span>
            </div>
        </div>

        <!-- Terlambat (Late) -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm hover:shadow transition">
            <div class="flex items-center justify-between">
                <span class="text-xs font-semibold text-amber-600 uppercase tracking-wider">Terlambat</span>
                <span class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <span class="text-2xl font-extrabold text-amber-700">{{ number_format($lateCount) }}</span>
                <span class="text-xs text-amber-600/70 block mt-0.5">Late</span>
            </div>
        </div>
    </div>

    <!-- Incomplete Alert Bar if any -->
    @if ($incompleteCount > 0)
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-3">
                <svg class="w-5 h-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div class="text-sm">
                    <strong>{{ $incompleteCount }} presensi hari ini belum selesai (Incomplete)</strong> — Karyawan belum melakukan check-out setelah jam pulang terlampaui.
                </div>
            </div>
            <a href="{{ route('admin.reports.index', ['status' => 'incomplete']) }}" class="text-xs font-bold text-rose-700 hover:underline flex-shrink-0">
                Lihat di Laporan &rarr;
            </a>
        </div>
    @endif

    <!-- Recent Attendance Activity Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h3 class="text-base font-bold text-slate-800">Aktivitas Presensi Terbaru</h3>
                <p class="text-xs text-slate-500 mt-0.5">10 catatan presensi terkini yang tersimpan di sistem.</p>
            </div>
            <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                <span>Buka Laporan Lengkap</span>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Tanggal</th>
                        <th class="px-6 py-3.5">Karyawan</th>
                        <th class="px-6 py-3.5">Lokasi</th>
                        <th class="px-6 py-3.5">Check-in</th>
                        <th class="px-6 py-3.5">Check-out</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($recentRecords as $record)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-medium text-slate-800 whitespace-nowrap">
                                {{ $record->attendance_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">{{ $record->user?->name ?? 'User #' . $record->user_id }}</div>
                                <div class="text-xs text-slate-400">{{ $record->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-slate-700">{{ $record->workLocation?->name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($record->check_in_at)
                                    <div class="font-medium text-slate-800">{{ $record->check_in_at->format('H:i:s') }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        dist: {{ round($record->check_in_distance_meters ?? 0) }}m (±{{ round($record->check_in_accuracy_meters ?? 0) }}m)
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($record->check_out_at)
                                    <div class="font-medium text-slate-800">{{ $record->check_out_at->format('H:i:s') }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        dist: {{ round($record->check_out_distance_meters ?? 0) }}m (±{{ round($record->check_out_accuracy_meters ?? 0) }}m)
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum checkout</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @php
                                    $statusVal = $record->evaluated_status?->value ?? $record->status?->value ?? 'incomplete';
                                @endphp
                                @if ($statusVal === 'present')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Present
                                    </span>
                                @elseif ($statusVal === 'late')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Late
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Incomplete
                                    </span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                <p class="text-sm font-medium">Belum ada riwayat aktivitas presensi.</p>
                                <p class="text-xs text-slate-400 mt-1">Presensi yang dilakukan melalui mobile app akan tampil di sini secara real-time.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
