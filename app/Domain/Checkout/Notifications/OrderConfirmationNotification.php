<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Notifications;

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Models\Payment;
use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderConfirmationNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Order $order,
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $order = $this->order->load(['items', 'payments']);
        $storeName = $this->getStoreName();
        $subject = "Pedido #{$order->order_number} confirmado - {$storeName}";

        $orderUrl = $this->getOrderTrackingUrl($order);

        /** @var Payment|null $payment */
        $payment = $order->latestPayment();

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.orders.confirmation', [
                'order' => $order,
                'orderUrl' => $orderUrl,
                'payment' => $payment,
            ])
            ->text('emails.orders.confirmation_plain', [
                'order' => $order,
                'orderUrl' => $orderUrl,
                'payment' => $payment,
            ]);
    }

    /**
     * Get the order tracking URL based on customer type.
     */
    private function getOrderTrackingUrl(Order $order): string
    {
        // For authenticated users, link to customer dashboard
        if ($order->user_id !== null) {
            return route('customer.orders.show', ['order' => $order->id]);
        }

        // For guests, use checkout success with access token
        return route('checkout.success', [
            'order' => $order->id,
            'token' => $order->access_token,
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'order_id'     => $this->order->id,
            'order_number' => $this->order->order_number,
            'total'        => $this->order->total,
        ];
    }

    /**
     * Get the store name from settings.
     */
    private function getStoreName(): string
    {
        $settings = app(SettingsService::class);
        $storeName = $settings->get('general.store_name');

        return is_string($storeName) && $storeName !== '' ? $storeName : config('app.name');
    }
}
