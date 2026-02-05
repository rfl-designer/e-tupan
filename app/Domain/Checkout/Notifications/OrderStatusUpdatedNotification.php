<?php

declare(strict_types=1);

namespace App\Domain\Checkout\Notifications;

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Order $order,
        public readonly OrderStatus $oldStatus,
        public readonly OrderStatus $newStatus,
    ) {
        $this->initializeQueueableNotification();
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
        $subject = $this->getSubject($storeName);
        $orderUrl = $this->getOrderTrackingUrl($order);

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.orders.status-updated', [
                'order' => $order,
                'orderUrl' => $orderUrl,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
                'subject' => $subject,
                'preheader' => $this->getPreheader(),
            ])
            ->text('emails.orders.status-updated_plain', [
                'order' => $order,
                'orderUrl' => $orderUrl,
                'oldStatus' => $this->oldStatus,
                'newStatus' => $this->newStatus,
            ]);
    }

    /**
     * Get the subject based on the new status.
     */
    private function getSubject(string $storeName): string
    {
        $orderNumber = $this->order->order_number;

        return match ($this->newStatus) {
            OrderStatus::Processing => "Pedido #{$orderNumber} - Pagamento Confirmado - {$storeName}",
            OrderStatus::Shipped => "Pedido #{$orderNumber} foi Enviado - {$storeName}",
            OrderStatus::Completed => "Pedido #{$orderNumber} foi Entregue - {$storeName}",
            OrderStatus::Cancelled => "Pedido #{$orderNumber} foi Cancelado - {$storeName}",
            OrderStatus::Refunded => "Pedido #{$orderNumber} foi Reembolsado - {$storeName}",
            default => "Atualização do Pedido #{$orderNumber} - {$storeName}",
        };
    }

    /**
     * Get the preheader text based on the new status.
     */
    private function getPreheader(): string
    {
        $orderNumber = $this->order->order_number;

        return match ($this->newStatus) {
            OrderStatus::Processing => "O pagamento do seu pedido #{$orderNumber} foi confirmado",
            OrderStatus::Shipped => "Seu pedido #{$orderNumber} está a caminho",
            OrderStatus::Completed => "Seu pedido #{$orderNumber} foi entregue com sucesso",
            OrderStatus::Cancelled => "Seu pedido #{$orderNumber} foi cancelado",
            OrderStatus::Refunded => "O reembolso do pedido #{$orderNumber} foi processado",
            default => "O status do seu pedido #{$orderNumber} foi atualizado",
        };
    }

    /**
     * Get the order tracking URL based on customer type.
     */
    private function getOrderTrackingUrl(Order $order): string
    {
        if ($order->user_id !== null) {
            return route('customer.orders.show', ['order' => $order->id]);
        }

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
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'old_status' => $this->oldStatus->value,
            'new_status' => $this->newStatus->value,
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
