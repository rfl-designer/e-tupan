<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Domain\Checkout\Models\Order;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AdminLayoutServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        View::composer('admin.layouts.app', function ($view): void {
            $pendingOrdersCount = null;

            // Only count if authenticated as admin
            if (auth('admin')->check()) {
                $pendingOrdersCount = Order::query()
                    ->where('status', 'pending')
                    ->count();

                // Only show badge if there are pending orders
                if ($pendingOrdersCount === 0) {
                    $pendingOrdersCount = null;
                }
            }

            $view->with('pendingOrdersCount', $pendingOrdersCount);
        });
    }
}
