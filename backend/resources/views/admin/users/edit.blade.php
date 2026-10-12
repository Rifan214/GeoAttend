@extends('layouts.admin')

@section('title', 'Edit Karyawan')
@section('page_title', 'Edit Data Karyawan')

@section('content')
<div class="max-w-2xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <a href="{{ route('admin.users.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Kembali ke Daftar Karyawan</span>
        </a>
    </div>

    @if ($user->id === auth()->id())
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-800 text-xs flex items-center gap-3">
            <svg class="w-5 h-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
            <span><strong>Perhatian:</strong> Anda sedang mengedit akun login Anda sendiri. Role dan status aktif tidak dapat diturunkan untuk mencegah akun administratif terkunci.</span>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <h2 class="text-lg font-bold text-slate-800 mb-1">Perbarui Profil: {{ $user->name }}</h2>
        <p class="text-xs text-slate-500 mb-6">Kosongkan kolom password jika tidak ingin mengganti password pengguna.</p>

        <form method="POST" action="{{ route('admin.users.update', $user) }}" class="space-y-5">
            @csrf
            @method('PUT')

            <!-- Nama Lengkap -->
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-rose-500">*</span></label>
                <input type="text" id="name" name="name" value="{{ old('name', $user->name) }}" required
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('name') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                @error('name')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div>
                <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Alamat Email <span class="text-rose-500">*</span></label>
                <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}" required
                    class="w-full px-3.5 py-2.5 rounded-lg border @error('email') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                @error('email')
                    <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Password Baru (Opsional) -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="password" class="block text-xs font-semibold text-slate-700 mb-1">Password Baru (Opsional)</label>
                    <input type="password" id="password" name="password" minlength="8"
                        class="w-full px-3.5 py-2.5 rounded-lg border @error('password') border-rose-300 bg-rose-50/30 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Biarkan kosong bila tidak diubah">
                    @error('password')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="password_confirmation" class="block text-xs font-semibold text-slate-700 mb-1">Konfirmasi Password Baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" minlength="8"
                        class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition"
                        placeholder="Ulangi password baru">
                </div>
            </div>

            <!-- Role & Lokasi Kerja -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="role" class="block text-xs font-semibold text-slate-700 mb-1">Role Akun <span class="text-rose-500">*</span></label>
                    @php
                        $userRoleVal = is_object($user->role) ? $user->role->value : $user->role;
                    @endphp
                    <select id="role" name="role" required {{ $user->id === auth()->id() ? 'disabled' : '' }}
                        class="w-full px-3.5 py-2.5 rounded-lg border @error('role') border-rose-300 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition {{ $user->id === auth()->id() ? 'bg-slate-100 cursor-not-allowed' : '' }}">
                        @foreach ($roles as $r)
                            <option value="{{ $r->value }}" {{ old('role', $userRoleVal) === $r->value ? 'selected' : '' }}>
                                {{ ucfirst($r->value) }}
                            </option>
                        @endforeach
                    </select>
                    @if ($user->id === auth()->id())
                        <input type="hidden" name="role" value="{{ $userRoleVal }}">
                    @endif
                    @error('role')
                        <p class="text-xs text-rose-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="work_location_id" class="block text-xs font-semibold text-slate-700 mb-1">Lokasi Kerja</label>
                    <select id="work_location_id" name="work_location_id" class="w-full px-3.5 py-2.5 rounded-lg border @error('work_location_id') border-rose-300 @else border-slate-300 @enderror text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        <option value="">-- Pilih Lokasi Kerja (Opsional) --</option>
                        @foreach ($locations as $loc)
                            <option value="{{ $loc->id }}" {{ old('work_location_id', $user->work_location_id) == $loc->id ? 'selected' : '' }}>
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
                <label class="relative flex items-start gap-3 {{ $user->id === auth()->id() ? 'opacity-60 cursor-not-allowed' : 'cursor-pointer' }}">
                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        {{ $user->id === auth()->id() ? 'disabled' : '' }}
                        class="w-4 h-4 mt-0.5 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500">
                    <div>
                        <span class="text-sm font-semibold text-slate-800">Status Akun Aktif</span>
                        <p class="text-xs text-slate-500">Jika dinonaktifkan, riwayat presensi tetap tersimpan namun akun tidak dapat masuk.</p>
                    </div>
                </label>
                @if ($user->id === auth()->id())
                    <input type="hidden" name="is_active" value="1">
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <a href="{{ route('admin.users.index') }}" class="px-4 py-2.5 rounded-lg border border-slate-300 text-slate-600 hover:bg-slate-50 text-sm font-semibold transition">
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
