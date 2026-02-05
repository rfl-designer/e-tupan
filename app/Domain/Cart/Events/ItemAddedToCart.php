<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Events;

use App\Domain\Cart\Models\{Cart, CartItem};
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ItemAddedToCart
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public readonly Cart $cart,
        public readonly CartItem $item,
    ) {
    }
}
