<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Jobs;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\{DB, Log};

class CleanOldCartsJob implements ShouldQueue
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
        $abandonedCount = $this->cleanOldAbandonedCarts();
        $emptyCount     = $this->cleanOldEmptyCarts();

        if ($abandonedCount > 0 || $emptyCount > 0) {
            Log::info("CleanOldCartsJob: Removed {$abandonedCount} abandoned carts and {$emptyCount} empty carts.");
        }
    }

    /**
     * Clean abandoned carts older than retention period.
     */
    protected function cleanOldAbandonedCarts(): int
    {
        $retentionDays = (int) config('cart.abandoned_retention_days', 90);
        $threshold     = now()->subDays($retentionDays);

        $cartIds = Cart::query()
            ->where('status', CartStatus::Abandoned)
            ->where('abandoned_at', '<=', $threshold)
            ->pluck('id');

        if ($cartIds->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($cartIds) {
            // Release stock reservations
            StockReservation::whereIn('cart_id', $cartIds)->delete();

            // Delete cart items
            DB::table('cart_items')->whereIn('cart_id', $cartIds)->delete();

            // Delete carts
            Cart::whereIn('id', $cartIds)->delete();
        });

        return $cartIds->count();
    }

    /**
     * Clean empty carts older than retention period.
     */
    protected function cleanOldEmptyCarts(): int
    {
        $retentionDays = (int) config('cart.empty_retention_days', 7);
        $threshold     = now()->subDays($retentionDays);

        $cartIds = Cart::query()
            ->where('status', '!=', CartStatus::Converted)
            ->where('last_activity_at', '<=', $threshold)
            ->whereDoesntHave('items')
            ->pluck('id');

        if ($cartIds->isEmpty()) {
            return 0;
        }

        DB::transaction(function () use ($cartIds) {
            // Release any stock reservations (shouldn't be any for empty carts)
            StockReservation::whereIn('cart_id', $cartIds)->delete();

            // Delete carts
            Cart::whereIn('id', $cartIds)->delete();
        });

        return $cartIds->count();
    }
}
