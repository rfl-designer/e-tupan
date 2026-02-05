<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminIsMaster
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth('admin')->user()?->isMaster()) {
            abort(403, 'Acesso restrito a administradores master.');
        }

        return $next($request);
    }
}
