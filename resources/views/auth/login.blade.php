<!DOCTYPE html>
<html lang="id" class="h-full bg-slate-950">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - CargoTrace Maritime Visibility</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        body { font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="h-full flex items-center justify-center p-4 bg-gradient-to-br from-slate-950 via-slate-900 to-blue-950 text-slate-100">

    <div class="w-full max-w-4xl grid grid-cols-1 md:grid-cols-12 bg-slate-900/90 border border-slate-800 rounded-3xl shadow-2xl overflow-hidden backdrop-blur-xl">
        
        <!-- Left Column: Branding & Role Selection -->
        <div class="md:col-span-5 p-8 bg-gradient-to-b from-slate-900 to-slate-950 flex flex-col justify-between border-b md:border-b-0 md:border-r border-slate-800">
            <div>
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-blue-600 to-cyan-500 flex items-center justify-center text-white shadow-lg shadow-blue-500/25">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v18m0-18c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm-8 11a8 8 0 0016 0M4 14h2m12 0h2"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-xl font-black text-white tracking-tight">Cargo<span class="text-cyan-400">Trace</span></h1>
                        <span class="text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded bg-blue-900/60 text-cyan-300 border border-blue-700/50">Maritime Visibility</span>
                    </div>
                </div>

                <h2 class="text-lg font-bold text-white mb-2">Portal Otentikasi Multi-Role</h2>
                <p class="text-xs text-slate-400 leading-relaxed mb-6">
                    Sistem CargoTrace mengimplementasikan Role-Based Access Control (RBAC) dengan isolasi data ketat untuk 3 role:
                </p>

                <!-- Quick Switch Buttons for Demo -->
                <div class="space-y-2.5">
                    <p class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Pilih Akun Demo (1-Click Login):</p>
                    
                    <!-- Admin Card -->
                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="admin@cargotrace.com">
                        <input type="hidden" name="password" value="password">
                        <button type="submit" class="w-full text-left p-3 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700 hover:border-blue-500 transition group flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-purple-500/20 text-purple-300 border border-purple-500/40 flex items-center justify-center text-sm font-bold">🛡️</span>
                                <div>
                                    <div class="text-xs font-bold text-white group-hover:text-cyan-300 transition">Administrator</div>
                                    <div class="text-[10px] text-slate-400 font-mono">admin@cargotrace.com</div>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-purple-400 px-2 py-0.5 rounded bg-purple-950/60 border border-purple-800">Masuk &rarr;</span>
                        </button>
                    </form>

                    <!-- Operator Card -->
                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="operator@cargotrace.com">
                        <input type="hidden" name="password" value="password">
                        <button type="submit" class="w-full text-left p-3 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700 hover:border-cyan-500 transition group flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-cyan-500/20 text-cyan-300 border border-cyan-500/40 flex items-center justify-center text-sm font-bold">🚢</span>
                                <div>
                                    <div class="text-xs font-bold text-white group-hover:text-cyan-300 transition">Logistics Operator</div>
                                    <div class="text-[10px] text-slate-400 font-mono">operator@cargotrace.com</div>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-cyan-400 px-2 py-0.5 rounded bg-cyan-950/60 border border-cyan-800">Masuk &rarr;</span>
                        </button>
                    </form>

                    <!-- Customer Card -->
                    <form action="{{ route('login.post') }}" method="POST">
                        @csrf
                        <input type="hidden" name="email" value="customer@cargotrace.com">
                        <input type="hidden" name="password" value="password">
                        <button type="submit" class="w-full text-left p-3 rounded-2xl bg-slate-800/80 hover:bg-slate-800 border border-slate-700 hover:border-emerald-500 transition group flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <span class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-300 border border-emerald-500/40 flex items-center justify-center text-sm font-bold">👤</span>
                                <div>
                                    <div class="text-xs font-bold text-white group-hover:text-emerald-300 transition">Customer (Pelanggan)</div>
                                    <div class="text-[10px] text-slate-400 font-mono">customer@cargotrace.com</div>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold text-emerald-400 px-2 py-0.5 rounded bg-emerald-950/60 border border-emerald-800">Masuk &rarr;</span>
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-800/80 text-[10px] text-slate-500 text-center">
                Kata sandi default semua akun demo: <span class="font-mono text-slate-300 bg-slate-800 px-1.5 py-0.5 rounded">password</span>
            </div>
        </div>

        <!-- Right Column: Manual Login Form -->
        <div class="md:col-span-7 p-8 md:p-10 flex flex-col justify-center">
            <div class="mb-6">
                <h3 class="text-xl font-black text-white tracking-tight">Masuk ke Akun Anda</h3>
                <p class="text-xs text-slate-400 mt-1">Silakan masukkan email dan kata sandi Anda untuk mengakses dashboard operasional maritim.</p>
            </div>

            <!-- Flash Error / Success Notifications -->
            @if(session('success'))
                <div class="mb-4 p-3 rounded-xl bg-emerald-950/70 border border-emerald-800 text-emerald-300 text-xs flex items-center gap-2">
                    <span>✅</span>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-3 rounded-xl bg-rose-950/70 border border-rose-800 text-rose-300 text-xs space-y-1">
                    <div class="font-bold flex items-center gap-1">
                        <span>⚠️</span>
                        <span>Gagal Masuk:</span>
                    </div>
                    @foreach($errors->all() as $err)
                        <div class="pl-5">&bull; {{ $err }}</div>
                    @endforeach
                </div>
            @endif

            <!-- Standard Form -->
            <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label for="email" class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Alamat Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', 'operator@cargotrace.com') }}" required autofocus
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition outline-none"
                           placeholder="nama@cargotrace.com">
                </div>

                <div>
                    <label for="password" class="block text-xs font-bold text-slate-300 mb-1.5 uppercase tracking-wider">Kata Sandi</label>
                    <input type="password" id="password" name="password" value="password" required
                           class="w-full px-4 py-2.5 rounded-xl bg-slate-950 border border-slate-800 text-slate-100 text-xs focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 transition outline-none"
                           placeholder="••••••••">
                </div>

                <div class="flex items-center justify-between text-xs pt-1">
                    <label class="flex items-center gap-2 text-slate-400 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-700 bg-slate-950 text-cyan-600 focus:ring-cyan-500">
                        <span>Ingat saya di perangkat ini</span>
                    </label>
                    <a href="{{ route('tracking.map') }}" class="text-cyan-400 hover:text-cyan-300 font-bold transition">
                        Buka Peta sebagai Tamu &rarr;
                    </a>
                </div>

                <div class="pt-3">
                    <button type="submit" class="w-full py-3 px-4 rounded-xl bg-gradient-to-r from-blue-600 to-cyan-600 hover:from-blue-500 hover:to-cyan-500 text-white font-black text-xs uppercase tracking-wider shadow-lg shadow-blue-600/30 transition transform active:scale-[0.99]">
                        Masuk ke CargoTrace
                    </button>
                </div>
            </form>

            <div class="mt-8 pt-4 border-t border-slate-800/80 flex items-center justify-between text-[11px] text-slate-500">
                <span>CargoTrace Platform &copy; 2026</span>
                <a href="{{ route('dashboard') }}" class="hover:text-slate-300 transition">Kembali ke Dashboard &rarr;</a>
            </div>
        </div>

    </div>

</body>
</html>
