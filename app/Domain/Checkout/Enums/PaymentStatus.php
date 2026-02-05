<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Enums;

enum PaymentStatus: string
{
    case Pending    = 'pending';
    case Processing = 'processing';
    case Approved   = 'approved';
    case Declined   = 'declined';
    case Cancelled  = 'cancelled';
    case Refunded   = 'refunded';
    case Failed     = 'failed';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending    => 'Pendente',
            self::Processing => 'Processando',
            self::Approved   => 'Aprovado',
            self::Declined   => 'Recusado',
            self::Cancelled  => 'Cancelado',
            self::Refunded   => 'Reembolsado',
            self::Failed     => 'Falhou',
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
            self::Approved   => 'lime',
            self::Declined   => 'red',
            self::Cancelled  => 'zinc',
            self::Refunded   => 'purple',
            self::Failed     => 'red',
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
            self::Approved   => 'check-circle',
            self::Declined   => 'x-circle',
            self::Cancelled  => 'minus-circle',
            self::Refunded   => 'arrow-uturn-left',
            self::Failed     => 'exclamation-circle',
        };
    }

    /**
     * Check if this is a final status.
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::Approved,
            self::Declined,
            self::Cancelled,
            self::Refunded,
            self::Failed,
        ], true);
    }

    /**
     * Check if payment was successful.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Approved;
    }

    /**
     * Check if payment can be refunded.
     */
    public function canBeRefunded(): bool
    {
        return $this === self::Approved;
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
