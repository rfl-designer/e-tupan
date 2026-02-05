<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Events;

use App\Domain\Checkout\Models\{Order, Payment};
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderPaid
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Order $order,
        public readonly Payment $payment,
    ) {
    }
}
