@extends('layouts.app')

@section('content')
<div class="space-y-6">
    <!-- E.1 Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-xs text-slate-500 mb-1">
                <span class="font-bold text-blue-600">CargoTrace</span>
                <span>/</span>
                <span>Pelayaran Maritim</span>
                <span>/</span>
                <span class="text-slate-800 font-semibold">Live AIS Fleet & Shipment Visibility Map</span>
            </div>
            <h1 class="text-2xl font-black text-slate-900 tracking-tight flex items-center gap-2">
                Pusat Pelacakan Kapal & Kontainer Maritim (Tracking Map)
            </h1>
            <p class="text-xs text-slate-500 mt-0.5">
                Monitoring pergerakan kapal kargo samudra, koordinat AIS satelit, kalkulasi ETA & keterlambatan, cuaca maritim Open-Meteo, dan penyesuaian mata uang lokal.
            </p>
        </div>

        <!-- Telemetry Sync Status & Simulation Trigger -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 px-3 py-1.5 rounded-xl bg-slate-900 text-white text-xs border border-slate-700 shadow-xs">
                <span class="w-2 h-2 rounded-full bg-cyan-400 animate-pulse"></span>
                <span class="text-slate-400">Status AIS:</span>
                <span class="font-bold font-mono text-cyan-300">Active (Live Stream)</span>
            </div>

            @if($selectedShipment && $selectedShipment->vessel)
                <form method="POST" action="{{ route('tracking.simulate_ais') }}" class="inline">
                    @csrf
                    <input type="hidden" name="shipment_id" value="{{ $selectedShipment->id }}">
                    <button type="submit"
                            title="Simulasikan pembaruan posisi satelit AIS otomatis untuk kapal ini"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs shadow-md shadow-emerald-500/20 transition cursor-pointer">
                        <span>📡</span>
                        <span>Simulasi AIS Ping</span>
                    </button>
                </form>
            @endif

            @if($selectedShipment)
                <a href="{{ route('tracking.export', ['code' => $selectedShipment->tracking_number]) }}"
                   title="Unduh laporan lengkap perjalanan kargo ini dalam format CSV"
                   class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs border border-slate-300 transition cursor-pointer">
                    <svg class="w-4 h-4 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    <span>Export CSV</span>
                </a>
            @endif
        </div>
    </div>

    <!-- Flash Notification Messages -->
    @if(session('success'))
        <div class="p-3.5 rounded-2xl bg-emerald-50 text-emerald-800 border border-emerald-200 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>✓</span>
                <span>{{ session('success') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-600 hover:text-emerald-800 font-bold">&times;</button>
        </div>
    @endif
    @if(session('error'))
        <div class="p-3.5 rounded-2xl bg-rose-50 text-rose-800 border border-rose-200 text-xs font-semibold flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span>⚠️</span>
                <span>{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-600 hover:text-rose-800 font-bold">&times;</button>
        </div>
    @endif

    <!-- E.2 Summary KPI Cards Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
        <!-- Total Monitored Shipments -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Dipantau</span>
                <span class="w-6 h-6 rounded-md bg-blue-50 text-blue-600 flex items-center justify-center text-xs font-bold">📦</span>
            </div>
            <div class="text-2xl font-black text-slate-900">{{ number_format($summary['total_monitored']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Kontainer Maritim</div>
        </div>

        <!-- Connected Vessels -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Armada Kapal</span>
                <span class="w-6 h-6 rounded-md bg-cyan-50 text-cyan-600 flex items-center justify-center text-xs font-bold">🚢</span>
            </div>
            <div class="text-2xl font-black text-cyan-600">{{ number_format($summary['total_vessels']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Kapal Kargo Aktif</div>
        </div>

        <!-- In Transit -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Dalam Pelayaran</span>
                <span class="w-6 h-6 rounded-md bg-amber-50 text-amber-600 flex items-center justify-center text-xs font-bold">⚓</span>
            </div>
            <div class="text-2xl font-black text-amber-600">{{ number_format($summary['in_transit_count']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5 flex items-center gap-1">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                <span>Di Samudra Lepas</span>
            </div>
        </div>

        <!-- Delayed -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Terlambat</span>
                <span class="w-6 h-6 rounded-md bg-rose-50 text-rose-600 flex items-center justify-center text-xs font-bold">⏱️</span>
            </div>
            <div class="text-2xl font-black text-rose-600">{{ number_format($summary['delayed_count']) }}</div>
            <div class="text-[10px] text-rose-500 font-semibold mt-0.5">Delay Melebihi Jadwal</div>
        </div>

        <!-- Arrived / Delivered -->
        <div class="bg-white p-4 rounded-2xl border border-slate-200/90 shadow-xs">
            <div class="flex items-center justify-between text-slate-400 mb-1">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Tiba / Bongkar</span>
                <span class="w-6 h-6 rounded-md bg-emerald-50 text-emerald-600 flex items-center justify-center text-xs font-bold">🏁</span>
            </div>
            <div class="text-2xl font-black text-emerald-600">{{ number_format($summary['arrived_count']) }}</div>
            <div class="text-[10px] text-slate-500 mt-0.5">Pelabuhan Tujuan</div>
        </div>
    </div>

    <!-- B.6 Multi-Criteria Search & Filter Section -->
    <div class="bg-white p-5 rounded-3xl border border-slate-200/90 shadow-xs space-y-4">
        <form method="GET" action="{{ route('tracking.map') }}" class="space-y-4">
            <!-- Row 1: Keyword Search & Shipment Quick Selector -->
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-8 relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           name="q"
                           value="{{ $filters['search'] ?? '' }}"
                           placeholder="Cari nomor resi (CT-...), nomor kontainer (MSKU-...), kargo, atau nama barang..."
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-hidden text-slate-800 placeholder-slate-400">
                </div>

                <div class="md:col-span-4">
                    <select name="code"
                            onchange="this.form.submit()"
                            class="w-full px-3 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold text-slate-800 focus:bg-white focus:ring-2 focus:ring-blue-500 outline-hidden">
                        <option value="">-- Pilih Kontainer Aktif --</option>
                        @foreach($shipments as $s)
                            <option value="{{ $s->tracking_number }}" {{ $selectedShipment && $selectedShipment->id === $s->id ? 'selected' : '' }}>
                                {{ $s->tracking_number }} • {{ $s->vessel->name ?? 'Kapal' }} ({{ $s->origin->city }} &rarr; {{ $s->destination->city }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Row 2: Deep Filters (Vessel, Country, Port, Status, Date Range) -->
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3 text-xs">
                <!-- Filter Vessel -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Kapal Kargo</label>
                    <select name="vessel_id" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden">
                        <option value="">Semua Kapal</option>
                        @foreach($vesselsList as $v)
                            <option value="{{ $v->id }}" {{ ($filters['vessel_id'] ?? '') == $v->id ? 'selected' : '' }}>
                                {{ $v->name }} (IMO {{ $v->imo_number }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Country -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Negara Rute</label>
                    <select name="country" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden">
                        <option value="">Semua Negara</option>
                        @foreach($countriesList as $cnt)
                            <option value="{{ $cnt }}" {{ ($filters['country'] ?? '') == $cnt ? 'selected' : '' }}>
                                {{ $cnt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Port -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Pelabuhan</label>
                    <select name="port_id" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden">
                        <option value="">Semua Pelabuhan</option>
                        @foreach($portsList as $p)
                            <option value="{{ $p->id }}" {{ ($filters['port_id'] ?? '') == $p->id ? 'selected' : '' }}>
                                {{ $p->name }} ({{ $p->code }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Status -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Status Pelayaran</label>
                    <select name="status" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden font-semibold">
                        <option value="">Semua Status</option>
                        <option value="CREATED" {{ ($filters['status'] ?? '') == 'CREATED' ? 'selected' : '' }}>Created</option>
                        <option value="BOOKED" {{ ($filters['status'] ?? '') == 'BOOKED' ? 'selected' : '' }}>Booked</option>
                        <option value="PICKED_UP" {{ ($filters['status'] ?? '') == 'PICKED_UP' ? 'selected' : '' }}>Picked Up</option>
                        <option value="IN_TRANSIT" {{ ($filters['status'] ?? '') == 'IN_TRANSIT' ? 'selected' : '' }}>In Transit</option>
                        <option value="DELIVERED" {{ ($filters['status'] ?? '') == 'DELIVERED' ? 'selected' : '' }}>Delivered</option>
                        <option value="DELAYED" {{ ($filters['status'] ?? '') == 'DELAYED' ? 'selected' : '' }}>Delayed Only</option>
                    </select>
                </div>

                <!-- Filter Date Range: From -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Berangkat Sejak</label>
                    <input type="date" name="start_date" value="{{ $filters['start_date'] ?? '' }}"
                           class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden">
                </div>

                <!-- Filter Date Range: To -->
                <div>
                    <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Tiba Hingga</label>
                    <input type="date" name="end_date" value="{{ $filters['end_date'] ?? '' }}"
                           class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-xl text-xs outline-hidden">
                </div>
            </div>

            <!-- Filter Action Buttons -->
            <div class="flex items-center justify-between pt-2 border-t border-slate-100 text-xs">
                <div class="flex items-center gap-2">
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl shadow-xs transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/>
                        </svg>
                        <span>Terapkan Filter</span>
                    </button>

                    <a href="{{ route('tracking.map') }}"
                       class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold rounded-xl transition">
                        Reset Filter
                    </a>
                </div>

                <div class="text-[11px] text-slate-500">
                    Menampilkan <b>{{ $shipments->count() }}</b> hasil rute kargo laut
                </div>
            </div>
        </form>
    </div>

    @if($selectedShipment)
        <!-- E.4 Top Voyage & Vessel Telemetry Card -->
        <div class="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 rounded-3xl p-6 text-white shadow-lg border border-slate-700/60">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <!-- Vessel Identity -->
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/30 shrink-0">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex flex-wrap items-center gap-2 mb-1">
                            <span class="text-xs font-black uppercase tracking-wider px-2 py-0.5 rounded bg-blue-900/80 text-cyan-300 border border-blue-700/60">
                                {{ $selectedShipment->carrier->name ?? 'Ocean Carrier' }}
                            </span>
                            <span class="text-xs font-mono font-bold text-slate-300">
                                IMO {{ $selectedShipment->vessel?->imo_number ?? '9778791' }}
                            </span>
                            @if($selectedShipment->vessel && $selectedShipment->vessel->mmsi)
                                <span class="text-xs font-mono text-slate-400">
                                    MMSI {{ $selectedShipment->vessel->mmsi }}
                                </span>
                            @endif
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
                                AIS {{ $selectedShipment->vessel?->status ?? 'UNDERWAY' }}
                            </span>
                        </div>
                        <h2 class="text-2xl font-black text-white tracking-tight">
                            {{ $selectedShipment->vessel?->name ?? 'Container Vessel' }}
                        </h2>
                        <div class="flex flex-wrap items-center gap-4 text-xs text-slate-400 mt-1">
                            <span class="flex items-center gap-1 font-mono text-cyan-300">
                                <span>📦 Kontainer:</span> <b>{{ $selectedShipment->container_number ?? 'MSKU-7819201' }}</b>
                            </span>
                            <span>•</span>
                            <span>Rute: <b class="text-white">{{ $selectedShipment->origin->city }} ({{ $selectedShipment->origin->country }}) &rarr; {{ $selectedShipment->destination->city }} ({{ $selectedShipment->destination->country }})</b></span>
                            <span>•</span>
                            <span>Resi: <b class="font-mono text-slate-200">{{ $selectedShipment->tracking_number }}</b></span>
                        </div>
                    </div>
                </div>

                <!-- Vessel Telemetry Gauges -->
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 bg-slate-950/60 p-4 rounded-2xl border border-slate-700/60 text-xs">
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Status Pelayaran</span>
                        <span class="font-black text-emerald-400 font-mono text-sm uppercase">
                            {{ $selectedShipment->current_status }}
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Kecepatan Kapal</span>
                        <span class="font-black text-cyan-300 font-mono text-sm">
                            {{ $selectedShipment->vessel?->current_speed_knots ?? 18.5 }} kts
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Arah Haluan</span>
                        <span class="font-black text-slate-200 font-mono text-sm">
                            {{ $selectedShipment->vessel?->current_heading_degrees ?? 285 }}° NW
                        </span>
                    </div>
                    <div>
                        <span class="text-[10px] uppercase font-bold text-slate-400 block">Cuaca Perairan</span>
                        <span class="font-bold text-sky-300 truncate block text-xs">
                            {{ $vesselWeather['weather_condition'] ?? 'Cerah Berawan' }}
                            @if(isset($vesselWeather['weather_temp_c']))
                                ({{ $vesselWeather['weather_temp_c'] }}°C)
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- E.3 Main Map Section & E.4 Side Detail Panel -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
            <!-- Left / Center: Interactive Leaflet Map (7 cols) -->
            <div class="lg:col-span-7 bg-white rounded-3xl p-5 border border-slate-200/90 shadow-xs flex flex-col gap-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="p-1.5 rounded-lg bg-blue-50 text-blue-600">🗺️</span>
                        <div>
                            <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Rute Samudra Antar Pelabuhan (Ocean Voyage Map)</h3>
                            <p class="text-[11px] text-slate-500">Posisi real-time satelit AIS, pelabuhan muat & bongkar, dan waypoint laut</p>
                        </div>
                    </div>
                    <span class="text-[11px] font-mono font-bold px-2 py-1 rounded bg-slate-100 text-slate-600 border border-slate-200">
                        Leaflet + OSM Marine
                    </span>
                </div>

                <!-- Leaflet Map Container -->
                <div class="relative w-full rounded-2xl overflow-hidden border border-slate-200 bg-slate-900">
                    <div id="map" class="w-full h-[560px] z-10"></div>
                </div>

                <!-- Map Legend -->
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200/80 flex flex-wrap items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-emerald-500 ring-2 ring-emerald-200"></span>
                            <span class="font-semibold text-slate-700">Pelabuhan Asal</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-blue-500 ring-2 ring-blue-200"></span>
                            <span class="font-semibold text-slate-700">Posisi Kapal (AIS)</span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-3 h-3 rounded-full bg-purple-500 ring-2 ring-purple-200"></span>
                            <span class="font-semibold text-slate-700">Pelabuhan Tujuan</span>
                        </div>
                    </div>
                    <span class="text-[11px] text-slate-500 font-medium">Klik marker untuk melihat rincian cuaca & waktu</span>
                </div>
            </div>

            <!-- Right: E.4 Shipment & Vessel Detail Panel (5 cols) -->
            <div class="lg:col-span-5 space-y-5">
                <!-- Panel 1: Shipment & ETA Overview -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Rincian Pengiriman & Kalkulasi ETA</h3>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black {{ $etaDetails['is_delayed'] ? 'bg-rose-100 text-rose-800' : 'bg-emerald-100 text-emerald-800' }}">
                            {{ $etaDetails['difference_label'] }}
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs">
                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Jadwal Keberangkatan</span>
                            <span class="font-bold text-slate-800">
                                {{ $selectedShipment->expected_departure ? $selectedShipment->expected_departure->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Jadwal Awal (Official ETA)</span>
                            <span class="font-bold text-slate-800">
                                {{ $selectedShipment->expected_arrival ? $selectedShipment->expected_arrival->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-xl {{ $etaDetails['is_delayed'] ? 'bg-rose-50/70 border border-rose-200' : 'bg-emerald-50/70 border border-emerald-200' }}">
                            <span class="text-[10px] uppercase font-bold {{ $etaDetails['is_delayed'] ? 'text-rose-600' : 'text-emerald-600' }} block">Estimasi Sistem Terkini (ETA)</span>
                            <span class="font-extrabold {{ $etaDetails['is_delayed'] ? 'text-rose-700' : 'text-emerald-700' }} text-sm">
                                {{ $selectedShipment->estimated_arrival ? $selectedShipment->estimated_arrival->format('d M Y, H:i') : '-' }}
                            </span>
                        </div>

                        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
                            <span class="text-[10px] uppercase font-bold text-slate-400 block">Keterlambatan (Severity)</span>
                            <span class="font-black text-sm {{ $etaDetails['is_delayed'] ? 'text-rose-600' : 'text-slate-800' }}">
                                {{ $etaDetails['delay_hours'] }} Jam ({{ $etaDetails['severity'] }})
                            </span>
                        </div>
                    </div>

                    <div class="pt-2 border-t border-slate-100 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-slate-500">Customer:</span>
                            <span class="font-bold text-slate-800">{{ $selectedShipment->customer->name ?? 'Acme Corp' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Jenis Kargo:</span>
                            <span class="font-bold text-slate-800">{{ $selectedShipment->cargo_type }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Total Berat & Volume:</span>
                            <span class="font-bold text-slate-800">{{ number_format($selectedShipment->total_weight_kg, 1) }} kg • {{ number_format($selectedShipment->total_volume_cbm, 2) }} CBM</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Posisi Terakhir Terdata:</span>
                            <span class="font-bold text-slate-800">
                                {{ $selectedShipment->vessel?->last_latitude ?? '-' }}, {{ $selectedShipment->vessel?->last_longitude ?? '-' }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-slate-500">Update Terakhir:</span>
                            <span class="font-mono text-slate-600">
                                {{ $selectedShipment->vessel?->last_position_at ? $selectedShipment->vessel->last_position_at->diffForHumans() : 'Baru saja' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Panel 2: E.6 Live Marine Weather & Port Condition Monitoring -->
                <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-4">
                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span class="p-1 rounded bg-sky-50 text-sky-600">🌤️</span>
                            <h3 class="text-sm font-extrabold text-slate-900 tracking-tight">Kondisi Cuaca Laut & Kepadatan Pelabuhan</h3>
                        </div>
                        <span class="text-[10px] font-mono text-slate-400">Open-Meteo Live</span>
                    </div>

                    <!-- Vessel Marine Weather -->
                    <div class="p-3.5 rounded-2xl bg-sky-50/70 border border-sky-200 space-y-2">
                        <div class="flex items-center justify-between text-xs font-bold text-sky-900">
                            <span>Cuaca di Posisi Kapal Terkini</span>
                            <span class="font-mono">{{ $vesselWeather['weather_temp_c'] ?? 28.5 }}°C</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2 text-[11px] text-sky-800">
                            <div>
                                <span class="text-slate-400 block text-[10px]">Kondisi</span>
                                <b>{{ $vesselWeather['weather_condition'] ?? 'Cerah' }}</b>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Angin Laut</span>
                                <b>{{ $vesselWeather['weather_wind_speed_kmh'] ?? 18 }} km/h</b>
                            </div>
                            <div>
                                <span class="text-slate-400 block text-[10px]">Status Ombak</span>
                                <b>{{ $vesselWeather['sea_state'] ?? 'Tenang' }}</b>
                            </div>
                        </div>
                    </div>

                    <!-- Destination Port Condition -->
                    @if($destinationPortActivity)
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-2">
                            <div class="flex items-center justify-between text-xs font-bold text-slate-800">
                                <span>Kepadatan Pelabuhan Tujuan ({{ $selectedShipment->destination->name }})</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] bg-emerald-100 text-emerald-800 font-bold">
                                    {{ $destinationPortActivity['congestion_level'] }}
                                </span>
                            </div>
                            <div class="grid grid-cols-2 gap-2 text-[11px] text-slate-600">
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Kapal Menuju Pelabuhan</span>
                                    <b>{{ $destinationPortActivity['inbound_vessels'] }} Kapal Kargo</b>
                                </div>
                                <div>
                                    <span class="text-slate-400 block text-[10px]">Status Dermaga</span>
                                    <b>{{ $destinationPortActivity['status'] }}</b>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Panel 3: E.7 Active Exceptions & Incidents Alerts -->
                @if($selectedShipment->exceptions->isNotEmpty())
                    <div class="p-5 rounded-3xl bg-rose-50 border border-rose-200 space-y-3">
                        <div class="flex items-center justify-between text-rose-800">
                            <div class="flex items-center gap-2 font-black text-xs">
                                <span>⚠️</span>
                                <span>Gangguan Pelayaran Aktif (Exception Alert)</span>
                            </div>
                            <span class="text-[10px] font-mono uppercase px-2 py-0.5 rounded bg-rose-200/60 font-bold">
                                {{ $selectedShipment->exceptions->count() }} Gangguan
                            </span>
                        </div>

                        <div class="space-y-2">
                            @foreach($selectedShipment->exceptions as $exc)
                                <div class="bg-white/80 p-3 rounded-2xl border border-rose-200 text-xs">
                                    <div class="flex items-center justify-between font-bold text-rose-900">
                                        <span>{{ $exc->exception_type }} (Tingkat: {{ $exc->severity }})</span>
                                        <span class="font-mono text-[10px] uppercase px-1.5 py-0.5 rounded bg-rose-100 font-bold text-rose-800">{{ $exc->status }}</span>
                                    </div>
                                    <p class="text-[11px] text-rose-700 mt-1 leading-relaxed">{{ $exc->description }}</p>
                                    <div class="text-[10px] text-slate-400 font-mono mt-1">Dilaporkan: {{ $exc->reported_at ? $exc->reported_at->diffForHumans() : 'Hari ini' }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- NEW: Cargo Valuation & Multi-Currency Conditioning Card -->
        @if($valuation)
            <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 pb-4 border-b border-slate-100">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 font-bold">💰</span>
                            <h3 class="text-base font-extrabold text-slate-900 tracking-tight">
                                Valuasi Kargo & Penyesuaian Mata Uang Tiap Negara
                            </h3>
                        </div>
                        <p class="text-xs text-slate-500 mt-0.5">
                            Harga barang dikondisikan otomatis dengan mata uang negara tujuan (<b>{{ $valuation['destination']['country'] }} - {{ $valuation['destination']['code'] }}</b>) dan negara asal melalui referensi kurs bank sentral (Frankfurter API).
                        </p>
                    </div>

                    <!-- Live Exchange Rate Badge -->
                    <div class="flex items-center gap-2 bg-slate-50 px-3.5 py-2 rounded-xl border border-slate-200 text-xs">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        <span class="text-slate-500">Kurs Acuan:</span>
                        <span class="font-mono font-extrabold text-slate-900">{{ $valuation['exchange_rate']['pair'] }}</span>
                    </div>
                </div>

                <!-- 3 Currency Conversion Cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Base Currency (USD) -->
                    <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80">
                        <div class="flex items-center justify-between text-slate-400 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Nilai Kargo Standar (Base)</span>
                            <span class="text-xs font-mono font-bold text-slate-600">USD ($)</span>
                        </div>
                        <div class="text-2xl font-black text-slate-900">{{ $valuation['base']['formatted'] }}</div>
                        <div class="text-[11px] text-slate-500 mt-1">Mata uang referensi kargo internasional</div>
                    </div>

                    <!-- Destination Country Currency (Conditional) -->
                    <div class="p-4 rounded-2xl bg-emerald-50/70 border border-emerald-200">
                        <div class="flex items-center justify-between text-emerald-700 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-emerald-700">Mata Uang Negara Tujuan</span>
                            <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-emerald-200/60 text-emerald-800">
                                {{ $valuation['destination']['country'] }} ({{ $valuation['destination']['code'] }})
                            </span>
                        </div>
                        <div class="text-2xl font-black text-emerald-700">{{ $valuation['destination']['formatted'] }}</div>
                        <div class="text-[11px] text-emerald-600 mt-1 flex items-center gap-1 font-semibold">
                            <span>✓ Dikonversikan untuk kepabeanan & biaya lokal di {{ $selectedShipment->destination->city }}</span>
                        </div>
                    </div>

                    <!-- Origin Country Currency -->
                    <div class="p-4 rounded-2xl bg-blue-50/70 border border-blue-200">
                        <div class="flex items-center justify-between text-blue-700 mb-1">
                            <span class="text-[10px] font-bold uppercase tracking-wider text-blue-700">Mata Uang Negara Asal</span>
                            <span class="text-xs font-mono font-bold px-1.5 py-0.5 rounded bg-blue-200/60 text-blue-800">
                                {{ $valuation['origin']['country'] }} ({{ $valuation['origin']['code'] }})
                            </span>
                        </div>
                        <div class="text-2xl font-black text-blue-700">{{ $valuation['origin']['formatted'] }}</div>
                        <div class="text-[11px] text-blue-600 mt-1">Nilai perolehan barang di pelabuhan muat</div>
                    </div>
                </div>

                <!-- Itemized Manifest & Price Table -->
                <div class="overflow-hidden rounded-2xl border border-slate-200/80">
                    <div class="bg-slate-50 px-5 py-3 border-b border-slate-200/80 flex items-center justify-between">
                        <h4 class="text-xs font-extrabold uppercase tracking-wider text-slate-700">
                            Manifest Rincian Barang & Harga Tiap Item (Itemized Cargo Pricing)
                        </h4>
                        <span class="text-[11px] font-mono text-slate-500 font-semibold">
                            Total Item: {{ $selectedShipment->items->count() }} unit jenis kargo
                        </span>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-slate-500 font-bold uppercase text-[10px] tracking-wider border-b border-slate-200">
                                <tr>
                                    <th class="px-5 py-3">Nama Barang</th>
                                    <th class="px-5 py-3">Kode HS</th>
                                    <th class="px-5 py-3 text-center">Jumlah & Berat</th>
                                    <th class="px-5 py-3 text-right">Harga Satuan (USD)</th>
                                    <th class="px-5 py-3 text-right">Total Nilai (USD)</th>
                                    <th class="px-5 py-3 text-right text-emerald-700 bg-emerald-50/50">
                                        Nilai di Negara Tujuan ({{ $valuation['destination']['code'] }})
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @forelse($selectedShipment->items as $item)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="px-5 py-3.5 font-bold text-slate-900">
                                            {{ $item->item_name }}
                                        </td>
                                        <td class="px-5 py-3.5 font-mono text-slate-500 text-[11px]">
                                            {{ $item->hs_code ?? '-' }}
                                        </td>
                                        <td class="px-5 py-3.5 text-center text-slate-700">
                                            <b>{{ number_format($item->quantity) }}</b> unit
                                            <span class="text-slate-400 text-[10px]">({{ $item->weight_kg }} kg/u)</span>
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono text-slate-800">
                                            $ {{ number_format($item->unit_price, 2) }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono font-bold text-slate-900">
                                            $ {{ number_format($item->total_price, 2) }}
                                        </td>
                                        <td class="px-5 py-3.5 text-right font-mono font-extrabold text-emerald-700 bg-emerald-50/30">
                                            {{ $item->total_price_dest_formatted ?? '-' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-5 py-6 text-center text-slate-400">
                                            Belum ada manifest rincian barang untuk pengiriman ini.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-slate-50 font-bold border-t border-slate-200">
                                <tr>
                                    <td colspan="4" class="px-5 py-3 text-right text-slate-700 uppercase tracking-wider text-[11px]">
                                        Total Valuasi Kargo:
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm font-black text-slate-900">
                                        {{ $valuation['base']['formatted'] }}
                                    </td>
                                    <td class="px-5 py-3 text-right font-mono text-sm font-black text-emerald-700 bg-emerald-100/50">
                                        {{ $valuation['destination']['formatted'] }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>

                <!-- Interactive Multi-Currency Simulator -->
                <div class="p-4 rounded-2xl bg-gradient-to-r from-blue-900 to-slate-900 text-white flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h4 class="text-xs font-black uppercase tracking-wider text-cyan-300">
                            Simulasi Konversi Mata Uang Lainnya
                        </h4>
                        <p class="text-[11px] text-slate-300 mt-0.5">
                            Pilih mata uang negara lain untuk mengonversikan total nilai kargo (<b>${{ number_format($selectedShipment->total_declared_value, 2) }} USD</b>) secara instan.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <select id="currency-switcher"
                                onchange="calculateConvertedValue(this.value)"
                                class="bg-slate-800 text-white border border-slate-700 text-xs font-bold rounded-xl px-3 py-2 outline-hidden">
                            <option value="EUR">Euro (EUR €) - Eropa</option>
                            <option value="IDR">Rupiah (IDR Rp) - Indonesia</option>
                            <option value="SGD">Singapore Dollar (SGD S$) - Singapura</option>
                            <option value="JPY">Japanese Yen (JPY ¥) - Jepang</option>
                            <option value="AUD">Australian Dollar (AUD A$) - Australia</option>
                            <option value="GBP">British Pound (GBP £) - Inggris</option>
                            <option value="CNY">Chinese Yuan (CNY ¥) - China</option>
                        </select>

                        <div id="currency-converted-result" class="font-mono text-base font-black text-cyan-300 px-3 py-1.5 rounded-xl bg-slate-950/70 border border-cyan-500/30">
                            {{ $valuation['destination']['formatted'] }}
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- E.5 Tracking Timeline Section (Voyage Log) -->
        <div class="bg-white rounded-3xl p-6 border border-slate-200/90 shadow-xs space-y-5">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-3 border-b border-slate-100">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900 tracking-tight">Kronologi Riwayat Perjalanan (Voyage Log Timeline)</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Riwayat peristiwa pelayaran bersifat append-only immutable tanpa penimpaan data</p>
                </div>
                <div class="flex items-center gap-2">
                    <span class="text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200">
                        {{ $selectedShipment->trackingEvents->count() }} Peristiwa Tercatat
                    </span>
                    <a href="{{ route('tracking.export', ['code' => $selectedShipment->tracking_number]) }}" class="text-xs text-blue-600 hover:text-blue-800 font-bold flex items-center gap-1">
                        Export Timeline &rarr;
                    </a>
                </div>
            </div>

            <!-- Timeline List -->
            <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
                @forelse($selectedShipment->trackingEvents as $index => $event)
                    <div class="relative pl-7 pb-4 border-l-2 {{ $loop->last ? 'border-transparent' : 'border-blue-200' }}">
                        <!-- Node Indicator -->
                        <div class="absolute -left-[9px] top-0 w-4 h-4 rounded-full border-2 border-white {{ $event->event_type === 'ORIGIN' ? 'bg-emerald-500' : ($event->event_type === 'DESTINATION' ? 'bg-purple-600' : 'bg-blue-600') }} shadow-xs"></div>

                        <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 hover:bg-white hover:border-blue-300 transition shadow-2xs">
                            <div class="flex flex-wrap items-center justify-between gap-2 mb-1.5">
                                <span class="text-[10px] font-black uppercase tracking-wider px-2 py-0.5 rounded {{ $event->event_type === 'ORIGIN' ? 'bg-emerald-100 text-emerald-800' : ($event->event_type === 'DESTINATION' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ $event->event_type ?? 'CHECKPOINT' }}
                                </span>
                                <span class="text-xs font-mono text-slate-500">
                                    {{ $event->event_time ? $event->event_time->format('d M Y, H:i') : '-' }} UTC
                                </span>
                            </div>

                            <div class="font-extrabold text-slate-900 text-sm flex items-center gap-1.5">
                                <span>📍</span>
                                <span>{{ $event->location_name ?? 'Laut Samudra (At Sea)' }}</span>
                            </div>

                            @if($event->notes)
                                <p class="text-xs text-slate-600 mt-1 leading-relaxed">
                                    {{ $event->notes }}
                                </p>
                            @endif

                            <div class="mt-2.5 pt-2 border-t border-slate-200/60 flex flex-wrap items-center gap-3 text-xs">
                                @if($event->weather_condition || $event->weather_temp_c !== null)
                                    <div class="flex items-center gap-1 text-sky-700 bg-sky-50 px-2 py-0.5 rounded-md font-semibold text-[11px]">
                                        <span>🌤️</span>
                                        <span>{{ $event->weather_condition ?? 'Cuaca Maritim' }}</span>
                                        @if($event->weather_temp_c !== null)
                                            <span class="font-bold">({{ $event->weather_temp_c }}°C)</span>
                                        @endif
                                    </div>
                                @endif

                                @if($event->latitude && $event->longitude)
                                    <div class="text-[11px] font-mono text-slate-400">
                                        Koord: {{ number_format($event->latitude, 4) }}, {{ number_format($event->longitude, 4) }}
                                    </div>
                                @endif

                                <div class="text-[10px] text-slate-400 uppercase font-mono ms-auto">
                                    Sumber: {{ $event->source ?? 'CARGOTRACE_SYSTEM' }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-8 text-slate-400 text-xs">
                        Belum ada riwayat timeline tracking yang tercatat untuk kontainer ini.
                    </div>
                @endforelse
            </div>
        </div>
    @else
        <!-- Empty State for Empty Search Result -->
        <div class="bg-white rounded-3xl p-12 text-center border border-slate-200 shadow-xs space-y-3">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center text-3xl font-bold">
                🚢
            </div>
            <h3 class="text-lg font-bold text-slate-900">Tidak ada pengiriman yang cocok dengan filter</h3>
            <p class="text-xs text-slate-500 max-w-md mx-auto">
                Silakan sesuaikan kriteria pencarian, pilih kapal lain, atau klik tombol di bawah untuk melihat seluruh armada aktif.
            </p>
            <div>
                <a href="{{ route('tracking.map') }}" class="inline-block px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs rounded-xl shadow transition">
                    Tampilkan Semua Pengiriman
                </a>
            </div>
        </div>
    @endif
</div>

<!-- Leaflet Map Controller & Live Currency Script -->
<script>
// Live Currency Simulator function
const globalRates = @json($rates ?? []);
const baseDeclaredAmount = {{ $selectedShipment ? (float)$selectedShipment->total_declared_value : 0 }};

function calculateConvertedValue(targetCurrency) {
    const rate = globalRates[targetCurrency] || 1.0;
    const converted = baseDeclaredAmount * rate;
    const resEl = document.getElementById('currency-converted-result');
    if (!resEl) return;

    const symbols = {
        'EUR': '€ ',
        'IDR': 'Rp ',
        'SGD': 'S$ ',
        'JPY': '¥ ',
        'AUD': 'A$ ',
        'GBP': '£ ',
        'CNY': '¥ ',
        'USD': '$ '
    };

    const symbol = symbols[targetCurrency] || targetCurrency + ' ';
    const decimals = (targetCurrency === 'IDR' || targetCurrency === 'JPY') ? 0 : 2;
    resEl.textContent = symbol + new Intl.NumberFormat('id-ID', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(converted);
}

document.addEventListener('DOMContentLoaded', function () {
    @if($selectedShipment)
        const mapContainer = document.getElementById('map');
        if (!mapContainer || typeof L === 'undefined') return;

        // Initialize Map with full dragging, panning, touch, and seamless horizontal wrapping
        const map = L.map('map', {
            zoomControl: true,
            scrollWheelZoom: true,
            dragging: true,
            touchZoom: true,
            doubleClickZoom: true,
            boxZoom: true,
            keyboard: true,
            worldCopyJump: true,
            minZoom: 2,
            maxZoom: 18,
            inertia: true,
            inertiaDeceleration: 3000
        }).setView([ -6.10, 106.88 ], 5);

        // Explicitly guarantee dragging is enabled
        map.dragging.enable();

        // Invalidate size to calculate exact dimensions inside flex/grid layouts
        setTimeout(function() {
            map.invalidateSize();
        }, 150);

        // OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 18,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors • CargoTrace Maritime'
        }).addTo(map);

        // SVG Marker Helper
        function createIcon(color, emoji) {
            return L.divIcon({
                className: 'custom-maritime-marker',
                html: `
                    <div style="display:flex;align-items:center;justify-content:center;width:34px;height:34px;border-radius:50%;background:${color};border:2px solid #ffffff;box-shadow:0 4px 10px rgba(0,0,0,0.3);font-size:16px;">
                        ${emoji}
                    </div>
                `,
                iconSize: [34, 34],
                iconAnchor: [17, 17],
                popupAnchor: [0, -17]
            });
        }

        const originIcon = createIcon('#10b981', '⚓');
        const vesselIcon = createIcon('#2563eb', '🚢');
        const transitIcon = createIcon('#0284c7', '📍');
        const destIcon = createIcon('#9333ea', '🏁');

        const points = [];
        const latLngs = [];

        // 1. Add Origin Port Marker
        @if($selectedShipment->origin && $selectedShipment->origin->latitude && $selectedShipment->origin->longitude)
            points.push({
                lat: {{ $selectedShipment->origin->latitude }},
                lng: {{ $selectedShipment->origin->longitude }},
                title: "Pelabuhan Asal: {{ $selectedShipment->origin->name }} ({{ $selectedShipment->origin->city }}, {{ $selectedShipment->origin->country }})",
                type: 'ORIGIN',
                icon: originIcon,
                desc: "Titik tolak muat kargo • Mata Uang: {{ $valuation['origin']['code'] ?? 'IDR' }}"
            });
        @endif

        // 2. Add Tracking Events & Historical Checkpoints
        @foreach($selectedShipment->trackingEvents as $ev)
            @if($ev->latitude && $ev->longitude)
                points.push({
                    lat: {{ $ev->latitude }},
                    lng: {{ $ev->longitude }},
                    title: "{{ $ev->location_name ?? 'At Sea' }}",
                    type: '{{ $ev->event_type ?? "CHECKPOINT" }}',
                    icon: '{{ $ev->event_type }}' === 'ORIGIN' ? originIcon : ('{{ $ev->event_type }}' === 'DESTINATION' ? destIcon : ('{{ $ev->event_type }}' === 'IN_TRANSIT' ? vesselIcon : transitIcon)),
                    desc: "{{ $ev->notes ?? 'Waypoint pelayaran' }}",
                    weather: "{{ $ev->weather_condition ? $ev->weather_condition . ($ev->weather_temp_c !== null ? ' (' . $ev->weather_temp_c . '°C)' : '') : '' }}",
                    time: "{{ $ev->event_time ? $ev->event_time->format('d M Y, H:i') : '' }}"
                });
            @endif
        @endforeach

        // 3. Add Live Vessel Marker if coordinates differ from latest event
        @if($selectedShipment->vessel && $selectedShipment->vessel->last_latitude && $selectedShipment->vessel->last_longitude)
            points.push({
                lat: {{ $selectedShipment->vessel->last_latitude }},
                lng: {{ $selectedShipment->vessel->last_longitude }},
                title: "Kapal: {{ $selectedShipment->vessel->name }} (Posisi Satelit AIS)",
                type: 'VESSEL',
                icon: vesselIcon,
                desc: "Kecepatan: {{ $selectedShipment->vessel->last_speed_knots ?? 18 }} knots • Haluan: {{ $selectedShipment->vessel->last_heading_degrees ?? 280 }}°",
                time: "{{ $selectedShipment->vessel->last_position_at ? $selectedShipment->vessel->last_position_at->format('d M Y, H:i') : 'Real-time' }}"
            });
        @endif

        // 4. Add Destination Port Marker
        @if($selectedShipment->destination && $selectedShipment->destination->latitude && $selectedShipment->destination->longitude)
            points.push({
                lat: {{ $selectedShipment->destination->latitude }},
                lng: {{ $selectedShipment->destination->longitude }},
                title: "Pelabuhan Tujuan: {{ $selectedShipment->destination->name }} ({{ $selectedShipment->destination->city }}, {{ $selectedShipment->destination->country }})",
                type: 'DESTINATION',
                icon: destIcon,
                desc: "Pelabuhan akhir pembongkaran kargo • Valuasi: {{ $valuation['destination']['formatted'] ?? '-' }}"
            });
        @endif

        // Render markers & polyline
        points.forEach(function (pt) {
            latLngs.push([pt.lat, pt.lng]);
            const marker = L.marker([pt.lat, pt.lng], { icon: pt.icon }).addTo(map);

            let popupContent = `
                <div style="font-family: inherit; font-size: 12px; line-height: 1.4; min-width: 190px;">
                    <div style="font-weight: 800; font-size: 13px; color: #0f172a; margin-bottom: 4px;">
                        ${pt.title}
                    </div>
                    <div style="color: #475569; margin-bottom: 4px;">
                        ${pt.desc}
                    </div>
            `;
            if (pt.time) {
                popupContent += `<div style="font-size: 11px; color: #64748b;">🕒 ${pt.time}</div>`;
            }
            if (pt.weather) {
                popupContent += `
                    <div style="margin-top: 6px; padding: 4px 8px; border-radius: 6px; background: #f0f9ff; color: #0369a1; font-weight: 600; font-size: 11px;">
                        🌤️ ${pt.weather}
                    </div>
                `;
            }
            popupContent += `</div>`;
            marker.bindPopup(popupContent);
        });

        let voyagePolyline = null;
        if (latLngs.length > 1) {
            voyagePolyline = L.polyline(latLngs, {
                color: '#0284c7',
                weight: 4,
                opacity: 0.85,
                dashArray: '8, 8',
                lineJoin: 'round'
            }).addTo(map);

            map.fitBounds(voyagePolyline.getBounds(), { padding: [50, 50] });
        } else if (latLngs.length === 1) {
            map.setView(latLngs[0], 6);
        }

        // Add custom Floating Reset View Button directly on the map
        const resetControl = L.control({ position: 'topright' });
        resetControl.onAdd = function () {
            const div = L.DomUtil.create('div', 'leaflet-bar');
            div.innerHTML = `
                <button type="button" title="Pusatkan Seluruh Rute" style="background:#ffffff;border:none;padding:6px 10px;font-size:11px;font-weight:700;color:#1e293b;cursor:pointer;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.2);display:flex;align-items:center;gap:4px;">
                    <span>🧭</span> <span>Fit Rute</span>
                </button>
            `;
            div.style.cursor = 'pointer';
            L.DomEvent.disableClickPropagation(div);
            div.onclick = function () {
                if (voyagePolyline) {
                    map.fitBounds(voyagePolyline.getBounds(), { padding: [50, 50] });
                } else if (latLngs.length > 0) {
                    map.setView(latLngs[0], 6);
                }
            };
            return div;
        };
        resetControl.addTo(map);
    @endif
});
</script>
@endsection
