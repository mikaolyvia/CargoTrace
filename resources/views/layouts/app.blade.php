<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'CargoTrace - Global Maritime Visibility & Automated Tracking' }}</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>

    <!-- Leaflet CSS & JS in Head for Guaranteed Tile & Drag Support -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, -apple-system, sans-serif;
        }
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background-color: #334155;
            border-radius: 4px;
        }

        /* Essential Leaflet rules so panning and dragging work seamlessly with Tailwind */
        .leaflet-pane, .leaflet-tile, .leaflet-marker-icon, .leaflet-marker-shadow,
        .leaflet-tile-container, .leaflet-pane > svg, .leaflet-pane > canvas {
            position: absolute !important;
            left: 0;
            top: 0;
        }
        .leaflet-container img {
            max-width: none !important;
            max-height: none !important;
        }
        .leaflet-container {
            cursor: grab !important;
            touch-action: pan-x pan-y !important;
            user-select: none !important;
            -webkit-user-select: none !important;
        }
        .leaflet-container:active,
        .leaflet-dragging .leaflet-container {
            cursor: grabbing !important;
        }
        #map {
            width: 100%;
            height: 540px;
            position: relative;
            z-index: 10;
        }
    </style>
</head>

<body class="h-full flex overflow-hidden text-slate-800 bg-slate-100">
    <!-- Left Maritime SaaS Sidebar Navigation -->
    <aside class="w-64 bg-slate-900 text-slate-300 flex flex-col justify-between border-r border-slate-800 select-none z-40 shrink-0">
        <div>
            <!-- Maritime Brand Header -->
            <div class="h-18 px-5 flex items-center justify-between border-b border-slate-800/80 bg-slate-950/60">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25 group-hover:scale-105 transition-transform">
                        <!-- Cargo Vessel Anchor SVG Icon -->
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18m0-18c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm-8 11a8 8 0 0016 0M4 14h2m12 0h2"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-1.5">
                            <span class="text-lg font-black tracking-tight text-white">Cargo<span class="text-cyan-400">Trace</span></span>
                        </div>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-1.5 py-0.5 rounded bg-blue-900/60 text-cyan-300 border border-blue-700/50">Maritime SaaS</span>
                    </div>
                </a>
            </div>

            <!-- Navigation Links -->
            <nav class="p-4 space-y-1.5 sidebar-scroll overflow-y-auto max-h-[calc(100vh-180px)] text-xs font-semibold">
                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 px-3 pt-2 pb-1">
                    Operasional Laut
                </div>

                <!-- Dashboard -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard', 'home') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                    <span>Dashboard Metrik</span>
                </a>

                <!-- Live AIS Fleet Map -->
                <a href="{{ route('tracking.map') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('tracking.map', 'tracking.index', 'tracking.track') ? 'bg-blue-600 text-white shadow-md shadow-blue-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                        </svg>
                        <span>Peta Kapal & AIS</span>
                    </div>
                    <span class="flex items-center gap-1 text-[10px] font-black uppercase tracking-wider px-1.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-400 border border-emerald-500/40 animate-pulse">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> LIVE
                    </span>
                </a>

                <!-- Vessels Fleet -->
                <a href="{{ route('dashboard') }}#vessels-section"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                    </svg>
                    <span>Armada Kapal (Fleet)</span>
                </a>

                <!-- Shipments List -->
                <a href="{{ route('dashboard') }}#shipments-table"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    <span>Pengiriman Kontainer</span>
                </a>

                <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 px-3 pt-4 pb-1">
                    Kontrol & Audit
                </div>

                <!-- Exceptions -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <div class="flex items-center gap-3">
                        <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Gangguan (Exceptions)</span>
                    </div>
                </a>

                <!-- Notifications -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                    <span>Notifikasi Sistem</span>
                </a>

                <!-- Audit Logs -->
                <a href="{{ route('dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Jejak Audit</span>
                </a>
            </nav>
        </div>

        <!-- User Profile Card in Sidebar Bottom -->
        <div class="p-4 border-t border-slate-800 bg-slate-950/70">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center font-bold text-white text-xs shadow">
                    OP
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-white truncate">Logistics Dispatcher</p>
                    <div class="flex items-center gap-1.5 mt-0.5">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>
                        <span class="text-[10px] font-semibold text-slate-400 uppercase tracking-wider">OPERATOR</span>
                    </div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main App Content (Header + Scrollable Body) -->
    <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
        <!-- Modern SaaS Top Header Bar -->
        <header class="h-18 bg-white border-b border-slate-200/80 px-6 flex items-center justify-between shrink-0 shadow-xs z-30">
            <!-- Global Search Bar -->
            <div class="flex-1 max-w-lg">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <input type="text"
                           placeholder="Cari nomor resi (CT-...), kontainer (MSKU-...), atau nama kapal (IMO)..."
                           class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition outline-hidden text-slate-800 placeholder-slate-400">
                </div>
            </div>

            <!-- Header Status Pills & Actions -->
            <div class="flex items-center gap-4">
                <!-- Status Telemetry AIS -->
                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-800 border border-emerald-200/80 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                    <span>AIS Live Telemetry: <b>Active</b></span>
                </div>

                <!-- Status Marine Weather -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-sky-50 text-sky-800 border border-sky-200/80 text-xs font-semibold">
                    <span>🌤️</span>
                    <span>Open-Meteo Marine: <b>Connected</b></span>
                </div>

                <div class="h-6 w-px bg-slate-200"></div>

                <!-- Direct Quick Action: Track Map -->
                <a href="{{ route('tracking.map') }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-bold text-xs shadow-md shadow-blue-500/20 transition cursor-pointer">
                    <svg class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/>
                    </svg>
                    <span>Buka Peta Pelayaran</span>
                </a>
            </div>
        </header>

        <!-- Dynamic Main Scrollable Workspace -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-8 bg-slate-50/80">
            @yield('content')
        </main>
    </div>
</body>
</html>
