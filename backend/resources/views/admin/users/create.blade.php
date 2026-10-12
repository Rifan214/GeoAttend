@extends('layouts.admin')

@section('title', 'Tambah Karyawan')
@section('page_title', 'Tambah Karyawan Baru')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Karyawan</span>
        </a>
    </div>

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Formulir Pendaftaran Karyawan</h2>
        <p class="text-xs text-slate-500 mb-6">Isi informasi akun karyawan dengan lengkap. Akun ini dapat digunakan untuk login ke aplikasi mobile.</p>

        <form method="POST" action="{{ route('admin.users.store') }}" class="space-y-5">
            @csrf

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name') }}" required
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="Contoh: Budi Pratama">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email') }}" required
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('email') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                    placeholder="budi@example.com">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password & Konfirmasi -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password <span class="text-rose-500">*</span></label>
                    <input type="password" id="password" name="password" required minlength="8"
                        class="w-full px-3.5 py-2.5 rounded-lg border @error('password') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Minimal 8 karakter">
                    @error('password')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password <span class="text-rose-500">*</span></label>
                    <input type="password" id="password_confirmation" name="password_confirmation" required minlength="8"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Ulangi password">
                </div>
            </div>

            <!-- Role & Lokasi Kerja -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-700 mb-1">Role Akun <span class="text-rose-500">*</span></label>
                    <select id="role" name="role" required class="w-full px-3.5 py-2.5 rounded-lg border @error('role') border-rose-300 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}" {{ old('role', 'employee') === $r->value ? 'selected' : '' }}>
                                {{ ucfirst($r->value) }}
                            </option>
                        @endforeach
                    </select>
                    @error('role')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="work_location_id" class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Kerja</label>
                    <select id="work_location_id" name="work_location_id" class="w-full px-3.5 py-2.5 rounded-lg border @error('work_location_id') border-rose-300 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        <option value="">-- Pilih Lokasi Kerja (Opsional) --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('work_location_id') == $loc->id ? 'selected' : '' }}>
                                {{ $loc->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('work_location_id')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Status Aktif -->
            <div class="pt-2">
                <label class="relative flex items-start gap-3 cursor-pointer">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }}
                        class="w-4 h-4 mt-0.5 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Status Akun Aktif</span>
                        <p class="text-xs text-slate-500">Jika dinonaktifkan, pengguna tidak dapat login ke mobile app atau dashboard.</p>
                    </div>
                </label>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition">
                    Batal
                </a>
                <button type="submit" class="px-5 py-2.5 rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold shadow-sm transition">
                    Simpan Karyawan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
