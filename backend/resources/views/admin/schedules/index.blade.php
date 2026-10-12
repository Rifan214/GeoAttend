@extends('layouts.admin')

@section('title', 'Jadwal Kerja')
@section('page_title', 'Jadwal Kerja & Jam Operasional')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-800">Daftar Jadwal Kerja</h2>
            <p class="text-xs text-slate-500 mt-0.5">Pengaturan jam masuk, jam pulang, dan batas toleransi keterlambatan per lokasi kerja (Timezone: Asia/Jakarta).</p>
        </div>
    </div>

    <!-- Schedules Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Nama Jadwal</th>
                        <th class="px-6 py-3.5">Lokasi Kerja</th>
                        <th class="px-6 py-3.5">Jam Masuk (Start)</th>
                        <th class="px-6 py-3.5">Jam Pulang (End)</th>
                        <th class="px-6 py-3.5">Toleransi Telat</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($schedules as $sched)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 font-medium text-slate-900 whitespace-nowrap">
                                {{ $sched->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="font-medium text-slate-800">{{ $sched->workLocation?->name ?? 'Semua Lokasi' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-slate-700">
                                {{ substr($sched->start_time, 0, 5) }} WIB
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap font-mono text-sm text-slate-700">
                                {{ substr($sched->end_time, 0, 5) }} WIB
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    +{{ $sched->late_tolerance_minutes }} menit
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($sched->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right">
                                <a href="{{ route('admin.schedules.edit', $sched) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                                    Edit Jadwal
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-medium">Belum ada jadwal kerja terdaftar.</p>
                                <p class="text-xs text-slate-400 mt-1">Jadwal default akan terbuat otomatis saat membuat lokasi kantor baru.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($schedules->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $schedules->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
