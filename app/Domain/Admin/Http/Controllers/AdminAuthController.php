<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Controllers;

use App\Domain\Admin\Http\Requests\AdminLoginRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Display the admin login view.
     */
    public function showLogin(): View
    {
        return view('admin.auth.login');
    }

    /**
     * Handle an incoming admin authentication request.
     */
    public function login(AdminLoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $remember    = $request->boolean('remember');

        if (!Auth::guard('admin')->attempt($credentials, $remember)) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        /** @var \App\Domain\Admin\Models\Admin $admin */
        $admin = Auth::guard('admin')->user();

        if (!$admin->is_active) {
            Auth::guard('admin')->logout();

            throw ValidationException::withMessages([
                'email' => __('Your account has been deactivated.'),
            ]);
        }

        $request->session()->regenerate();

        $admin->update([
            'last_login_at' => now(),
            'last_login_ip' => $request->ip(),
        ]);

        return redirect()->intended(route('admin.dashboard'));
    }

    /**
     * Destroy an authenticated admin session.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
