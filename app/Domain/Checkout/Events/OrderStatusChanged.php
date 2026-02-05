<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Events;

use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $oldStatus,
        public readonly OrderStatus $newStatus,
    ) {}
}
