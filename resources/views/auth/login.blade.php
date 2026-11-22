<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk ke CargoTrace - Portal Logistik & Pelabuhan Maritim</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
        }

        body {
            background-color: #050b14;
            background-image: 
                radial-gradient(circle at 15% 50%, rgba(6, 182, 212, 0.15), transparent 25%),
                radial-gradient(circle at 85% 30%, rgba(59, 130, 246, 0.18), transparent 30%),
                linear-gradient(135deg, rgba(5, 11, 20, 0.88) 0%, rgba(10, 25, 47, 0.82) 50%, rgba(2, 6, 23, 0.94) 100%),
                url('https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&w=2400&q=85');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            background-attachment: fixed;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #f1f5f9;
            padding: 1.5rem;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle animated radar / marine grid lines overlay */
        body::before {
            content: "";
            position: absolute;
            inset: 0;
            background-image: 
                linear-gradient(rgba(255, 255, 255, 0.02) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255, 255, 255, 0.02) 1px, transparent 1px);
            background-size: 60px 60px;
            pointer-events: none;
            z-index: 1;
        }

        .login-wrapper {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 980px;
            margin: auto;
        }

        .port-card {
            background: rgba(13, 23, 42, 0.78);
            backdrop-filter: blur(24px);
            -webkit-backdrop-filter: blur(24px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 28px;
            box-shadow: 0 25px 60px -15px rgba(0, 0, 0, 0.7),
                        0 0 50px rgba(6, 182, 212, 0.1);
            overflow: hidden;
        }

        .brand-font {
            font-family: 'Space Grotesk', sans-serif;
        }

        .input-port {
            background: rgba(15, 23, 42, 0.75);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #f8fafc;
            transition: all 0.2s ease;
        }

        .input-port:focus {
            background: rgba(15, 23, 42, 0.95);
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.25);
            outline: none;
        }

        .btn-port-primary {
            background: linear-gradient(135deg, #0284c7 0%, #06b6d4 100%);
            color: #ffffff;
            font-weight: 700;
            border-radius: 14px;
            transition: all 0.25s ease;
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.4);
        }

        .btn-port-primary:hover {
            background: linear-gradient(135deg, #0369a1 0%, #0891b2 100%);
            transform: translateY(-1px);
            box-shadow: 0 15px 30px -5px rgba(6, 182, 212, 0.5);
        }

        .btn-port-primary:active {
            transform: translateY(0);
        }

        .role-chip {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .role-chip:hover {
            background: rgba(51, 65, 85, 0.9);
            border-color: #38bdf8;
            transform: translateY(-1px);
        }

        .role-chip.active {
            background: rgba(6, 182, 212, 0.2);
            border-color: #06b6d4;
            color: #38bdf8;
        }

        .pulse-beacon {
            animation: beacon 2.5s infinite;
        }

        @keyframes beacon {
            0% { transform: scale(0.95); opacity: 0.8; }
            50% { transform: scale(1.05); opacity: 1; }
            100% { transform: scale(0.95); opacity: 0.8; }
        }
    </style>
</head>
<body>

    <div class="login-wrapper">
        <div class="port-card grid grid-cols-1 lg:grid-cols-12">
            
            <!-- Left Panel: Maritime Port Branding & Atmosphere -->
            <div class="lg:col-span-5 p-8 sm:p-10 bg-gradient-to-br from-slate-950/90 via-slate-900/80 to-blue-950/60 flex flex-col justify-between border-b lg:border-b-0 lg:border-r border-slate-800/80 relative">
                <div>
                    <!-- Top Port Badge -->
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-cyan-500 to-blue-600 flex items-center justify-center text-white shadow-lg shadow-cyan-500/30">
                            <!-- Container Ship / Anchor SVG Icon -->
                            <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m0-18c-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4-1.79-4-4-4zm-8 11a8 8 0 0016 0M4 14h2m12 0h2" />
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h1 class="text-2xl font-black text-white tracking-tight brand-font">Cargo<span class="text-cyan-400">Trace</span></h1>
                                <span class="w-2 h-2 rounded-full bg-emerald-400 pulse-beacon" title="Sistem Aktif"></span>
                            </div>
                            <p class="text-[11px] font-semibold text-cyan-300 uppercase tracking-wider">Maritime Port Visibility</p>
                        </div>
                    </div>

                    <!-- Port Terminal Narrative -->
                    <div class="space-y-4">
                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-cyan-950/70 border border-cyan-800/60 text-cyan-300 text-xs font-semibold">
                            <span>⚓</span>
                            <span>Pelabuhan & Terminal Kontainer</span>
                        </div>

                        <h2 class="text-2xl sm:text-3xl font-extrabold text-white leading-tight">
                            Portal Visibilitas Logistik Maritim Global.
                        </h2>

                        <p class="text-xs sm:text-sm text-slate-300 leading-relaxed">
                            Pantau pergerakan kapal kargo (AIS radar), status kontainer pelabuhan Tanjung Priok & Singapura, serta manajemen manifes logistik secara real-time.
                        </p>
                    </div>

                    <!-- Key Metrics Highlights -->
                    <div class="grid grid-cols-2 gap-3 mt-8">
                        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                            <div class="text-xs text-slate-400 font-medium">Terminal Pantauan</div>
                            <div class="text-lg font-bold text-white mt-0.5">8 Pelabuhan</div>
                            <div class="text-[10px] text-cyan-400 mt-0.5">AIS Satelit & Terestrial</div>
                        </div>
                        <div class="p-3.5 rounded-2xl bg-slate-900/60 border border-slate-800">
                            <div class="text-xs text-slate-400 font-medium">Keamanan Sesi</div>
                            <div class="text-lg font-bold text-emerald-400 mt-0.5">Verifikasi SSL</div>
                            <div class="text-[10px] text-slate-400 mt-0.5">RBAC Terisolasi Ketat</div>
                        </div>
                    </div>
                </div>

                <!-- Footer note in left panel -->
                <div class="mt-8 pt-4 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                        </svg>
                        Enkripsi End-to-End
                    </span>
                    <a href="{{ route('tracking.map') }}" class="text-cyan-400 hover:text-cyan-300 font-semibold transition">
                        Buka Peta Tamu &rarr;
                    </a>
                </div>
            </div>

            <!-- Right Panel: Clean, Standard & Easy Login Form -->
            <div class="lg:col-span-7 p-8 sm:p-12 flex flex-col justify-center bg-slate-900/60">
                <div class="max-w-md w-full mx-auto">
                    
                    <!-- Header -->
                    <div class="mb-6">
                        <h3 class="text-2xl font-bold text-white tracking-tight">Selamat Datang Kembali</h3>
                        <p class="text-xs sm:text-sm text-slate-400 mt-1.5">
                            Silakan masukkan email dan kata sandi Anda untuk mengakses akun.
                        </p>
                    </div>

                    <!-- Flash Notifications (Clean Alerts) -->
                    @if(session('info'))
                        <div class="mb-5 p-3.5 rounded-xl bg-blue-950/80 border border-blue-600/60 text-blue-200 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">ℹ️</span>
                            <span class="leading-relaxed">{{ session('info') }}</span>
                        </div>
                    @endif

                    @if(session('success'))
                        <div class="mb-5 p-3.5 rounded-xl bg-emerald-950/80 border border-emerald-600/60 text-emerald-200 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">✅</span>
                            <span class="leading-relaxed">{{ session('success') }}</span>
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="mb-5 p-3.5 rounded-xl bg-rose-950/80 border border-rose-600/60 text-rose-200 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">⚠️</span>
                            <span class="leading-relaxed">{{ session('error') }}</span>
                        </div>
                    @endif

                    @if($errors->any())
                        <div class="mb-5 p-3.5 rounded-xl bg-rose-950/80 border border-rose-600/60 text-rose-200 text-xs flex items-start gap-2.5">
                            <span class="text-base shrink-0">⚠️</span>
                            <div>
                                <div class="font-bold">Gagal Masuk:</div>
                                @foreach($errors->all() as $err)
                                    <div>&bull; {{ $err }}</div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Quick Demo Role Selector (Non-Intrusive Pills) -->
                    <div class="mb-6 p-3 rounded-2xl bg-slate-950/60 border border-slate-800/80">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Isi Cepat Akun Demo:</span>
                            <span class="text-[10px] text-slate-500 font-mono">Password: password</span>
                        </div>
                        <div class="grid grid-cols-3 gap-2">
                            <button type="button" onclick="fillAccount('admin@cargotrace.com', 'password', this)" 
                                    class="role-chip py-2 px-2 rounded-xl text-center text-xs font-semibold text-slate-300">
                                🛡️ Admin
                            </button>
                            <button type="button" onclick="fillAccount('operator@cargotrace.com', 'password', this)" 
                                    class="role-chip py-2 px-2 rounded-xl text-center text-xs font-semibold text-slate-300">
                                🚢 Operator
                            </button>
                            <button type="button" onclick="fillAccount('customer@cargotrace.com', 'password', this)" 
                                    class="role-chip py-2 px-2 rounded-xl text-center text-xs font-semibold text-slate-300">
                                👤 Customer
                            </button>
                        </div>
                    </div>

                    <!-- Main Standard Login Form -->
                    <form action="{{ route('login.post') }}" method="POST" class="space-y-4">
                        @csrf
                        
                        <!-- Email Input -->
                        <div>
                            <label for="email" class="block text-xs font-bold text-slate-300 uppercase tracking-wider mb-2">
                                Alamat Email
                            </label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.206" />
                                    </svg>
                                </div>
                                <input type="email" id="email" name="email" 
                                       value="{{ old('email', $prefilledEmail ?? '') }}" 
                                       required autofocus
                                       placeholder="nama@cargotrace.com"
                                       class="input-port w-full pl-10 pr-4 py-3 rounded-xl text-sm placeholder-slate-500">
                            </div>
                        </div>

                        <!-- Password Input with Show/Hide Toggle -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label for="password" class="block text-xs font-bold text-slate-300 uppercase tracking-wider">
                                    Kata Sandi
                                </label>
                                <span class="text-[11px] text-slate-400">Wajib diverifikasi</span>
                            </div>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                </div>
                                <input type="password" id="password" name="password" 
                                       required autocomplete="current-password"
                                       placeholder="••••••••"
                                       class="input-port w-full pl-10 pr-10 py-3 rounded-xl text-sm placeholder-slate-500">
                                <button type="button" onclick="togglePasswordVisibility()" 
                                        class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-200 transition">
                                    <svg id="eye-icon" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Remember Me & Extra Info -->
                        <div class="flex items-center justify-between pt-1">
                            <label class="flex items-center gap-2 text-xs text-slate-300 cursor-pointer select-none">
                                <input type="checkbox" name="remember" class="w-4 h-4 rounded border-slate-700 bg-slate-900 text-cyan-500 focus:ring-cyan-500">
                                <span>Ingat saya di perangkat ini</span>
                            </label>
                            <span class="text-xs text-slate-400">Verifikasi Terproteksi</span>
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-3">
                            <button type="submit" class="btn-port-primary w-full py-3.5 px-6 flex items-center justify-center gap-2 text-sm uppercase tracking-wider">
                                <span>Masuk ke CargoTrace</span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </button>
                        </div>
                    </form>

                    <!-- Back to Dashboard / Footer -->
                    <div class="mt-8 pt-6 border-t border-slate-800/80 flex items-center justify-between text-xs text-slate-400">
                        <span>CargoTrace Platform &copy; 2026</span>
                        <a href="{{ route('dashboard') }}" class="hover:text-cyan-300 transition font-medium">
                            Kembali ke Dashboard &rarr;
                        </a>
                    </div>

                </div>
            </div>

        </div>
    </div>

    <!-- Interactive Script for Quick Fill & Eye Toggle -->
    <script>
        function fillAccount(email, password, element) {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            if (emailInput) {
                emailInput.value = email;
            }
            if (passwordInput) {
                passwordInput.value = password;
                passwordInput.focus();
            }

            document.querySelectorAll('.role-chip').forEach(chip => chip.classList.remove('active'));
            if (element) {
                element.classList.add('active');
            }
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');
            if (!passwordInput) return;

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l18 18" />
                `;
            } else {
                passwordInput.type = 'password';
                eyeIcon.innerHTML = `
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                `;
            }
        }
    </script>
</body>
</html>
