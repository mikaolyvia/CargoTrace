@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <span class="font-bold text-blue-600">CargoTrace Global</span>
            <span>/</span>
            <span>Jaringan 50 Pelabuhan Dunia</span>
            <span>/</span>
            <span class="text-slate-800 font-semibold">{{ Auth::user()->role ?? 'TAMU' }}</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            @if(Auth::check() && Auth::user()->isCustomer())
                🚢 Portal Pelacakan Kargo & Kontainer Saya
            @elseif(Auth::check() && Auth::user()->isOperator())
                🚢 Pusat Kontrol Operasional & Disposisi Armada Maritim
            @else
                🌍 Global Ocean Freight & Enterprise Fleet Oversight
            @endif
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
            @if(Auth::check() && Auth::user()->isCustomer())
                Selamat datang, <b>{{ Auth::user()->name }}</b>. Memantau status pengiriman kargo pribadi Anda secara real-time dengan proteksi data terenkripsi.
            @else
                Pemantauan pelayaran kapal kargo, radar satelit AIS, jaringan 50 pelabuhan kontainer dunia, dan mitigasi keterlambatan rute laut.
            @endif
        </p>
    </div>

    <!-- Quick Action / Status Badges -->
    <div class="flex items-center gap-2.5">
        <a href="#maritime-glossary"
           class="inline-flex items-center gap-1.5 px-3.5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs transition">
            <span>📖</span>
            <span>Panduan Istilah</span>
        </a>
        <a href="{{ route('tracking.map') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition group">
            <svg class="w-4 h-4 text-cyan-300 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span>Buka Peta Pelayaran (AIS Map)</span>
        </a>
    </div>
</div>

<!-- Role Data Isolation & Privacy Information Banner -->
<div class="p-4 rounded-2xl bg-gradient-to-r from-blue-900/90 via-slate-900 to-slate-950 text-white shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3.5">
        <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-cyan-400 border border-blue-400/30 flex items-center justify-center font-bold text-lg shrink-0">
            @if(Auth::check() && Auth::user()->isCustomer()) 👤 @elseif(Auth::check() && Auth::user()->isOperator()) 🚢 @else 🛡️ @endif
        </div>
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-cyan-300">
                    Mode Akses Pengguna: {{ Auth::user()->role ?? 'TAMU' }}
                </h3>
                <span class="text-[9px] px-2 py-0.2 rounded-full bg-cyan-950 text-cyan-400 border border-cyan-800 font-bold">
                    Isolasi Akses Aktif
                </span>
            </div>
            <p class="text-xs text-slate-300 mt-0.5">
                @if(Auth::check() && Auth::user()->isCustomer())
                    Menampilkan <b>{{ $metrics['summary']['total_shipments'] }} kontainer</b> yang terdaftar atas nama perusahaan Anda. Data perusahaan kargo lain tidak dapat Anda akses demi menjaga privasi & rahasia dagang.
                @elseif(Auth::check() && Auth::user()->isOperator())
                    Menampilkan seluruh <b>{{ $metrics['summary']['total_shipments'] }} pengiriman operasional</b> lintas 50 pelabuhan global. Anda berwenang memantau AIS dan memperbarui status kargo.
                @else
                    Menampilkan ringkasan eksekutif seluruh <b>{{ $metrics['summary']['total_shipments'] }} pengiriman global</b> dengan total valuasi $ {{ number_format($metrics['summary']['total_cargo_valuation_usd'], 2) }} USD dan jejak audit kepatuhan.
                @endif
            </p>
        </div>
    </div>
    <div class="flex items-center gap-2 shrink-0">
        <a href="{{ route('notifications.index') }}" class="px-3 py-1.5 rounded-xl bg-white/10 hover:bg-white/20 text-xs font-bold transition flex items-center gap-1.5 border border-white/10">
            <span>🔔</span>
            <span>Lihat Notifikasi</span>
        </a>
    </div>
</div>

<!-- Multi-Currency Valuation Banner -->
<div class="p-4 rounded-2xl bg-white border border-slate-200/90 shadow-xs flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-lg">
            💵
        </div>
        <div>
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">Total Valuasi Kargo Maritim yang Sedang Berlayar</h3>
            <p class="text-[11px] text-slate-500">Nilai pabean barang dikondisikan dengan kurs referensi antar-negara (Frankfurter API)</p>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-3">
        <div class="px-3 py-1.5 rounded-xl bg-slate-50 border border-slate-200 text-xs">
            <span class="text-slate-400 font-semibold">USD:</span>
            <span class="font-mono font-black text-slate-900">{{ $metrics['summary']['formatted_valuation_usd'] ?? '$ 0.00' }}</span>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-emerald-50 border border-emerald-200 text-xs">
            <span class="text-emerald-600 font-semibold">Ekuivalen IDR:</span>
            <span class="font-mono font-black text-emerald-700">{{ $metrics['summary']['formatted_valuation_idr'] ?? 'Rp 0' }}</span>
        </div>
        <div class="px-3 py-1.5 rounded-xl bg-blue-50 border border-blue-200 text-xs">
            <span class="text-blue-600 font-semibold">Ekuivalen EUR:</span>
            <span class="font-mono font-black text-blue-700">{{ $metrics['summary']['formatted_valuation_eur'] ?? '€ 0.00' }}</span>
        </div>
    </div>
</div>

<!-- 5 Maritime KPI Metric Cards -->
<div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
    <!-- Card 1: Total Shipments -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-blue-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Total Kontainer</span>
            <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                📦
            </div>
        </div>
        <div class="text-2xl font-black text-slate-900">{{ number_format($metrics['summary']['total_shipments']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span class="text-blue-600 font-bold">100%</span> Jalur Laut
        </div>
    </div>

    <!-- Card 2: Sailing / In Transit -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-amber-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Dalam Pelayaran</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                🚢
            </div>
        </div>
        <div class="text-2xl font-black text-amber-600">{{ number_format($metrics['summary']['in_transit']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1.5">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            <span class="font-semibold text-slate-700">{{ $metrics['summary']['sailing_vessels'] ?? 0 }} Kapal Underway</span>
        </div>
    </div>

    <!-- Card 3: Delivered -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Delivered</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                ⚓
            </div>
        </div>
        <div class="text-2xl font-black text-emerald-600">{{ number_format($metrics['summary']['delivered']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Tiba di pelabuhan tujuan</div>
    </div>

    <!-- Card 4: Delayed -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-rose-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Delayed (Terlambat)</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                ⏱️
            </div>
        </div>
        <div class="text-2xl font-black text-rose-600">{{ number_format($metrics['summary']['delayed']) }}</div>
        <div class="text-[11px] text-rose-500 font-semibold mt-1">
            {{ $metrics['summary']['delay_rate_percentage'] }}% delay rate
        </div>
    </div>

    <!-- Card 5: Open Exceptions -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-purple-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Open Exceptions</span>
            <div class="w-8 h-8 rounded-lg bg-purple-50 text-purple-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                ⚠️
            </div>
        </div>
        <div class="text-2xl font-black text-purple-600">{{ number_format($metrics['summary']['open_exceptions']) }}</div>
        <div class="text-[11px] text-purple-600 font-semibold mt-1">
            {{ $metrics['summary']['exception_rate_percentage'] }}% butuh penanganan
        </div>
    </div>
</div>

<!-- Maritime Fleet Live AIS Cards -->
<div id="vessels-section" class="bg-gradient-to-r from-slate-900 to-slate-800 rounded-3xl p-6 text-white shadow-lg border border-slate-700/60">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
        <div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-cyan-400 animate-ping"></span>
                <h2 class="text-lg font-black tracking-tight text-white">Fleet Telemetry (Simulated AIS)</h2>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Pembaruan koordinat kapal disimulasikan melalui scheduler berkala atau integrasi REST API (<code class="text-cyan-300">/api/integrations/vessel-positions</code>)</p>
        </div>
        <div>
            @if(!empty($metrics['summary']['latest_ais_sync']))
                <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[11px] font-mono font-bold text-cyan-400 flex items-center gap-1.5">
                    <span>📡</span> Simulasi AIS Feed: Terhubung • Sinkron {{ \Carbon\Carbon::parse($metrics['summary']['latest_ais_sync'])->diffForHumans() }}
                </span>
            @else
                <span class="px-2.5 py-1 rounded-full bg-slate-800 border border-slate-700 text-[11px] font-mono font-bold text-slate-400 flex items-center gap-1.5">
                    <span>📡</span> Simulasi AIS Feed: Siap Terima Data
                </span>
            @endif
        </div>
    </div>

    <!-- Active Vessel Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($metrics['active_vessels'] as $vessel)
            <div class="p-4 rounded-2xl bg-slate-800/80 border border-slate-700/80 hover:border-cyan-500/50 transition">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-2">
                        <div class="w-8 h-8 rounded-lg bg-cyan-500/10 text-cyan-400 flex items-center justify-center font-bold text-base">
                            🚢
                        </div>
                        <div>
                            <h4 class="font-extrabold text-sm text-white tracking-tight">{{ $vessel->name }}</h4>
                            <p class="text-[10px] text-slate-400 font-mono">IMO: {{ $vessel->imo_number }} • {{ $vessel->carrier->code ?? 'CARRIER' }}</p>
                        </div>
                    </div>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold font-mono {{ $vessel->status === 'UNDERWAY' ? 'bg-cyan-500/20 text-cyan-300 border border-cyan-500/40' : 'bg-slate-700 text-slate-300' }}">
                        {{ $vessel->status }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-2 text-xs mb-3 bg-slate-900/60 p-2.5 rounded-xl border border-slate-700/50">
                    <div>
                        <span class="text-slate-400 text-[10px] block">Kecepatan:</span>
                        <span class="font-bold text-white font-mono">{{ $vessel->last_speed_knots ?? 0 }} kn</span>
                    </div>
                    <div>
                        <span class="text-slate-400 text-[10px] block">Arah Haluan:</span>
                        <span class="font-bold text-white font-mono">{{ $vessel->last_heading_degrees ?? 0 }}°</span>
                    </div>
                    <div class="col-span-2 pt-1 border-t border-slate-800">
                        <span class="text-slate-400 text-[10px] block">Koordinat AIS Terkini:</span>
                        <span class="font-mono text-cyan-300 text-[11px]">
                            {{ number_format($vessel->last_latitude ?? 0, 4) }}°, {{ number_format($vessel->last_longitude ?? 0, 4) }}°
                        </span>
                    </div>
                </div>

                <div class="flex items-center justify-between text-[11px] text-slate-400 pt-2 border-t border-slate-700/60">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        {{ $vessel->last_position_at ? $vessel->last_position_at->diffForHumans() : 'Baru saja' }}
                    </span>
                    <a href="{{ route('tracking.map') }}" class="text-cyan-400 hover:text-cyan-300 font-bold transition">
                        Buka di Peta &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-6 text-slate-400 text-xs">
                Tidak ada data armada kapal maritim yang aktif.
            </div>
        @endforelse
    </div>
</div>

<!-- Charts Row: Status Doughnut & Carrier Distribution -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Status Distribution Doughnut Chart with Legend -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-2">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Distribusi Status Pengiriman</h3>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status Maritim</span>
            </div>
            <div class="relative h-56 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 sm:grid-cols-5 gap-2 text-[11px] text-slate-600">
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-slate-400 shrink-0"></span>
                <span>Created ({{ $metrics['charts']['status_distribution']['CREATED'] ?? 0 }})</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-indigo-500 shrink-0"></span>
                <span>Booked ({{ $metrics['charts']['status_distribution']['BOOKED'] ?? 0 }})</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500 shrink-0"></span>
                <span>Picked Up ({{ $metrics['charts']['status_distribution']['PICKED_UP'] ?? 0 }})</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500 shrink-0"></span>
                <span>In Transit ({{ $metrics['charts']['status_distribution']['IN_TRANSIT'] ?? 0 }})</span>
            </div>
            <div class="flex items-center gap-1.5">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shrink-0"></span>
                <span>Delivered ({{ $metrics['charts']['status_distribution']['DELIVERED'] ?? 0 }})</span>
            </div>
        </div>
    </div>

    <!-- Carrier Distribution Bar Chart -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs lg:col-span-2 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Beban Muatan Ocean Carrier</h3>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Maersk • Evergreen • MSC • DHL • SQC</span>
            </div>
            <div class="relative h-56">
                <canvas id="carrierChart"></canvas>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>Data muatan dihitung otomatis dari shipment maritim terdaftar</span>
            <span class="font-bold text-blue-600">Terverifikasi Sistem</span>
        </div>
    </div>
</div>

<!-- Active Ocean Shipments Table with Server-Side Search & Multi-Criteria Filters -->
<div id="shipments-table" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
    <div class="p-6 border-b border-slate-200/80 flex flex-col gap-4">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Daftar Pengiriman Kontainer</h3>
                <p class="text-xs text-slate-500 mt-0.5">Filter berdasarkan nomor resi, status pelayaran, carrier, pelabuhan rute, dan keterlambatan</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('tracking.map') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
                    <span>Telusuri di Peta Interaktif</span> &rarr;
                </a>
            </div>
        </div>

        <!-- Filter Bar Form -->
        <form method="GET" action="{{ route('dashboard') }}" class="bg-slate-50 p-4 rounded-xl border border-slate-200/70 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 text-xs">
            <!-- Keyword Search -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Cari Resi / Kontainer / Kargo</label>
                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="CT-..., MSKU-..., Mesin..."
                       class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
            </div>

            <!-- Status Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status Pengiriman</label>
                <select name="status" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="">Semua Status</option>
                    <option value="CREATED" {{ ($filters['status'] ?? '') === 'CREATED' ? 'selected' : '' }}>CREATED</option>
                    <option value="BOOKED" {{ ($filters['status'] ?? '') === 'BOOKED' ? 'selected' : '' }}>BOOKED</option>
                    <option value="PICKED_UP" {{ ($filters['status'] ?? '') === 'PICKED_UP' ? 'selected' : '' }}>PICKED_UP</option>
                    <option value="IN_TRANSIT" {{ ($filters['status'] ?? '') === 'IN_TRANSIT' ? 'selected' : '' }}>IN_TRANSIT</option>
                    <option value="DELIVERED" {{ ($filters['status'] ?? '') === 'DELIVERED' ? 'selected' : '' }}>DELIVERED</option>
                </select>
            </div>

            <!-- Carrier Filter -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Ocean Carrier</label>
                <select name="carrier_id" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="">Semua Carrier</option>
                    @foreach($carriers as $c)
                        <option value="{{ $c->id }}" {{ ($filters['carrier_id'] ?? '') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Origin Port -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pelabuhan Asal</label>
                <select name="origin_id" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="">Semua Pelabuhan Asal</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ ($filters['origin_id'] ?? '') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->city }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Destination Port -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Pelabuhan Tujuan</label>
                <select name="destination_id" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="">Semua Pelabuhan Tujuan</option>
                    @foreach($locations as $loc)
                        <option value="{{ $loc->id }}" {{ ($filters['destination_id'] ?? '') == $loc->id ? 'selected' : '' }}>
                            {{ $loc->name }} ({{ $loc->city }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Delay Status -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Status Keterlambatan</label>
                <select name="delay_status" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="">Semua Jadwal</option>
                    <option value="delayed" {{ ($filters['delay_status'] ?? '') === 'delayed' ? 'selected' : '' }}>Terlambat (Delayed Only)</option>
                    <option value="on_schedule" {{ ($filters['delay_status'] ?? '') === 'on_schedule' ? 'selected' : '' }}>Tepat Waktu (On Schedule)</option>
                </select>
            </div>

            <!-- Date From -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Dari Tanggal (Date From)</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
            </div>

            <!-- Date To -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Sampai Tanggal (Date To)</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
            </div>

            <!-- Sorting -->
            <div>
                <label class="block text-[10px] font-bold uppercase text-slate-500 mb-1">Urutkan</label>
                <select name="sort_by" class="w-full px-3 py-2 rounded-lg bg-white border border-slate-200 focus:outline-none focus:border-blue-500 text-xs">
                    <option value="created_at" {{ ($filters['sort_by'] ?? '') === 'created_at' ? 'selected' : '' }}>Waktu Pendaftaran (Terbaru)</option>
                    <option value="expected_arrival" {{ ($filters['sort_by'] ?? '') === 'expected_arrival' ? 'selected' : '' }}>Estimasi ETA Kedatangan</option>
                    <option value="delay_hours" {{ ($filters['sort_by'] ?? '') === 'delay_hours' ? 'selected' : '' }}>Durasi Keterlambatan</option>
                    <option value="shipment_number" {{ ($filters['sort_by'] ?? '') === 'shipment_number' ? 'selected' : '' }}>Nomor Resi</option>
                </select>
            </div>

            <!-- Action Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-2 px-3 rounded-lg bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs transition">
                    Terapkan Filter
                </button>
                <a href="{{ route('dashboard') }}" class="py-2 px-3 rounded-lg bg-slate-200 hover:bg-slate-300 text-slate-700 font-bold text-xs transition">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-left text-xs">
            <thead class="bg-slate-50 text-slate-500 uppercase font-bold text-[10px] tracking-wider border-b border-slate-200/80">
                <tr>
                    <th class="px-6 py-3.5">No. Resi & Kontainer</th>
                    <th class="px-6 py-3.5">Kapal & Carrier</th>
                    <th class="px-6 py-3.5">Rute Pelabuhan</th>
                    <th class="px-6 py-3.5">Nilai Kargo (Mata Uang)</th>
                    <th class="px-6 py-3.5">Status Pengiriman</th>
                    <th class="px-6 py-3.5">Estimasi ETA</th>
                    <th class="px-6 py-3.5 text-right">Aksi</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($metrics['shipments'] as $shipment)
                    <tr class="hover:bg-slate-50/80 transition">
                        <!-- Tracking & Container -->
                        <td class="px-6 py-4">
                            <div class="font-black text-slate-900 font-mono text-sm">{{ $shipment->tracking_number }}</div>
                            <div class="text-[11px] text-blue-600 font-semibold font-mono mt-0.5 flex items-center gap-1">
                                <span>📦</span> {{ $shipment->container_number ?? 'ISO CONTAINER' }}
                            </div>
                        </td>

                        <!-- Vessel & Carrier -->
                        <td class="px-6 py-4">
                            <div class="font-extrabold text-slate-900 flex items-center gap-1.5">
                                <span class="text-cyan-600">🚢</span>
                                <span>{{ $shipment->vessel->name ?? 'Ocean Vessel' }}</span>
                            </div>
                            <div class="text-[11px] text-slate-500 mt-0.5">
                                {{ $shipment->carrier->name ?? 'Carrier Line' }} ({{ $shipment->carrier->code ?? 'MSK' }})
                            </div>
                        </td>

                        <!-- Route -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-2 text-slate-800 font-semibold">
                                <span class="px-1.5 py-0.5 rounded bg-slate-100 text-[10px] font-bold text-slate-600">
                                    {{ $shipment->origin->city ?? 'Origin' }}
                                </span>
                                <span class="text-blue-500 font-bold">&rarr;</span>
                                <span class="px-1.5 py-0.5 rounded bg-blue-50 text-[10px] font-bold text-blue-700">
                                    {{ $shipment->destination->city ?? 'Destination' }}
                                </span>
                            </div>
                            <div class="text-[10px] text-slate-400 mt-1">
                                Pelabuhan: {{ $shipment->origin->name ?? '-' }} ke {{ $shipment->destination->name ?? '-' }}
                            </div>
                        </td>

                        <!-- Cargo Value & Country Currency -->
                        <td class="px-6 py-4">
                            <div class="font-extrabold text-slate-900 font-mono text-sm">
                                $ {{ number_format($shipment->total_declared_value, 2) }}
                            </div>
                            <div class="text-[11px] text-emerald-700 font-semibold mt-0.5">
                                @php
                                    $currencyService = app(\App\Services\CurrencyService::class);
                                    $destCur = $currencyService->getCurrencyForCountry($shipment->destination->country ?? null);
                                    $destVal = $currencyService->convert((float)$shipment->total_declared_value, 'USD', $destCur['code']);
                                    $formattedDest = $currencyService->format($destVal, $destCur['code']);
                                @endphp
                                <span>≈ {{ $formattedDest }} ({{ $destCur['code'] }})</span>
                            </div>
                        </td>

                        <!-- Status Badge -->
                        <td class="px-6 py-4">
                            @if($shipment->current_status === 'DELIVERED')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> DELIVERED
                                </span>
                            @elseif($shipment->current_status === 'IN_TRANSIT')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-blue-50 text-blue-700 border border-blue-200 animate-pulse">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> IN TRANSIT
                                </span>
                            @elseif($shipment->current_status === 'BOOKED' || $shipment->current_status === 'PICKED_UP')
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-indigo-50 text-indigo-700 border border-indigo-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span> {{ $shipment->current_status }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-slate-100 text-slate-700 border border-slate-300">
                                    {{ $shipment->current_status }}
                                </span>
                            @endif
                        </td>

                        <!-- ETA & Delay -->
                        <td class="px-6 py-4">
                            <div class="font-bold text-slate-800">
                                {{ $shipment->estimated_arrival ? $shipment->estimated_arrival->format('d M Y, H:i') : '-' }}
                            </div>
                            @if($shipment->isDelayed())
                                <div class="text-[10px] font-bold text-rose-600 flex items-center gap-1 mt-0.5">
                                    <span>⚠️ Terlambat</span>
                                    <span>+{{ $shipment->delay_hours }}h ({{ $shipment->delay_severity }})</span>
                                </div>
                            @else
                                <div class="text-[10px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                    <span>✓ On Schedule</span>
                                </div>
                            @endif
                        </td>

                        <!-- Action -->
                        <td class="px-6 py-4 text-right">
                            <a href="{{ route('tracking.map') }}?code={{ $shipment->tracking_number }}"
                               class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg bg-blue-50 hover:bg-blue-600 text-blue-700 hover:text-white font-bold text-xs transition cursor-pointer">
                                <span>Track Map</span>
                                <span>&rarr;</span>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                            Tidak ada pengiriman kontainer yang sesuai dengan kriteria filter saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Server-Side Pagination Links -->
    <div class="px-6 py-4 border-t border-slate-100">
        {{ $metrics['shipments']->links() }}
    </div>
</div>

<!-- Role Specific Panel: Notifications for Customer OR Audit Logs for Operator/Admin -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    @if(Auth::check() && Auth::user()->isCustomer())
        <!-- Customer View: My Recent Cargo Alerts -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">🔔</span>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Pemberitahuan Terkini Kargo Anda</h3>
                </div>
                <a href="{{ route('notifications.index') }}" class="text-xs font-bold text-blue-600 hover:text-blue-800">
                    Buka Semua &rarr;
                </a>
            </div>
            <div class="space-y-3">
                @forelse($metrics['recent_notifications'] as $notif)
                    <div class="p-3.5 rounded-xl bg-slate-50 border border-slate-200/70 flex items-start gap-3 text-xs">
                        <span class="text-base shrink-0 mt-0.5">
                            {{ $notif->type === 'WARNING' ? '⚠️' : ($notif->type === 'SUCCESS' ? '✅' : 'ℹ️') }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800">{{ $notif->title }}</div>
                            <p class="text-[11px] text-slate-600 mt-0.5 leading-relaxed">{{ $notif->message }}</p>
                            <span class="text-[10px] text-slate-400 mt-1 block">{{ $notif->created_at->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-6">Belum ada notifikasi baru untuk pengiriman Anda.</p>
                @endforelse
            </div>
        </div>
    @else
        <!-- Admin & Operator View: Operational Audit Log Feed -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-purple-50 text-purple-600">🛡️</span>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Jejak Audit Aktivitas Sistem Terkini</h3>
                </div>
                <a href="{{ route('audit.index') }}" class="text-xs font-bold text-purple-600 hover:text-purple-800">
                    Buka Halaman Audit Trail &rarr;
                </a>
            </div>
            <div class="space-y-3">
                @forelse($metrics['recent_audit_logs'] as $log)
                    <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between text-xs">
                        <div>
                            <div class="font-bold text-slate-800 flex items-center gap-2">
                                <span class="px-1.5 py-0.5 rounded bg-purple-100 text-purple-800 font-mono text-[10px] uppercase font-bold">{{ $log->action }}</span>
                                <span>{{ $log->entity_type && $log->entity_type !== 'SYSTEM' ? class_basename($log->entity_type) : 'Shipment' }} #{{ $log->entity_id ?? $log->id }}</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-0.5">Oleh: {{ $log->user->name ?? 'System Worker' }} ({{ $log->user->role ?? 'DAEMON' }})</p>
                        </div>
                        <span class="text-[10px] text-slate-400 font-mono">{{ $log->created_at->diffForHumans() }}</span>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 text-center py-4">Belum ada aktivitas audit log.</p>
                @endforelse
            </div>
        </div>
    @endif

    <!-- Security & Compliance Policy Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center gap-2">
                    <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">🔒</span>
                    <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Keamanan Data & Isolasi Multi-Tenant</h3>
                </div>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider font-mono bg-emerald-50 px-2 py-0.5 rounded border border-emerald-200">
                    RBAC SECURED
                </span>
            </div>
            <div class="space-y-3 text-xs text-slate-600">
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Isolasi Hak Akses Kargo:</b> Pelanggan hanya dapat melihat data kontainer miliknya sendiri. Data operasional internal dan manifes kargo pelanggan lain terlindungi.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Peralihan Akun Terverifikasi:</b> Setiap pergantian sesi wajib melalui proses logout dan verifikasi kata sandi demi menjaga integritas privasi sesi.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Jaringan 50 Pelabuhan Global:</b> Terintegrasi dengan pelabuhan utama dunia di Asia, Timur Tengah, Eropa, Amerika, dan Afrika menggunakan standar UN/LOCODE.</p>
                </div>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>CargoTrace Global Platform &copy; 2026</span>
            <span class="font-bold text-slate-700">Multi-Role RBAC Enforced</span>
        </div>
    </div>
</div>

<!-- Maritime Glossary & Guide Section (Agar Pengguna Tidak Bingung) -->
<div id="maritime-glossary" class="bg-white p-6 sm:p-8 rounded-3xl border border-slate-200 shadow-sm space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-slate-100 pb-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-xl bg-cyan-50 text-cyan-700 border border-cyan-100 text-lg">📚</span>
                <h2 class="text-lg font-black text-slate-900 tracking-tight">Panduan & Glosarium Istilah Logistik Maritim</h2>
            </div>
            <p class="text-xs text-slate-500 mt-1">
                Penjelasan istilah teknis agar seluruh pengguna (Admin, Operator, dan Customer) memahami makna data pengiriman.
            </p>
        </div>
        <span class="text-xs font-semibold px-3 py-1 rounded-full bg-slate-100 text-slate-600 w-fit">
            Panduan Edukasi Pengguna
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Item 1: AIS -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">📡</span>
                <h4 class="text-xs font-bold text-slate-900">AIS (Automatic Identification System)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                Pemancar satelit dan radio kapal otomatis yang menyiarkan koordinat GPS, kecepatan (*knots*), dan arah haluan secara langsung ke stasiun darat dan satelit.
            </p>
        </div>

        <!-- Item 2: ETA vs Expected Arrival -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">⏱️</span>
                <h4 class="text-xs font-bold text-slate-900">ETA vs Jadwal Awal (Expected)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                <b>Expected:</b> Jadwal kontrak saat pemesanan.<br>
                <b>Estimated Time of Arrival (ETA):</b> Estimasi kedatangan terkini yang dikalkulasi otomatis sesuai kecepatan dan cuaca laut.
            </p>
        </div>

        <!-- Item 3: UN/LOCODE -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">⚓</span>
                <h4 class="text-xs font-bold text-slate-900">UN/LOCODE (Kode Pelabuhan PBB)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                Standar PBB untuk lokasi pelabuhan 5 digit. Contoh: <b>IDJKT</b> (Jakarta Tanjung Priok), <b>SGSIN</b> (Singapura), <b>NLRTM</b> (Rotterdam), <b>CNSHA</b> (Shanghai).
            </p>
        </div>

        <!-- Item 4: Delay Severity -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">⚠️</span>
                <h4 class="text-xs font-bold text-slate-900">Tingkat Keterlambatan (Delay Severity)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                • <b>Minor:</b> Deviasi &lt; 12 jam (wajar dalam pelayaran).<br>
                • <b>Medium:</b> Deviasi 12–24 jam (misal antrean sandar).<br>
                • <b>Major:</b> Deviasi &gt; 24 jam (badai atau inspeksi pabean).
            </p>
        </div>

        <!-- Item 5: HS Code -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">📋</span>
                <h4 class="text-xs font-bold text-slate-900">HS Code (Harmonized System)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                Kode klasifikasi pabean internasional 6–8 digit yang mendefinisikan jenis komoditas kargo untuk kepabeanan dan penetapan tarif bea masuk.
            </p>
        </div>

        <!-- Item 6: Port Congestion & Berthing -->
        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-1.5">
            <div class="flex items-center gap-2">
                <span class="text-base">🏗️</span>
                <h4 class="text-xs font-bold text-slate-900">Antrean Sandar (Port Congestion)</h4>
            </div>
            <p class="text-[11px] text-slate-600 leading-relaxed">
                Kondisi saat dermaga pelabuhan penuh sehingga kapal harus menunggu di area labuh jangkar (*anchorage*) sebelum dapat membongkar kontainer.
            </p>
        </div>
    </div>
</div>

<!-- Chart.js Initialization Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Status Doughnut Chart with Legend and Center Metric Text
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusDist = @json($metrics['charts']['status_distribution']);
            const totalShipments = {{ (int) ($metrics['summary']['total_shipments'] ?? 0) }};

            // Custom Chart.js Plugin to draw center number and label exactly in the doughnut hole
            const centerTextPlugin = {
                id: 'centerTextPlugin',
                afterDraw(chart) {
                    const { ctx, chartArea } = chart;
                    if (!chartArea) return;

                    const centerX = (chartArea.left + chartArea.right) / 2;
                    const centerY = (chartArea.top + chartArea.bottom) / 2;

                    ctx.save();
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';

                    // Number (Total Count)
                    ctx.font = '800 26px "Plus Jakarta Sans", system-ui, sans-serif';
                    ctx.fillStyle = '#0f172a';
                    ctx.fillText(String(totalShipments), centerX, centerY - 8);

                    // Subtitle Label
                    ctx.font = '700 9px "Plus Jakarta Sans", system-ui, sans-serif';
                    ctx.fillStyle = '#64748b';
                    ctx.fillText('TOTAL KARGO', centerX, centerY + 14);

                    ctx.restore();
                }
            };

            new Chart(statusCtx, {
                type: 'doughnut',
                plugins: [centerTextPlugin],
                data: {
                    labels: Object.keys(statusDist),
                    datasets: [{
                        data: Object.values(statusDist),
                        backgroundColor: [
                            '#94a3b8', // CREATED
                            '#6366f1', // BOOKED
                            '#3b82f6', // PICKED_UP
                            '#f59e0b', // IN_TRANSIT
                            '#10b981'  // DELIVERED
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'bottom',
                            labels: {
                                boxWidth: 10,
                                padding: 8,
                                font: {
                                    size: 10,
                                    family: 'Plus Jakarta Sans, sans-serif',
                                    weight: 'bold'
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.parsed || 0;
                                    const percentage = totalShipments > 0 ? ((value / totalShipments) * 100).toFixed(1) : 0;
                                    return ` ${label}: ${value} unit (${percentage}%)`;
                                }
                            }
                        }
                    },
                    cutout: '68%'
                }
            });
        }

        // Carrier Bar Chart (Horizontal layout to prevent label collisions)
        const carrierCtx = document.getElementById('carrierChart');
        if (carrierCtx) {
            const carrierDist = @json($metrics['charts']['carrier_distribution']);
            new Chart(carrierCtx, {
                type: 'bar',
                data: {
                    labels: Object.keys(carrierDist),
                    datasets: [{
                        label: 'Jumlah Kontainer',
                        data: Object.values(carrierDist),
                        backgroundColor: [
                            '#2563eb', // Maersk
                            '#059669', // Evergreen
                            '#d97706', // MSC
                            '#dc2626', // DHL
                            '#7c3aed'  // SQC
                        ],
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y', // Horizontal bars prevent X-axis label collision
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: { stepSize: 1, font: { size: 10 } },
                            grid: { color: '#f1f5f9' }
                        },
                        y: {
                            grid: { display: false },
                            ticks: {
                                font: { size: 11, weight: '600', family: 'Plus Jakarta Sans, sans-serif' },
                                color: '#334155'
                            }
                        }
                    },
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    });
</script>
@endsection
