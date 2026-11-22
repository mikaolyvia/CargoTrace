@extends('layouts.app')

@section('title', 'Pusat Notifikasi Sistem - CargoTrace')

@section('content')
<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-blue-50 text-blue-600 border border-blue-100 text-lg">🔔</span>
                <h1 class="text-xl font-black text-slate-800 tracking-tight">Pusat Notifikasi & Informasi Maritim</h1>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Pemberitahuan real-time terkait pergerakan kapal, status kontainer, peringatan cuaca, dan kepabeanan.
            </p>
        </div>

        <div class="flex items-center gap-2">
            @if($unreadCount > 0)
                <form action="{{ route('notifications.read_all') }}" method="POST">
                    @csrf
                    <button type="submit" class="px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-2 shadow-xs">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span>Tandai Semua Sudah Dibaca ({{ $unreadCount }})</span>
                    </button>
                </form>
            @endif
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-3 text-xs font-bold">
        <a href="{{ route('notifications.index', ['filter' => 'all']) }}" 
           class="px-4 py-2 rounded-xl transition {{ $currentFilter === 'all' ? 'bg-slate-900 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100' }}">
            Semua Notifikasi ({{ $notifications->total() }})
        </a>
        <a href="{{ route('notifications.index', ['filter' => 'unread']) }}" 
           class="px-4 py-2 rounded-xl transition flex items-center gap-1.5 {{ $currentFilter === 'unread' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-500 hover:bg-slate-100' }}">
            <span>Belum Dibaca</span>
            @if($unreadCount > 0)
                <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $currentFilter === 'unread' ? 'bg-white text-blue-600' : 'bg-blue-600 text-white' }} font-black">
                    {{ $unreadCount }}
                </span>
            @endif
        </a>
    </div>

    <!-- Notifications List -->
    <div class="space-y-3">
        @forelse($notifications as $notif)
            @php
                $isUnread = $notif->read_at === null;
                $badgeInfo = match($notif->type) {
                    'WARNING' => ['bg' => 'bg-amber-50', 'border' => 'border-amber-200', 'text' => 'text-amber-800', 'icon' => '⚠️', 'label' => 'Peringatan Operasional'],
                    'ERROR' => ['bg' => 'bg-rose-50', 'border' => 'border-rose-200', 'text' => 'text-rose-800', 'icon' => '🚨', 'label' => 'Insiden / Masalah'],
                    'SUCCESS' => ['bg' => 'bg-emerald-50', 'border' => 'border-emerald-200', 'text' => 'text-emerald-800', 'icon' => '✅', 'label' => 'Selesai / Terverifikasi'],
                    default => ['bg' => 'bg-blue-50', 'border' => 'border-blue-200', 'text' => 'text-blue-800', 'icon' => 'ℹ️', 'label' => 'Informasi Pelayaran'],
                };
            @endphp
            <div class="p-4 sm:p-5 rounded-2xl border transition {{ $isUnread ? 'bg-white border-blue-300 shadow-sm ring-1 ring-blue-500/10' : 'bg-slate-50/70 border-slate-200 opacity-80' }}">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-start gap-3.5">
                        <span class="text-xl shrink-0 mt-0.5">{{ $badgeInfo['icon'] }}</span>
                        <div class="space-y-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="px-2 py-0.5 rounded-md text-[10px] font-extrabold uppercase border {{ $badgeInfo['bg'] }} {{ $badgeInfo['border'] }} {{ $badgeInfo['text'] }}">
                                    {{ $badgeInfo['label'] }}
                                </span>
                                @if($isUnread)
                                    <span class="px-1.5 py-0.2 rounded-full bg-blue-600 text-white text-[9px] font-black uppercase tracking-wider">
                                        Baru
                                    </span>
                                @endif
                                <span class="text-[11px] text-slate-400 font-medium">
                                    {{ $notif->created_at->diffForHumans() }} ({{ $notif->created_at->format('d M Y, H:i') }})
                                </span>
                            </div>

                            <h3 class="text-sm font-bold text-slate-800">{{ $notif->title }}</h3>
                            <p class="text-xs text-slate-600 leading-relaxed">{{ $notif->message }}</p>

                            @if(!empty($notif->data))
                                <div class="mt-2.5 p-2.5 rounded-xl bg-slate-100/80 border border-slate-200/80 text-[11px] text-slate-600 flex flex-wrap gap-x-4 gap-y-1 font-mono">
                                    @foreach($notif->data as $key => $val)
                                        <span><b>{{ strtoupper(str_replace('_', ' ', $key)) }}:</b> {{ is_array($val) ? json_encode($val) : $val }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    @if($isUnread)
                        <form action="{{ route('notifications.read', $notif->id) }}" method="POST" class="shrink-0">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-bold transition" title="Tandai telah dibaca">
                                Baca
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="text-center py-16 bg-white rounded-2xl border border-slate-200 p-8 space-y-2">
                <span class="text-4xl">📭</span>
                <h3 class="text-sm font-bold text-slate-700">Tidak Ada Notifikasi</h3>
                <p class="text-xs text-slate-400">Semua pemberitahuan sistem dan pergerakan kargo telah Anda baca.</p>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="pt-4">
        {{ $notifications->links() }}
    </div>
</div>
@endsection
