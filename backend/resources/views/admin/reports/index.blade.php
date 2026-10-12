@extends('layouts.admin')

@section('title', 'Laporan Presensi')
@section('page_title', 'Rekapitulasi & Laporan Presensi')

@section('content')
<div class="space-y-6">
    <!-- Header with Export Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-800">Laporan Kehadiran Karyawan</h2>
            <p class="text-xs text-slate-500 mt-0.5">Filter riwayat kehadiran, waktu check-in/out, evaluasi status, dan ekspor data presensi ke format CSV aman.</p>
        </div>
        <a href="{{ route('admin.reports.export', request()->query()) }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span>Ekspor CSV (Sesuai Filter)</span>
        </a>
    </div>

    <!-- Filter Form Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.reports.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-3">
            <div>
                <label for="from" class="block text-xs font-semibold text-slate-600 mb-1">Dari Tanggal</label>
                <input type="date" id="from" name="from" value="{{ $filters['from'] ?? '' }}"
                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div>
                <label for="to" class="block text-xs font-semibold text-slate-600 mb-1">Sampai Tanggal</label>
                <input type="date" id="to" name="to" value="{{ $filters['to'] ?? '' }}"
                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div>
                <label for="user_id" class="block text-xs font-semibold text-slate-600 mb-1">Karyawan</label>
                <select id="user_id" name="user_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Karyawan</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="location_id" class="block text-xs font-semibold text-slate-600 mb-1">Lokasi Kerja</label>
                <select id="location_id" name="location_id" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Lokasi</option>
                    @foreach ($locations as $l)
                        <option value="{{ $l->id }}" {{ ($filters['location_id'] ?? '') == $l->id ? 'selected' : '' }}>
                            {{ $l->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="status" class="block text-xs font-semibold text-slate-600 mb-1">Status</label>
                <select id="status" name="status" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Status</option>
                    <option value="present" {{ ($filters['status'] ?? '') === 'present' ? 'selected' : '' }}>Present (Hadir)</option>
                    <option value="late" {{ ($filters['status'] ?? '') === 'late' ? 'selected' : '' }}>Late (Terlambat)</option>
                    <option value="incomplete" {{ ($filters['status'] ?? '') === 'incomplete' ? 'selected' : '' }}>Incomplete (Belum Selesai)</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-semibold transition">
                    Terapkan
                </button>
                @if (array_filter($filters))
                    <a href="{{ route('admin.reports.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition" title="Reset Filter">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Records Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Tanggal</th>
                        <th class="px-6 py-3.5">Karyawan</th>
                        <th class="px-6 py-3.5">Lokasi Kerja</th>
                        <th class="px-6 py-3.5">Waktu Check-in</th>
                        <th class="px-6 py-3.5">Waktu Check-out</th>
                        <th class="px-6 py-3.5 text-center">Status Kehadiran</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-medium text-slate-800 whitespace-nowrap">
                                {{ $record->attendance_date?->format('d M Y') ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $record->user?->name ?? 'User #' . $record->user_id }}</div>
                                <div class="text-xs text-slate-400">{{ $record->user?->email }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-slate-800 font-medium">{{ $record->workLocation?->name ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($record->check_in_at)
                                    <div class="font-semibold text-slate-800 font-mono">{{ $record->check_in_at->format('H:i:s') }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        jarak {{ round($record->check_in_distance_meters ?? 0) }}m &bull; akurasi &plusmn;{{ round($record->check_in_accuracy_meters ?? 0) }}m
                                    </div>
                                @else
                                    <span class="text-slate-400">-</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($record->check_out_at)
                                    <div class="font-semibold text-slate-800 font-mono">{{ $record->check_out_at->format('H:i:s') }}</div>
                                    <div class="text-[11px] text-slate-400">
                                        jarak {{ round($record->check_out_distance_meters ?? 0) }}m &bull; akurasi &plusmn;{{ round($record->check_out_accuracy_meters ?? 0) }}m
                                    </div>
                                @else
                                    <span class="text-slate-400 text-xs italic">Belum check-out</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                @php
                                    $statusVal = $record->evaluated_status?->value ?? $record->status?->value ?? 'incomplete';
                                @endphp
                                @if ($statusVal === 'present')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Present (Hadir)
                                    </span>
                                @elseif ($statusVal === 'late')
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                        Late (Terlambat)
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
                                <svg class="w-10 h-10 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                <p class="text-sm font-medium">Tidak ada data presensi yang sesuai kriteria filter.</p>
                                <p class="text-xs text-slate-400 mt-1">Sesuaikan rentang tanggal atau filter karyawan untuk menampilkan data.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $records->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
