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

            <!-- Navigation Links Tailored by Role -->
            @php
                $currentUser = Auth::user();
                $unreadNotificationsCount = $currentUser ? \App\Models\Notification::where('user_id', $currentUser->id)->unread()->count() : 0;
            @endphp
            <nav class="p-4 space-y-1.5 sidebar-scroll overflow-y-auto max-h-[calc(100vh-180px)] text-xs font-semibold">
                @if($currentUser?->isCustomer())
                    <!-- Customer View Navigation -->
                    <div class="text-[10px] uppercase font-bold tracking-wider text-cyan-400 px-3 pt-2 pb-1">
                        Portal Pelanggan Kargo
                    </div>

                    <!-- Customer My Cargo -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard', 'home') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4 text-cyan-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Kargo & Kontainer Saya</span>
                    </a>

                    <!-- Tracking Map -->
                    <a href="{{ route('tracking.map') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('tracking.map', 'tracking.index', 'tracking.track') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <span>Peta Pelacakan Kargo</span>
                        </div>
                    </a>

                    <!-- Customer Notifications -->
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('notifications.*') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Notifikasi Kargo</span>
                        </div>
                        @if($unreadNotificationsCount > 0)
                            <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Maritime Glossary Guide -->
                    <a href="{{ route('dashboard') }}#maritime-glossary"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Panduan & Glosarium</span>
                    </a>

                @elseif($currentUser?->isOperator())
                    <!-- Logistics Operator Navigation -->
                    <div class="text-[10px] uppercase font-bold tracking-wider text-cyan-400 px-3 pt-2 pb-1">
                        Pusat Kontrol Operasional
                    </div>

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard', 'home') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard Operasional</span>
                    </a>

                    <!-- Live AIS Fleet Map -->
                    <a href="{{ route('tracking.map') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('tracking.map', 'tracking.index', 'tracking.track') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <span>Peta Kapal & AIS Radar</span>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.2 rounded-full bg-cyan-500/20 text-cyan-400 border border-cyan-500/40">
                            LIVE
                        </span>
                    </a>

                    <!-- Vessels Fleet -->
                    <a href="{{ route('dashboard') }}#vessels-section"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l1.5-9h15L21 17M3 17l2 4h14l2-4M3 17h18M6 8V4h4v4m4-4h4v4"/>
                        </svg>
                        <span>Armada Kapal (Fleet)</span>
                    </a>

                    <!-- Shipments List -->
                    <a href="{{ route('dashboard') }}#shipments-table"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Manajemen Pengiriman</span>
                    </a>

                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 px-3 pt-4 pb-1">
                        Disposisi & Peringatan
                    </div>

                    <!-- Exceptions -->
                    <a href="{{ route('dashboard') }}#exceptions-section"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Pusat Gangguan</span>
                        </div>
                    </a>

                    <!-- Notifications -->
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('notifications.*') ? 'bg-cyan-600 text-white shadow-md shadow-cyan-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Notifikasi Operasional</span>
                        </div>
                        @if($unreadNotificationsCount > 0)
                            <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Audit Logs for Operator -->
                    <a href="{{ route('audit.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('audit.*') ? 'bg-cyan-600 text-white shadow-md font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Jejak Audit</span>
                    </a>

                @else
                    <!-- Admin & Global Navigation -->
                    <div class="text-[10px] uppercase font-bold tracking-wider text-purple-400 px-3 pt-2 pb-1">
                        Pusat Kendali Eksekutif
                    </div>

                    <!-- Dashboard -->
                    <a href="{{ route('dashboard') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('dashboard', 'home') ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        <span>Dashboard Eksekutif</span>
                    </a>

                    <!-- Live AIS Fleet Map -->
                    <a href="{{ route('tracking.map') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('tracking.map', 'tracking.index', 'tracking.track') ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                            </svg>
                            <span>Peta Maritim Global</span>
                        </div>
                        <span class="text-[9px] font-black uppercase tracking-wider px-1.5 py-0.2 rounded-full bg-purple-500/20 text-purple-300 border border-purple-500/40">
                            GLOBAL
                        </span>
                    </a>

                    <!-- Vessels Fleet -->
                    <a href="{{ route('dashboard') }}#vessels-section"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 17l1.5-9h15L21 17M3 17l2 4h14l2-4M3 17h18M6 8V4h4v4m4-4h4v4"/>
                        </svg>
                        <span>Armada Kapal Global</span>
                    </a>

                    <!-- Shipments List -->
                    <a href="{{ route('dashboard') }}#shipments-table"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                        </svg>
                        <span>Semua Pengiriman Kargo</span>
                    </a>

                    <div class="text-[10px] uppercase font-bold tracking-wider text-slate-500 px-3 pt-4 pb-1">
                        Sistem & Kepatuhan
                    </div>

                    <!-- Exceptions -->
                    <a href="{{ route('dashboard') }}#exceptions-section"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <span>Pusat Gangguan</span>
                        </div>
                    </a>

                    <!-- Notifications -->
                    <a href="{{ route('notifications.index') }}"
                       class="flex items-center justify-between px-3 py-2.5 rounded-xl transition {{ request()->routeIs('notifications.*') ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span>Notifikasi Sistem</span>
                        </div>
                        @if($unreadNotificationsCount > 0)
                            <span class="px-2 py-0.5 rounded-full bg-rose-500 text-white text-[10px] font-black">
                                {{ $unreadNotificationsCount }}
                            </span>
                        @endif
                    </a>

                    <!-- Audit Logs -->
                    <a href="{{ route('audit.index') }}"
                       class="flex items-center gap-3 px-3 py-2.5 rounded-xl transition {{ request()->routeIs('audit.*') ? 'bg-purple-600 text-white shadow-md shadow-purple-600/30 font-bold' : 'text-slate-400 hover:text-white hover:bg-slate-800' }}">
                        <svg class="w-4 h-4 text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <span>Jejak Audit Sistem (Audit Trail)</span>
                    </a>
                @endif
            </nav>
        </div>

        <!-- User Profile & Multi-Role Switcher in Sidebar Bottom -->
        @php
            $currentUser = Auth::user();
            $roleName = $currentUser?->role ?? 'TAMU (GUEST)';
            $initials = $currentUser ? strtoupper(substr($currentUser->name, 0, 2)) : 'GS';
            $roleBadgeColor = match($currentUser?->role) {
                'ADMIN' => 'bg-purple-500/20 text-purple-300 border-purple-500/40',
                'OPERATOR' => 'bg-cyan-500/20 text-cyan-300 border-cyan-500/40',
                'CUSTOMER' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/40',
                default => 'bg-slate-700 text-slate-300 border-slate-600',
            };
        @endphp
        <div class="p-4 border-t border-slate-800 bg-slate-950/80 space-y-3">
            <div class="flex items-center justify-between gap-2">
                <div class="flex items-center gap-2.5 min-w-0">
                    <div class="w-8 h-8 rounded-xl bg-gradient-to-tr from-cyan-600 to-blue-600 flex items-center justify-center font-bold text-white text-[11px] shadow shrink-0">
                        {{ $initials }}
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-bold text-white truncate">{{ $currentUser?->name ?? 'Tamu (Guest)' }}</p>
                        <span class="inline-block text-[9px] font-extrabold uppercase px-1.5 py-0.2 rounded border {{ $roleBadgeColor }}">
                            {{ $roleName }}
                        </span>
                    </div>
                </div>

                @if($currentUser)
                    <form action="{{ route('logout') }}" method="POST" title="Keluar / Logout">
                        @csrf
                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-slate-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                            </svg>
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-[11px] font-bold text-cyan-400 hover:text-cyan-300">Login</a>
                @endif
            </div>

            <!-- Secure Account Switcher (Enforces Logout & Identity Verification) -->
            <div class="pt-2 border-t border-slate-800/80">
                <div class="flex items-center justify-between mb-1.5">
                    <span class="text-[9px] uppercase font-bold tracking-wider text-slate-400">Ganti Akun (Verifikasi):</span>
                    <span class="text-[8px] font-bold text-amber-300 bg-amber-950/80 border border-amber-800/80 px-1 py-0.2 rounded flex items-center gap-0.5">
                        <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Wajib Logout
                    </span>
                </div>
                <div class="grid grid-cols-3 gap-1">
                    <form action="{{ route('auth.switch_role', 'admin') }}" method="POST" onsubmit="return confirm('Untuk menjaga keamanan & privasi, sistem akan me-logout sesi ini dan meminta verifikasi kata sandi Administrator. Lanjutkan?');">
                        @csrf
                        <button type="submit" title="Logout & Verifikasi Login Administrator" class="w-full py-1 text-[10px] font-bold rounded-lg border {{ $currentUser?->role === 'ADMIN' ? 'bg-purple-900/60 border-purple-500 text-purple-200 ring-1 ring-purple-500/40' : 'bg-slate-800/60 border-slate-700/60 text-slate-400 hover:text-white hover:border-purple-500/60' }} transition">
                            🛡️ Admin
                        </button>
                    </form>
                    <form action="{{ route('auth.switch_role', 'operator') }}" method="POST" onsubmit="return confirm('Untuk menjaga keamanan & privasi, sistem akan me-logout sesi ini dan meminta verifikasi kata sandi Logistics Operator. Lanjutkan?');">
                        @csrf
                        <button type="submit" title="Logout & Verifikasi Login Logistics Operator" class="w-full py-1 text-[10px] font-bold rounded-lg border {{ $currentUser?->role === 'OPERATOR' ? 'bg-cyan-900/60 border-cyan-500 text-cyan-200 ring-1 ring-cyan-500/40' : 'bg-slate-800/60 border-slate-700/60 text-slate-400 hover:text-white hover:border-cyan-500/60' }} transition">
                            🚢 Opr
                        </button>
                    </form>
                    <form action="{{ route('auth.switch_role', 'customer') }}" method="POST" onsubmit="return confirm('Untuk menjaga keamanan & privasi, sistem akan me-logout sesi ini dan meminta verifikasi kata sandi Customer. Lanjutkan?');">
                        @csrf
                        <button type="submit" title="Logout & Verifikasi Login Customer" class="w-full py-1 text-[10px] font-bold rounded-lg border {{ $currentUser?->role === 'CUSTOMER' ? 'bg-emerald-900/60 border-emerald-500 text-emerald-200 ring-1 ring-emerald-500/40' : 'bg-slate-800/60 border-slate-700/60 text-slate-400 hover:text-white hover:border-emerald-500/60' }} transition">
                            👤 Cust
                        </button>
                    </form>
                </div>
                <p class="text-[9px] text-slate-400 mt-1.5 leading-tight">
                    Sistem otomatis logout & memverifikasi identitas pengguna saat beralih akun.
                </p>
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
            <div class="flex items-center gap-3">
                <!-- Status Telemetry AIS -->
                <div class="hidden lg:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-cyan-50 text-cyan-800 border border-cyan-200/80 text-xs font-semibold">
                    <span class="w-2 h-2 rounded-full bg-cyan-500 animate-pulse"></span>
                    <span>AIS Telemetry: <b>Simulated</b></span>
                </div>

                <!-- Status Marine Weather -->
                <div class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-xl bg-sky-50 text-sky-800 border border-sky-200/80 text-xs font-semibold">
                    <span>🌤️</span>
                    <span>Open-Meteo: <b>Connected</b></span>
                </div>

                <div class="h-6 w-px bg-slate-200"></div>

                <!-- Direct Quick Action: Track Map -->
                <a href="{{ route('tracking.map') }}"
                   class="inline-flex items-center gap-2 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 font-bold text-xs border border-blue-200 transition">
                    <span>🗺️</span>
                    <span>Peta Laut Global</span>
                </a>

                @if(Auth::check())
                    @php
                        $headerNotifications = \App\Models\Notification::where('user_id', Auth::id())->orderByDesc('created_at')->limit(5)->get();
                    @endphp
                    <!-- Interactive Notification Bell with Dropdown -->
                    <div class="relative">
                        <button type="button" onclick="toggleNotifDropdown()" id="notif-bell-btn" class="relative p-2 rounded-xl text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition" title="Notifikasi Saya">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            @if($unreadNotificationsCount > 0)
                                <span class="absolute top-1 right-1 w-4 h-4 rounded-full bg-rose-500 text-white text-[9px] font-black flex items-center justify-center animate-pulse">
                                    {{ $unreadNotificationsCount }}
                                </span>
                            @endif
                        </button>

                        <!-- Notification Dropdown Menu -->
                        <div id="notif-dropdown" class="hidden absolute right-0 mt-2 w-80 sm:w-96 bg-white border border-slate-200 rounded-2xl shadow-xl z-50 overflow-hidden">
                            <div class="p-3.5 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-xs text-slate-800">Notifikasi Sistem</span>
                                    @if($unreadNotificationsCount > 0)
                                        <span class="px-1.5 py-0.2 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold">
                                            {{ $unreadNotificationsCount }} baru
                                        </span>
                                    @endif
                                </div>
                                @if($unreadNotificationsCount > 0)
                                    <form action="{{ route('notifications.read_all') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-[11px] font-bold text-blue-600 hover:text-blue-800">
                                            Tandai semua dibaca
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="divide-y divide-slate-100 max-h-72 overflow-y-auto">
                                @forelse($headerNotifications as $hNotif)
                                    <div class="p-3 hover:bg-slate-50 transition flex items-start gap-2.5 {{ $hNotif->read_at === null ? 'bg-blue-50/40' : '' }}">
                                        <span class="text-base shrink-0 mt-0.5">
                                            {{ $hNotif->type === 'WARNING' ? '⚠️' : ($hNotif->type === 'ERROR' ? '🚨' : ($hNotif->type === 'SUCCESS' ? '✅' : 'ℹ️')) }}
                                        </span>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-xs font-bold text-slate-800 truncate">{{ $hNotif->title }}</p>
                                            <p class="text-[11px] text-slate-500 line-clamp-2 mt-0.5">{{ $hNotif->message }}</p>
                                            <span class="text-[10px] text-slate-400 mt-1 block">{{ $hNotif->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-4 text-center text-xs text-slate-400">
                                        Tidak ada notifikasi baru.
                                    </div>
                                @endforelse
                            </div>

                            <a href="{{ route('notifications.index') }}" class="block p-3 text-center bg-slate-50 hover:bg-slate-100 text-xs font-bold text-blue-600 border-t border-slate-100 transition">
                                Buka Pusat Notifikasi Lengkap &rarr;
                            </a>
                        </div>
                    </div>

                    <!-- User Info & Logout Button -->
                    <div class="flex items-center gap-2">
                        <div class="hidden md:flex flex-col text-right">
                            <span class="text-xs font-bold text-slate-800 leading-tight">{{ Auth::user()->name }}</span>
                            <span class="text-[10px] font-mono font-bold text-slate-400 uppercase">{{ Auth::user()->role }}</span>
                        </div>
                        <form action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit" class="px-3 py-1.5 rounded-xl bg-slate-100 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 text-xs font-bold text-slate-600 transition flex items-center gap-1.5" title="Keluar / Ganti Akun">
                                <span>🚪</span>
                                <span>Logout</span>
                            </button>
                        </form>
                    </div>
                @else
                    <a href="{{ route('login') }}" class="px-3.5 py-1.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold transition flex items-center gap-1.5">
                        <span>🔑</span>
                        <span>Login</span>
                    </a>
                @endif
            </div>
        </header>

        <!-- Dynamic Main Scrollable Workspace -->
        <main class="flex-1 overflow-y-auto p-6 lg:p-8 space-y-8 bg-slate-50/80">
            @if(session('info'))
                <div class="p-3.5 rounded-2xl bg-blue-50 border border-blue-200 text-blue-800 text-xs font-bold flex items-center gap-2">
                    <span>ℹ️</span>
                    <span>{{ session('info') }}</span>
                </div>
            @endif
            @if(session('success'))
                <div class="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif
            @if(session('error'))
                <div class="p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-800 text-xs font-bold flex items-center gap-2">
                    <span>⚠️</span>
                    <span>{{ session('error') }}</span>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <!-- Interactive Dropdown Script -->
    <script>
        function toggleNotifDropdown() {
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown) {
                dropdown.classList.toggle('hidden');
            }
        }
        document.addEventListener('click', function(e) {
            const btn = document.getElementById('notif-bell-btn');
            const dropdown = document.getElementById('notif-dropdown');
            if (dropdown && btn && !btn.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.add('hidden');
            }
        });
    </script>
</body>
</html>
