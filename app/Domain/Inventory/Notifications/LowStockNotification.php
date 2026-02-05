<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Notifications;

use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

class LowStockNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     *
     * @param  Collection<int, \App\Domain\Catalog\Models\Product>  $products
     */
    public function __construct(
        public readonly Collection $products,
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
        $count       = $this->products->count();
        $productWord = $count === 1 ? 'produto' : 'produtos';

        $mail = (new MailMessage())
            ->subject("Alerta de Estoque Baixo - {$count} {$productWord}")
            ->greeting('Alerta de Estoque Baixo')
            ->line("Existem {$count} {$productWord} com estoque baixo que requerem sua atenção:")
            ->line('');

        foreach ($this->products as $product) {
            $threshold = $product->low_stock_threshold ?? config('inventory.default_low_stock_threshold', 5);
            $mail->line("**{$product->name}** (SKU: {$product->sku})");
            $mail->line("- Estoque atual: {$product->stock_quantity} unidades");
            $mail->line("- Limite minimo: {$threshold} unidades");
            $mail->line('');
        }

        return $mail
            ->action('Ver Produtos com Estoque Baixo', url('/admin/inventory?filter=low_stock'))
            ->salutation('Sistema de Gestao de Estoque');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'products_count' => $this->products->count(),
            'product_ids'    => $this->products->pluck('id')->toArray(),
        ];
    }
}
