<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AdminSessionTimeout
{
    /**
     * Session timeout in minutes.
     */
    protected int $timeout = 30;

    /**
     * Handle an incoming request.
     *
     * Checks if the admin session has been inactive for too long and logs them out if so.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $lastActivity = session('admin_last_activity');

        if ($lastActivity && $this->hasTimedOut($lastActivity)) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('warning', 'Sua sessão expirou por inatividade.');
        }

        session(['admin_last_activity' => now()]);

        return $next($request);
    }

    /**
     * Check if the session has timed out.
     */
    protected function hasTimedOut(mixed $lastActivity): bool
    {
        $lastActivityTime = $lastActivity instanceof Carbon
            ? $lastActivity
            : Carbon::parse($lastActivity);

        return $lastActivityTime->diffInMinutes(now()) >= $this->timeout;
    }
}
