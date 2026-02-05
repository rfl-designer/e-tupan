<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Enums;

enum ShipmentStatus: string
{
    case Pending        = 'pending';
    case CartAdded      = 'cart_added';
    case Purchased      = 'purchased';
    case Generated      = 'generated';
    case Posted         = 'posted';
    case InTransit      = 'in_transit';
    case OutForDelivery = 'out_for_delivery';
    case Delivered      = 'delivered';
    case Returned       = 'returned';
    case Cancelled      = 'cancelled';

    /**
     * Get the human-readable label for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending        => 'Aguardando',
            self::CartAdded      => 'No Carrinho ME',
            self::Purchased      => 'Frete Comprado',
            self::Generated      => 'Etiqueta Gerada',
            self::Posted         => 'Postado',
            self::InTransit      => 'Em Transito',
            self::OutForDelivery => 'Saiu para Entrega',
            self::Delivered      => 'Entregue',
            self::Returned       => 'Devolvido',
            self::Cancelled      => 'Cancelado',
        };
    }

    /**
     * Get the badge color for the status.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending        => 'zinc',
            self::CartAdded      => 'yellow',
            self::Purchased      => 'blue',
            self::Generated      => 'indigo',
            self::Posted         => 'cyan',
            self::InTransit      => 'sky',
            self::OutForDelivery => 'amber',
            self::Delivered      => 'green',
            self::Returned       => 'orange',
            self::Cancelled      => 'red',
        };
    }

    /**
     * Check if shipment can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::Pending,
            self::CartAdded,
            self::Purchased,
            self::Generated,
        ]);
    }

    /**
     * Check if shipment can generate label.
     */
    public function canGenerateLabel(): bool
    {
        return in_array($this, [
            self::Pending,
            self::CartAdded,
            self::Purchased,
        ]);
    }

    /**
     * Check if shipment is in a final state.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Delivered,
            self::Returned,
            self::Cancelled,
        ]);
    }

    /**
     * Check if shipment is trackable.
     */
    public function isTrackable(): bool
    {
        return in_array($this, [
            self::Posted,
            self::InTransit,
            self::OutForDelivery,
            self::Delivered,
            self::Returned,
        ]);
    }

    /**
     * Get all statuses as array for select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $status) {
            $options[$status->value] = $status->label();
        }

        return $options;
    }

    /**
     * Map Melhor Envio status to ShipmentStatus.
     */
    public static function fromMelhorEnvioStatus(string $status): ?self
    {
        return match ($status) {
            'pending'  => self::Pending,
            'released' => self::CartAdded,
            'posted'   => self::Posted,
            'in_transit', 'first_delivery_attempt' => self::InTransit,
            'out_for_delivery' => self::OutForDelivery,
            'delivered'        => self::Delivered,
            'returned', 'undelivered' => self::Returned,
            'cancelled' => self::Cancelled,
            default     => null,
        };
    }
}
