<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthWebController extends Controller
{
    /**
     * Show the application login form.
     */
    public function showLoginForm(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard')
                ->with('info', 'Anda saat ini sedang login sebagai ' . Auth::user()->name . ' (' . Auth::user()->role . '). Silakan logout terlebih dahulu untuk beralih atau masuk sebagai pengguna lain.');
        }

        return view('auth.login', [
            'prefilledEmail' => $request->query('email', ''),
            'users' => [
                'admin' => User::where('role', 'ADMIN')->first(),
                'operator' => User::where('role', 'OPERATOR')->first(),
                'customer' => User::where('role', 'CUSTOMER')->first(),
            ],
        ]);
    }

    /**
     * Handle an authentication attempt.
     */
    public function login(Request $request): RedirectResponse
    {
        // Jika sebelumnya masih ada sesi aktif, bersihkan terlebih dahulu demi keamanan
        if (Auth::check()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Verifikasi berhasil. Selamat datang kembali, ' . Auth::user()->name . ' (' . Auth::user()->role . ')!');
        }

        return back()->withErrors([
            'email' => 'Verifikasi gagal: Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Beralih peran / pengguna secara aman dengan mewajibkan logout terlebih dahulu
     * agar kredensial pengguna baru diverifikasi melalui form login.
     */
    public function switchRole(Request $request, string $role): RedirectResponse
    {
        $role = strtoupper($role);
        if (! in_array($role, ['ADMIN', 'OPERATOR', 'CUSTOMER'])) {
            return back()->with('error', 'Peran tidak valid.');
        }

        $targetUser = User::where('role', $role)->first();
        if (! $targetUser) {
            return back()->with('error', "Pengguna dengan peran {$role} tidak ditemukan.");
        }

        // Wajib logout sesi saat ini agar sistem memverifikasi identitas pengguna baru
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login', ['email' => $targetUser->email])
            ->with('info', "Sesi sebelumnya telah keluar. Silakan masukkan kata sandi untuk memverifikasi akun {$targetUser->name} ({$targetUser->role}) demi menjaga privasi dan keamanan.");
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem CargoTrace.');
    }
}
