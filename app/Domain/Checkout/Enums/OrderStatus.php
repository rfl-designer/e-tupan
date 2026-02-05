<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Enums;

enum OrderStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Shipped    = 'shipped';
    case Completed  = 'completed';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendente',
            self::Processing => 'Processando',
            self::Shipped    => 'Enviado',
            self::Completed  => 'Entregue',
            self::Cancelled  => 'Cancelado',
            self::Refunded   => 'Reembolsado',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::Pending    => 'amber',
            self::Processing => 'sky',
            self::Shipped    => 'indigo',
            self::Completed  => 'lime',
            self::Cancelled  => 'red',
            self::Refunded   => 'purple',
        };
    }

    /**
     * Get the icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::Pending    => 'clock',
            self::Processing => 'arrow-path',
            self::Shipped    => 'truck',
            self::Completed  => 'check-circle',
            self::Cancelled  => 'x-circle',
            self::Refunded   => 'arrow-uturn-left',
        };
    }

    /**
     * Check if the order can be cancelled.
     */
    public function canBeCancelled(): bool
    {
        return in_array($this, [
            self::Pending,
            self::Processing,
        ], true);
    }

    /**
     * Check if the order can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return in_array($this, [
            self::Completed,
            self::Shipped,
        ], true);
    }

    /**
     * Get all cases as options for forms.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
