<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Events;

use App\Domain\Cart\Models\Cart;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemRemovedFromCart
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Cart $cart,
        public readonly int $productId,
        public readonly ?int $variantId,
        public readonly int $quantity,
    ) {
    }
}
