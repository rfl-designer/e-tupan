<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Enums;

enum NotificationType: string
{
    case NewOrder          = 'new_order';
    case PaymentConfirmed  = 'payment_confirmed';
    case PaymentFailed     = 'payment_failed';
    case LowStock          = 'low_stock';
    case OrderCancelled    = 'order_cancelled';
    case IntegrationError  = 'integration_error';
    case ShipmentDelivered = 'shipment_delivered';

    public function icon(): string
    {
        return match ($this) {
            self::NewOrder          => 'shopping-cart',
            self::PaymentConfirmed  => 'credit-card',
            self::PaymentFailed     => 'x-circle',
            self::LowStock          => 'archive-box',
            self::OrderCancelled    => 'x-mark',
            self::IntegrationError  => 'exclamation-triangle',
            self::ShipmentDelivered => 'truck',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NewOrder          => 'blue',
            self::PaymentConfirmed  => 'green',
            self::PaymentFailed     => 'red',
            self::LowStock          => 'yellow',
            self::OrderCancelled    => 'red',
            self::IntegrationError  => 'orange',
            self::ShipmentDelivered => 'green',
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::NewOrder          => 'Novo Pedido',
            self::PaymentConfirmed  => 'Pagamento Confirmado',
            self::PaymentFailed     => 'Pagamento Falhou',
            self::LowStock          => 'Estoque Baixo',
            self::OrderCancelled    => 'Pedido Cancelado',
            self::IntegrationError  => 'Erro de Integracao',
            self::ShipmentDelivered => 'Entrega Realizada',
        };
    }
}
