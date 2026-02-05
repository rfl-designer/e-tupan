<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Listeners;

use App\Domain\Cart\Services\CartMergeService;
use Illuminate\Auth\Events\Login;

class MergeCartOnLogin
{
    public function __construct(
        protected CartMergeService $mergeService,
    ) {
    }

    /**
     * Handle the login event.
     */
    public function handle(Login $event): void
    {
        // Only merge for web guard (customers)
        if ($event->guard !== 'web') {
            return;
        }

        $sessionId = session()->getId();

        if ($sessionId === '') {
            return;
        }

        $this->mergeService->mergeOnLogin($event->user, $sessionId);
    }
}
