@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page_title', 'Audit Log & Jejak Forensik')

@section('content')
<div class="space-y-6">
    <!-- Read-only Security Notice Banner -->
    <div class="p-4 rounded-xl bg-slate-900 border border-slate-800 text-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-sm">
        <div class="flex items-center gap-3">
            <span class="p-2 rounded-lg bg-indigo-500/20 text-indigo-400 border border-indigo-500/30">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
            </span>
            <div>
                <h3 class="text-sm font-semibold text-white">Catatan Audit Read-Only</h3>
                <p class="text-xs text-slate-400">Jejak aktivitas presensi, autentikasi, dan perubahan administratif dicatat secara permanen dan tidak dapat diubah atau dihapus.</p>
            </div>
        </div>
        <span class="inline-flex items-center px-2.5 py-1 rounded-md text-[11px] font-semibold bg-slate-800 text-slate-300 border border-slate-700 self-start sm:self-auto">
            Audit Trail Immutability
        </span>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-sm">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label for="action" class="block text-xs font-semibold text-slate-600 mb-1">Jenis Aksi</label>
                <select id="action" name="action" class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                    <option value="">Semua Aksi</option>
                    @foreach ($actions as $act)
                        <option value="{{ $act }}" {{ request('action') === $act ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="search" class="block text-xs font-semibold text-slate-600 mb-1">Cari Aktor (Nama / Email)</label>
                <input type="text" id="search" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email aktor..."
                    class="w-full px-3 py-2 rounded-lg border border-slate-300 text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-semibold transition">
                    Filter
                </button>
                @if (request()->hasAny(['action', 'search']))
                    <a href="{{ route('admin.audit-logs.index') }}" class="px-3 py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-lg text-sm font-medium transition">
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-semibold text-slate-500 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Waktu Kejadian</th>
                        <th class="px-6 py-3.5">Aktor</th>
                        <th class="px-6 py-3.5">Jenis Aksi</th>
                        <th class="px-6 py-3.5">IP Address</th>
                        <th class="px-6 py-3.5">Detail & Metadata</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-slate-700">
                                <div class="font-semibold">{{ $log->created_at->format('d M Y') }}</div>
                                <div class="text-slate-400">{{ $log->created_at->format('H:i:s T') }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($log->actor)
                                    <div class="font-medium text-slate-900 text-sm">{{ $log->actor->name }}</div>
                                    <div class="text-xs text-slate-400">{{ $log->actor->email }}</div>
                                @elseif ($log->actor_user_id)
                                    <div class="font-mono text-xs text-slate-600">User #{{ $log->actor_user_id }}</div>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                        System / Anonymous
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $actionStr = $log->action;
                                    $badgeClass = 'bg-slate-100 text-slate-700 border-slate-200';
                                    if (str_contains($actionStr, 'rejected') || str_contains($actionStr, 'conflict') || str_contains($actionStr, 'failed')) {
                                        $badgeClass = 'bg-rose-50 text-rose-700 border-rose-200';
                                    } elseif (str_contains($actionStr, 'created') || str_contains($actionStr, 'login')) {
                                        $badgeClass = 'bg-emerald-50 text-emerald-700 border-emerald-200';
                                    } elseif (str_contains($actionStr, 'updated') || str_contains($actionStr, 'toggled')) {
                                        $badgeClass = 'bg-blue-50 text-blue-700 border-blue-200';
                                    }
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-mono font-semibold border {{ $badgeClass }}">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-slate-600">
                                {{ $log->ip_address ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-xs font-mono">
                                @if ($log->metadata && is_array($log->metadata) && count($log->metadata) > 0)
                                    <div class="bg-slate-50 p-2 rounded-lg border border-slate-200 max-w-md overflow-x-auto text-slate-700">
                                        @foreach ($log->metadata as $key => $val)
                                            <div>
                                                <span class="font-semibold text-slate-900">{{ $key }}:</span>
                                                <span class="text-slate-600">{{ is_array($val) ? json_encode($val) : $val }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 italic font-sans">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                <p class="text-sm font-medium">Belum ada catatan audit log.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
