<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Observers;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;

class CartObserver
{
    /**
     * Handle the Cart "updating" event.
     */
    public function updating(Cart $cart): void
    {
        // Don't update last_activity_at when status is changing to abandoned or converted
        if ($cart->isDirty('status')) {
            $newStatus = $cart->status;

            if ($newStatus === CartStatus::Abandoned || $newStatus === CartStatus::Converted) {
                return;
            }
        }

        // Update last_activity_at on any other change
        $cart->last_activity_at = now();
    }
}
