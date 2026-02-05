<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Jobs;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class MarkAbandonedCartsJob implements ShouldQueue
{
    use Queueable;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $inactivityHours = (int) config('cart.abandonment_hours', 24);
        $threshold       = now()->subHours($inactivityHours);

        $count = Cart::query()
            ->where('status', CartStatus::Active)
            ->where('last_activity_at', '<=', $threshold)
            ->whereHas('items')
            ->update([
                'status'       => CartStatus::Abandoned,
                'abandoned_at' => now(),
            ]);

        if ($count > 0) {
            Log::info("MarkAbandonedCartsJob: Marked {$count} carts as abandoned.");
        }
    }
}
