<?php declare(strict_types = 1);

namespace App\Domain\Admin\Http\Middleware;

use App\Domain\Admin\Models\Admin;
use App\Domain\Customer\Models\AuthLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAccess
{
    /**
     * HTTP methods that should be logged.
     *
     * @var list<string>
     */
    protected array $loggableMethods = ['POST', 'PUT', 'PATCH', 'DELETE'];

    /**
     * Handle an incoming request.
     *
     * Logs admin actions for auditing purposes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Capture admin before the request is processed (in case of logout)
        $admin = auth('admin')->user();

        $response = $next($request);

        // Log only for methods that modify data
        if (in_array($request->method(), $this->loggableMethods) && $admin instanceof Admin) {
            $this->logAdminAction($request, $admin);
        }

        return $response;
    }

    /**
     * Log the admin action.
     */
    protected function logAdminAction(Request $request, Admin $admin): void
    {
        AuthLog::create([
            'authenticatable_type' => Admin::class,
            'authenticatable_id'   => $admin->id,
            'email'                => $admin->email,
            'event'                => 'admin_action',
            'ip_address'           => $request->ip(),
            'user_agent'           => $request->userAgent(),
            'metadata'             => [
                'method' => $request->method(),
                'path'   => $request->path(),
                'route'  => $request->route()?->getName(),
            ],
        ]);
    }
}
