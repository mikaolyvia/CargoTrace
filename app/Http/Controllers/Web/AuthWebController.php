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
    public function showLoginForm(): View|RedirectResponse
    {
        return view('auth.login', [
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
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'))
                ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
        }

        return back()->withErrors([
            'email' => 'Email atau kata sandi yang Anda masukkan tidak sesuai.',
        ])->onlyInput('email');
    }

    /**
     * Quick switch between demo roles (Admin, Operator, Customer).
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

        Auth::login($targetUser);
        $request->session()->regenerate();

        return redirect()->back()
            ->with('success', "Beralih akun ke: {$targetUser->name} ({$targetUser->role})");
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
