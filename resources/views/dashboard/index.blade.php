@extends('layouts.app')

@section('content')
<!-- Page Header -->
<div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
    <div>
        <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
            <span class="font-bold text-blue-600">CargoTrace</span>
            <span>/</span>
            <span>Maritime Visibility</span>
            <span>/</span>
            <span class="text-slate-800 font-semibold">Overview</span>
        </div>
        <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
            Global Ocean Freight & Fleet Operations
        </h1>
        <p class="text-xs text-slate-500 mt-0.5">
            Pemantauan langsung pelayaran kapal kargo, kontainer intermodal, koordinat AIS, dan status cuaca pelabuhan.
        </p>
    </div>

    <!-- Quick Action / Status Badges -->
    <div class="flex items-center gap-2.5">
        <a href="{{ route('tracking.map') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition group">
            <svg class="w-4 h-4 text-cyan-300 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
            </svg>
            <span>Buka Peta Pelayaran (AIS Map)</span>
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
    <!-- Total Shipments -->
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

    <!-- Sailing / In Transit -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-amber-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Kapal Berlayar</span>
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                🚢
            </div>
        </div>
        <div class="text-2xl font-black text-amber-600">{{ number_format($metrics['summary']['in_transit']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1 flex items-center gap-1">
            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
            <span>Dalam pelayaran aktif</span>
        </div>
    </div>

    <!-- Delivered -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-emerald-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Bongkar Pelabuhan</span>
            <div class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                ⚓
            </div>
        </div>
        <div class="text-2xl font-black text-emerald-600">{{ number_format($metrics['summary']['delivered']) }}</div>
        <div class="text-[11px] text-slate-500 mt-1">Tiba di pelabuhan tujuan</div>
    </div>

    <!-- Delayed -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-rose-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Potensi Delay</span>
            <div class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 flex items-center justify-center font-bold text-sm group-hover:scale-110 transition">
                ⏱️
            </div>
        </div>
        <div class="text-2xl font-black text-rose-600">{{ number_format($metrics['summary']['delayed']) }}</div>
        <div class="text-[11px] text-rose-500 font-semibold mt-1">
            {{ $metrics['summary']['delay_rate_percentage'] }}% dari total armada
        </div>
    </div>

    <!-- Exceptions -->
    <div class="bg-white p-5 rounded-2xl border border-slate-200/90 shadow-xs hover:border-purple-300 transition group">
        <div class="flex items-center justify-between text-slate-400 mb-2">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Peringatan / Cuaca</span>
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
                <h2 class="text-lg font-black tracking-tight text-white">Live Maritime Fleet Telemetry</h2>
            </div>
            <p class="text-xs text-slate-400 mt-0.5">Data koordinat satelit AIS kapal peti kemas langsung dari integrasi Python Collector</p>
        </div>
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-cyan-500/10 text-cyan-300 border border-cyan-500/30 text-xs font-bold font-mono">
            <span>📡</span> Python AIS Collector: Active
        </span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @forelse($metrics['active_vessels'] as $vessel)
            <div class="bg-slate-800/90 rounded-2xl p-4 border border-slate-700/80 hover:border-cyan-500/50 transition flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between gap-2 mb-2">
                        <span class="text-[11px] font-black uppercase tracking-wider px-2 py-0.5 rounded bg-blue-900/60 text-cyan-300 border border-blue-700/60">
                            {{ $vessel->carrier->name ?? 'Ocean Carrier' }}
                        </span>
                        <span class="text-xs font-bold {{ $vessel->status === 'UNDERWAY' ? 'text-emerald-400' : 'text-amber-400' }} flex items-center gap-1">
                            <span class="w-2 h-2 rounded-full {{ $vessel->status === 'UNDERWAY' ? 'bg-emerald-400 animate-pulse' : 'bg-amber-400' }}"></span>
                            {{ $vessel->status }}
                        </span>
                    </div>

                    <h3 class="text-base font-extrabold text-white tracking-tight">{{ $vessel->name }}</h3>
                    <p class="text-xs text-slate-400 font-mono mt-0.5">IMO {{ $vessel->imo_number }} • Bendera {{ $vessel->flag_country ?? 'Intl' }}</p>

                    <div class="mt-3 pt-3 border-t border-slate-700/60 grid grid-cols-2 gap-2 text-xs">
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Kecepatan Saat Ini</span>
                            <div class="font-extrabold text-cyan-300 font-mono">{{ $vessel->current_speed_knots ?? 18.5 }} knots</div>
                        </div>
                        <div>
                            <span class="text-[10px] uppercase font-bold text-slate-400">Arah Haluan</span>
                            <div class="font-extrabold text-slate-200 font-mono">{{ $vessel->current_heading_degrees ?? 285 }}° NW</div>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-slate-700/40 flex items-center justify-between text-xs">
                    <span class="text-[11px] text-slate-400">Kapasitas: {{ number_format($vessel->capacity_teu ?? 20000) }} TEU</span>
                    <a href="{{ route('tracking.map') }}" class="text-cyan-400 hover:text-cyan-300 font-bold flex items-center gap-1">
                        Lihat Peta &rarr;
                    </a>
                </div>
            </div>
        @empty
            <div class="col-span-3 text-center py-6 text-slate-400 text-xs">
                Belum ada data kapal kargo terdaftar.
            </div>
        @endforelse
    </div>
</div>

<!-- Charts Row: Status Distribution & Ocean Carriers -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Status Distribution Doughnut Chart -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Distribusi Status Kontainer</h3>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Tahapan Pelayaran</span>
            </div>
            <div class="relative h-56 flex items-center justify-center">
                <canvas id="statusChart"></canvas>
            </div>
        </div>
        <div class="mt-4 pt-4 border-t border-slate-100 grid grid-cols-2 gap-2 text-xs text-slate-600">
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                <span>In Transit ({{ $metrics['charts']['status_distribution']['IN_TRANSIT'] ?? 0 }})</span>
            </div>
            <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                <span>Delivered ({{ $metrics['charts']['status_distribution']['DELIVERED'] ?? 0 }})</span>
            </div>
        </div>
    </div>

    <!-- Carrier Distribution Bar Chart -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs lg:col-span-2 flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Beban Muatan Ocean Carrier</h3>
                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Maersk • Evergreen • MSC</span>
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

<!-- Active Ocean Shipments Table -->
<div id="shipments-table" class="bg-white rounded-2xl border border-slate-200/90 shadow-xs overflow-hidden">
    <div class="p-6 border-b border-slate-200/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Daftar Pengiriman Kontainer Aktif</h3>
            <p class="text-xs text-slate-500 mt-0.5">Jadwal rute pelabuhan asal, pelabuhan transit, pelabuhan tujuan, dan estimasi waktu kedatangan (ETA)</p>
        </div>
        <a href="{{ route('tracking.map') }}" class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs transition">
            <span>Telusuri di Peta Interaktif</span> &rarr;
        </a>
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
                @forelse($metrics['recent_shipments'] as $shipment)
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
                                {{ $shipment->carrier->name ?? 'Maersk Line' }} ({{ $shipment->carrier->code ?? 'MSK' }})
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
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[10px] font-black bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> {{ $shipment->current_status }}
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
                                    <span>({{ $shipment->delay_severity }})</span>
                                </div>
                            @else
                                <div class="text-[10px] font-bold text-emerald-600 flex items-center gap-1 mt-0.5">
                                    <span>✓ Sesuai Jadwal (On Schedule)</span>
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
                        <td colspan="6" class="px-6 py-8 text-center text-slate-400">
                            Tidak ada pengiriman kontainer yang tercatat saat ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Exceptions & Audit Logs Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Operational Audit Log Feed -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Jejak Audit Aktivitas Terakhir</h3>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Sistem Keamanan</span>
        </div>
        <div class="space-y-3">
            @forelse($metrics['recent_audit_logs'] as $log)
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200/70 flex items-center justify-between text-xs">
                    <div>
                        <div class="font-bold text-slate-800 flex items-center gap-2">
                            <span class="px-1.5 py-0.5 rounded bg-slate-200 font-mono text-[10px] uppercase font-bold text-slate-700">{{ $log->action }}</span>
                            <span>{{ $log->auditable_type ? class_basename($log->auditable_type) : 'System' }} #{{ $log->auditable_id }}</span>
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

    <!-- Security & Compliance Policy Card -->
    <div class="bg-white p-6 rounded-2xl border border-slate-200/90 shadow-xs flex flex-col justify-between">
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Kepatuhan Operasional Maritim</h3>
                <span class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider font-mono">ISO 18185 COMPLIANT</span>
            </div>
            <div class="space-y-3 text-xs text-slate-600">
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Append-Only Immutability:</b> Seluruh data peristiwa tracking kapal tidak dapat dihapus atau diubah secara bebas untuk menjaga integritas data logistik.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Aturan State Machine:</b> Transisi status (CREATED &rarr; BOOKED &rarr; PICKED_UP &rarr; IN_TRANSIT &rarr; DELIVERED) divalidasi ketat di sisi server.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="w-5 h-5 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center font-bold shrink-0">✓</span>
                    <p><b class="text-slate-800">Integrasi Otomatis AIS & Open-Meteo:</b> Telemetri koordinat dan kondisi cuaca laut diperbarui secara otomatis tanpa input manual.</p>
                </div>
            </div>
        </div>
        <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs text-slate-500">
            <span>UAS Web Programming 2 - CargoTrace</span>
            <span class="font-bold text-slate-700">Laravel 12 + MariaDB</span>
        </div>
    </div>
</div>

<!-- Chart.js Initialization Script -->
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Status Doughnut Chart
        const statusCtx = document.getElementById('statusChart');
        if (statusCtx) {
            const statusDist = @json($metrics['charts']['status_distribution']);
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: Object.keys(statusDist),
                    datasets: [{
                        data: Object.values(statusDist),
                        backgroundColor: [
                            '#94a3b8', // CREATED
                            '#38bdf8', // BOOKED
                            '#fbbf24', // PICKED_UP
                            '#2563eb', // IN_TRANSIT
                            '#10b981', // DELIVERED
                            '#f43f5e', // EXCEPTION
                            '#64748b'  // CANCELLED
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    cutout: '70%'
                }
            });
        }

        // Carrier Bar Chart
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
                        backgroundColor: '#2563eb',
                        borderRadius: 8,
                        borderSkipped: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        },
                        x: {
                            grid: { display: false }
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
