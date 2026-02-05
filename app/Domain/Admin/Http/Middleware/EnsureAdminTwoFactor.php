<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminTwoFactor
{
    /**
     * Handle an incoming request.
     *
     * Ensures that the admin has two-factor authentication configured and confirmed.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $admin = auth('admin')->user();

        if (!$admin) {
            return redirect()->route('admin.login');
        }

        // If 2FA is not configured, redirect to setup
        if (!$admin->two_factor_secret) {
            if (!$request->routeIs('admin.two-factor.*')) {
                return redirect()->route('admin.two-factor.setup');
            }
        }

        // If 2FA is configured but not confirmed in this session
        if ($admin->two_factor_secret && !session('admin_two_factor_confirmed')) {
            if (!$request->routeIs('admin.two-factor.challenge*')) {
                return redirect()->route('admin.two-factor.challenge');
            }
        }

        return $next($request);
    }
}
