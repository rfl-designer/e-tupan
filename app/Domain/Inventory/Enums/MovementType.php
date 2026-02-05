<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Enums;

enum MovementType: string
{
    case ManualEntry        = 'manual_entry';
    case ManualExit         = 'manual_exit';
    case Adjustment         = 'adjustment';
    case Sale               = 'sale';
    case Refund             = 'refund';
    case Reservation        = 'reservation';
    case ReservationRelease = 'reservation_release';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::ManualEntry        => 'Entrada Manual',
            self::ManualExit         => 'Saida Manual',
            self::Adjustment         => 'Ajuste de Inventario',
            self::Sale               => 'Venda',
            self::Refund             => 'Estorno',
            self::Reservation        => 'Reserva de Carrinho',
            self::ReservationRelease => 'Liberacao de Reserva',
        };
    }

    /**
     * Check if this movement type increases stock.
     */
    public function isPositive(): bool
    {
        return in_array($this, [
            self::ManualEntry,
            self::Refund,
            self::ReservationRelease,
        ], true);
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::ManualEntry        => 'green',
            self::ManualExit         => 'red',
            self::Adjustment         => 'amber',
            self::Sale               => 'blue',
            self::Refund             => 'purple',
            self::Reservation        => 'zinc',
            self::ReservationRelease => 'zinc',
        };
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

    /**
     * Get manual adjustment types only.
     *
     * @return array<string, string>
     */
    public static function manualOptions(): array
    {
        return collect([
            self::ManualEntry,
            self::ManualExit,
            self::Adjustment,
        ])
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
