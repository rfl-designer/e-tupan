<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Listeners;

use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Notifications\OrderConfirmationNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderConfirmationEmail implements ShouldQueue
{
    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order        = $event->order;
        $notification = new OrderConfirmationNotification($order);

        /** @var User|null $user */
        $user = $order->user;

        if ($user !== null) {
            $user->notify($notification);

            return;
        }

        // For guest orders, send to guest_email
        if ($order->guest_email !== null) {
            Notification::route('mail', $order->guest_email)
                ->notify($notification);
        }
    }
}
