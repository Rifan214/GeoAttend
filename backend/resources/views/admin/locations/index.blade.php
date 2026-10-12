@extends('layouts.admin')

@section('title', 'Lokasi & Geofence')
@section('page_title', 'Lokasi Kantor & Geofence')

@section('content')
<div class="space-y-6">
    <!-- Header with Action Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-base font-semibold text-slate-800">Daftar Titik Lokasi Presensi</h2>
            <p class="text-xs text-slate-500 mt-0.5">Pengaturan koordinat GPS, radius geofence (meter), dan akurasi maksimal perangkat.</p>
        </div>
        <a href="{{ route('admin.locations.create') }}" class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span>Tambah Lokasi Baru</span>
        </a>
    </div>

    <!-- Locations Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Nama Lokasi</th>
                        <th class="px-6 py-3.5">Koordinat (Lat, Long)</th>
                        <th class="px-6 py-3.5">Radius Geofence</th>
                        <th class="px-6 py-3.5">Akurasi Maks</th>
                        <th class="px-6 py-3.5">Karyawan</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($locations as $loc)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-900">{{ $loc->name }}</div>
                                <div class="text-xs text-slate-400 max-w-xs truncate">{{ $loc->address ?: '-' }}</div>
                            </td>
                            <td class="px-6 py-4 font-mono text-xs whitespace-nowrap text-slate-700">
                                <div>Lat: {{ $loc->latitude }}</div>
                                <div>Lng: {{ $loc->longitude }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 font-semibold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded-md text-xs border border-indigo-200">
                                    {{ number_format($loc->radius_meters) }} meter
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                &le; {{ number_format($loc->max_accuracy_meters) }} m
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-600">
                                {{ $loc->users_count }} orang
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($loc->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right space-x-2">
                                <a href="{{ route('admin.locations.edit', $loc) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 transition">
                                    Edit
                                </a>

                                <form action="{{ route('admin.locations.toggle-status', $loc) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin mengubah status lokasi ini?')">
                                    @csrf
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold {{ $loc->is_active ? 'text-rose-600 bg-rose-50 hover:bg-rose-100' : 'text-emerald-600 bg-emerald-50 hover:bg-emerald-100' }} transition">
                                        {{ $loc->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-medium">Belum ada lokasi kerja terdaftar.</p>
                                <p class="text-xs text-slate-400 mt-1">Tambahkan lokasi kantor baru untuk menentukan radius presensi geofence.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($locations->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $locations->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
