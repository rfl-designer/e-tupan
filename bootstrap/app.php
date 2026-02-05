<?php declare(strict_types = 1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\{Exceptions, Middleware};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withEvents(discover: [
        __DIR__.'/../app/Domain/*/Listeners',
        __DIR__.'/../app/Listeners',
    ])
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function (): void {
            Route::middleware('web')
                ->group(base_path('routes/admin.php'));

            // Webhook routes without CSRF protection
            Route::middleware('throttle:60,1')
                ->group(base_path('routes/webhooks.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.login');
            }

            return route('login');
        });

        $middleware->redirectUsersTo(function (Request $request) {
            if ($request->is('admin', 'admin/*')) {
                return route('admin.dashboard');
            }

            return route('dashboard');
        });

        $middleware->alias([
            'admin.master'  => \App\Domain\Admin\Http\Middleware\EnsureAdminIsMaster::class,
            'admin.2fa'     => \App\Domain\Admin\Http\Middleware\EnsureAdminTwoFactor::class,
            'admin.timeout' => \App\Domain\Admin\Http\Middleware\AdminSessionTimeout::class,
            'admin.log'     => \App\Domain\Admin\Http\Middleware\LogAdminAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
