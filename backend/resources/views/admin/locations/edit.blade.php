@extends('layouts.admin')

@section('title', 'Edit Lokasi Kerja')
@section('page_title', 'Edit Lokasi & Geofence')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.locations.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Lokasi</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Perbarui Lokasi: {{ $location->name }}</h2>
        <p class="text-xs text-slate-500 mb-6">Perubahan koordinat dan radius akan memengaruhi validasi presensi berikutnya tanpa mengubah catatan historis yang telah tersimpan.</p>

        <form method="POST" action="{{ route('admin.locations.update', $location) }}" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Section 1: Informasi Lokasi -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">Informasi Lokasi</h3>
                <div class="space-y-4">
                    <div>
                        <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lokasi / Kantor <span class="text-rose-500">*</span></label>
                        <input type="text" id="name" name="name" value="{{ old('name', $location->name) }}" required
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        @error('name')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="address" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Lengkap</label>
                        <textarea id="address" name="address" rows="2"
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('address') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">{{ old('address', $location->address) }}</textarea>
                        @error('address')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Section 2: Koordinat & Geofence -->
            <div>
                <h3 class="text-sm font-bold text-slate-800 border-b border-slate-100 pb-2 mb-4">Koordinat GPS & Geofence</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="latitude" class="block text-xs font-semibold text-slate-700 mb-1">Latitude (-90 s/d 90) <span class="text-rose-500">*</span></label>
                        <input type="number" step="any" id="latitude" name="latitude" value="{{ old('latitude', $location->latitude) }}" required min="-90" max="90"
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('latitude') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition font-mono">
                        @error('latitude')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="longitude" class="block text-xs font-semibold text-slate-700 mb-1">Longitude (-180 s/d 180) <span class="text-rose-500">*</span></label>
                        <input type="number" step="any" id="longitude" name="longitude" value="{{ old('longitude', $location->longitude) }}" required min="-180" max="180"
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('longitude') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition font-mono">
                        @error('longitude')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="radius_meters" class="block text-xs font-semibold text-slate-700 mb-1">Radius Geofence (Meter) <span class="text-rose-500">*</span></label>
                        <input type="number" id="radius_meters" name="radius_meters" value="{{ old('radius_meters', $location->radius_meters) }}" required min="1"
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('radius_meters') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        @error('radius_meters')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="max_accuracy_meters" class="block text-xs font-semibold text-slate-700 mb-1">Akurasi GPS Maksimal (Meter) <span class="text-rose-500">*</span></label>
                        <input type="number" id="max_accuracy_meters" name="max_accuracy_meters" value="{{ old('max_accuracy_meters', $location->max_accuracy_meters) }}" required min="1"
                            class="w-full px-3.5 py-2.5 rounded-lg border @error('max_accuracy_meters') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        @error('max_accuracy_meters')
                            <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="relative flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $location->is_active) ? 'checked' : '' }}
                        class="w-4 h-4 mt-0.5 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Status Lokasi Aktif</span>
                        <p class="text-xs text-slate-500">Jika dinonaktifkan, presensi di lokasi ini akan ditolak.</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.locations.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition">
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
