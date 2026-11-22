@extends('layouts.app')

@section('title', 'Jejak Audit Sistem (Audit Trail) - CargoTrace')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-purple-50 text-purple-600 border border-purple-100 text-lg">🛡️</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Jejak Audit & Kepatuhan Sistem (Regulatory Audit Trail)</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Catatan riwayat aktivitas pengguna, perubahan status kargo, disposisi armada, serta akses keamanan sistem untuk kepatuhan regulasi logistik maritim.
            </p>
        </div>

        <div class="flex items-center gap-2">
            <span class="px-3 py-1.5 rounded-xl bg-purple-50 text-purple-800 border border-purple-200 text-xs font-bold flex items-center gap-1.5">
                <span class="w-2 h-2 rounded-full bg-purple-500 animate-pulse"></span>
                <span>Tamper-Evident Logging</span>
            </span>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200 shadow-xs">
        <form action="{{ route('audit.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <!-- Search Keyword -->
            <div class="sm:col-span-5">
                <label for="search" class="block text-xs font-bold text-slate-600 mb-1">Cari Aktivitas / IP / Entitas</label>
                <input type="text" id="search" name="search" value="{{ $filters['search'] ?? '' }}"
                       placeholder="Cari aksi (LOGIN, SHIPMENT, EXCEPTION), IP, atau nama pengguna..."
                       class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
            </div>

            <!-- Action Filter -->
            <div class="sm:col-span-3">
                <label for="action" class="block text-xs font-bold text-slate-600 mb-1">Jenis Aksi</label>
                <select id="action" name="action" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
                    <option value="">Semua Aksi</option>
                    @foreach($actions as $act)
                        <option value="{{ $act }}" {{ ($filters['action'] ?? '') === $act ? 'selected' : '' }}>
                            {{ $act }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- User Filter -->
            <div class="sm:col-span-2">
                <label for="user_id" class="block text-xs font-bold text-slate-600 mb-1">Pengguna</label>
                <select id="user_id" name="user_id" class="w-full px-3.5 py-2 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-800 focus:ring-2 focus:ring-purple-500 focus:bg-white outline-none transition">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ ($filters['user_id'] ?? '') == $u->id ? 'selected' : '' }}>
                            {{ $u->name }} ({{ $u->role }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Submit & Reset Buttons -->
            <div class="sm:col-span-2 flex items-center gap-2">
                <button type="submit" class="w-full py-2 px-3 rounded-xl bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold transition shadow-xs">
                    Filter
                </button>
                <a href="{{ route('audit.index') }}" class="py-2 px-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition" title="Reset filter">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 border-b border-slate-200 text-[10px] uppercase font-bold tracking-wider text-slate-500">
                    <tr>
                        <th class="py-3.5 px-4">Waktu (Timestamp)</th>
                        <th class="py-3.5 px-4">Pengguna & Peran</th>
                        <th class="py-3.5 px-4">Aksi / Event</th>
                        <th class="py-3.5 px-4">Entitas Terkait</th>
                        <th class="py-3.5 px-4">Alamat IP</th>
                        <th class="py-3.5 px-4">Rincian Perubahan (Data)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($logs as $log)
                        @php
                            $actionBadge = match($log->action) {
                                'EXCEPTION_REPORTED' => 'bg-amber-100 text-amber-800 border-amber-300',
                                'EXCEPTION_RESOLVED' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                                'STATUS_TRANSITION' => 'bg-blue-100 text-blue-800 border-blue-300',
                                'SHIPMENT_CREATED' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                                'USER_AUTHENTICATED', 'USER_LOGIN' => 'bg-cyan-100 text-cyan-800 border-cyan-300',
                                'AIS_TELEMETRY_SYNC' => 'bg-sky-100 text-sky-800 border-sky-300',
                                default => 'bg-slate-100 text-slate-800 border-slate-300',
                            };

                            $roleBadge = match($log->user?->role) {
                                'ADMIN' => 'bg-purple-100 text-purple-700 border-purple-200',
                                'OPERATOR' => 'bg-cyan-100 text-cyan-700 border-cyan-200',
                                'CUSTOMER' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                default => 'bg-slate-100 text-slate-600 border-slate-200',
                            };
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-600 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $log->created_at->format('d M Y, H:i:s') }}</div>
                                <span class="text-[10px] text-slate-400">{{ $log->created_at->diffForHumans() }}</span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $log->user?->name ?? 'System Automated' }}</div>
                                <span class="text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded border {{ $roleBadge }}">
                                    {{ $log->user?->role ?? 'DAEMON' }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 whitespace-nowrap">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-mono font-bold uppercase border {{ $actionBadge }}">
                                    {{ $log->action }}
                                </span>
                            </td>

                            <td class="py-3.5 px-4 text-slate-700 font-mono text-[11px]">
                                {{ class_basename($log->entity_type) }} #{{ $log->entity_id ?? '-' }}
                            </td>

                            <td class="py-3.5 px-4 font-mono text-[11px] text-slate-500 whitespace-nowrap">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>

                            <td class="py-3.5 px-4 text-[11px]">
                                @if(!empty($log->new_values))
                                    <div class="max-w-md bg-slate-50 p-2 rounded-lg border border-slate-200 font-mono text-[10px] text-slate-700 max-h-24 overflow-y-auto space-y-0.5">
                                        @foreach($log->new_values as $k => $v)
                                            <div><b class="text-slate-900">{{ $k }}:</b> {{ is_array($v) ? json_encode($v) : $v }}</div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-slate-400 italic">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-12 text-slate-400">
                                Belum ada riwayat aktivitas yang sesuai dengan filter pencarian.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-200">
            {{ $logs->links() }}
        </div>
    </div>
</div>
@endsection
