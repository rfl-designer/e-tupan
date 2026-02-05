<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Notifications;

use App\Domain\Shipping\Models\Shipment;
use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShipmentShippedNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly Shipment $shipment,
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
        $this->shipment->loadMissing('order');
        $order = $this->shipment->order;

        $message = (new MailMessage())
            ->subject("Seu pedido #{$order?->order_number} foi enviado!")
            ->greeting("Ola, {$this->shipment->recipient_name}!")
            ->line('Temos otimas noticias! Seu pedido foi enviado e esta a caminho.')
            ->line("**Codigo de rastreamento:** {$this->shipment->tracking_number}");

        // Add carrier info
        $message->line("**Transportadora:** {$this->shipment->carrier_name}");

        // Add shipping address
        $message->line('**Endereco de entrega:**');
        $message->line("{$this->shipment->recipient_name}");
        $message->line("{$this->shipment->address_street}, {$this->shipment->address_number}");

        if ($this->shipment->address_complement) {
            $message->line($this->shipment->address_complement);
        }

        $message->line("{$this->shipment->address_neighborhood}");
        $message->line("{$this->shipment->address_city}/{$this->shipment->address_state}");
        $message->line("CEP: {$this->shipment->address_zipcode}");

        // Add tracking link if route exists
        if ($this->shipment->tracking_number && \Illuminate\Support\Facades\Route::has('tracking.show')) {
            $trackingUrl = route('tracking.show', ['code' => $this->shipment->tracking_number]);
            $message->action('Rastrear meu pedido', $trackingUrl);
        }

        $message->line('Voce recebera atualizacoes sobre o status da entrega.');
        $message->line('Obrigado por comprar conosco!');

        return $message;
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'shipment_id'     => $this->shipment->id,
            'order_id'        => $this->shipment->order_id,
            'tracking_number' => $this->shipment->tracking_number,
            'carrier'         => $this->shipment->carrier_name,
        ];
    }
}
