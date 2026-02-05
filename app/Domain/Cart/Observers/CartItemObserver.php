<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Observers;

use App\Domain\Cart\Models\CartItem;

class CartItemObserver
{
    /**
     * Handle the CartItem "created" event.
     */
    public function created(CartItem $cartItem): void
    {
        $this->touchCartActivity($cartItem);
    }

    /**
     * Handle the CartItem "updated" event.
     */
    public function updated(CartItem $cartItem): void
    {
        $this->touchCartActivity($cartItem);
    }

    /**
     * Handle the CartItem "deleted" event.
     */
    public function deleted(CartItem $cartItem): void
    {
        $this->touchCartActivity($cartItem);
    }

    /**
     * Touch the cart's last_activity_at timestamp.
     */
    protected function touchCartActivity(CartItem $cartItem): void
    {
        if ($cartItem->cart !== null) {
            $cartItem->cart->touchLastActivity();
        }
    }
}
