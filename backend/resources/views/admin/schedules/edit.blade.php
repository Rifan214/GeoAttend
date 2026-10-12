@extends('layouts.admin')

@section('title', 'Edit Jadwal Kerja')
@section('page_title', 'Edit Jadwal Kerja')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.schedules.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Jadwal</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Perbarui Jadwal: {{ $schedule->name }}</h2>
        <p class="text-xs text-slate-500 mb-6">Atur jam mulai kerja, jam pulang, dan batas waktu toleransi keterlambatan (Timezone: Asia/Jakarta).</p>

        <form method="POST" action="{{ route('admin.schedules.update', $schedule) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Lokasi Kerja (Read-only reference) -->
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Terkait</label>
                <div class="px-3.5 py-2.5 rounded-lg bg-slate-100 border border-slate-200 text-sm font-medium text-slate-700">
                    {{ $schedule->workLocation?->name ?? 'Semua Lokasi' }}
                </div>
            </div>

            <!-- Nama Jadwal -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Jadwal <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $schedule->name) }}" required
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Jam Mulai & Jam Selesai -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_time" class="block text-xs font-semibold text-slate-700 mb-1">Jam Masuk (Format HH:mm) <span class="text-rose-500">*</span></label>
                    <input type="text" id="start_time" name="start_time" value="{{ old('start_time', substr($schedule->start_time, 0, 5)) }}" required
                        class="w-full px-3.5 py-2.5 rounded-lg border @error('start_time') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition font-mono"
                        placeholder="08:00">
                    @error('start_time')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="end_time" class="block text-xs font-semibold text-slate-700 mb-1">Jam Pulang (Format HH:mm) <span class="text-rose-500">*</span></label>
                    <input type="text" id="end_time" name="end_time" value="{{ old('end_time', substr($schedule->end_time, 0, 5)) }}" required
                        class="w-full px-3.5 py-2.5 rounded-lg border @error('end_time') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition font-mono"
                        placeholder="17:00">
                    @error('end_time')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Toleransi Keterlambatan -->
            <div>
                <label for="late_tolerance_minutes" class="block text-xs font-semibold text-slate-700 mb-1">Toleransi Keterlambatan (Menit) <span class="text-rose-500">*</span></label>
                <input type="number" id="late_tolerance_minutes" name="late_tolerance_minutes" value="{{ old('late_tolerance_minutes', $schedule->late_tolerance_minutes) }}" required min="0" max="240"
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('late_tolerance_minutes') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                <p class="text-[11px] text-slate-400 mt-1">Presensi check-in setelah Jam Masuk + Toleransi akan berstatus <strong>Late</strong>.</p>
                @error('late_tolerance_minutes')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="relative flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $schedule->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 mt-0.5 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Status Jadwal Aktif</span>
                        <p class="text-xs text-slate-500">Satu jadwal aktif default per lokasi digunakan untuk evaluasi presensi karyawan.</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.schedules.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition">
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
