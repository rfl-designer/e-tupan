<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Listeners;

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Events\OrderStatusChanged;
use App\Domain\Checkout\Notifications\OrderStatusUpdatedNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class SendOrderStatusUpdateEmail implements ShouldQueue
{
    /**
     * The queue connection that should handle the job.
     */
    public string $queue = 'emails';

    /**
     * Create the event listener.
     */
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(OrderStatusChanged $event): void
    {
        if (! $this->shouldNotifyForStatus($event->newStatus)) {
            return;
        }

        $order = $event->order;
        $notification = new OrderStatusUpdatedNotification(
            $order,
            $event->oldStatus,
            $event->newStatus,
        );

        /** @var User|null $user */
        $user = $order->user;

        if ($user !== null) {
            $user->notify($notification);

            return;
        }

        if ($order->guest_email !== null) {
            Notification::route('mail', $order->guest_email)
                ->notify($notification);
        }
    }

    /**
     * Check if email notification is enabled for the given status.
     */
    private function shouldNotifyForStatus(OrderStatus $status): bool
    {
        $settingKey = $this->getNotificationSettingKey($status);

        if ($settingKey === null) {
            return false;
        }

        return (bool) $this->settingsService->get($settingKey, true);
    }

    /**
     * Get the setting key for the given order status.
     */
    private function getNotificationSettingKey(OrderStatus $status): ?string
    {
        return match ($status) {
            OrderStatus::Processing => 'email.notify_status_processing',
            OrderStatus::Shipped => 'email.notify_status_shipped',
            OrderStatus::Completed => 'email.notify_status_completed',
            OrderStatus::Cancelled => 'email.notify_status_cancelled',
            OrderStatus::Refunded => 'email.notify_status_refunded',
            default => null,
        };
    }
}
