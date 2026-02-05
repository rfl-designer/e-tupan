<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Enums;

enum PaymentMethod: string
{
    case CreditCard = 'credit_card';
    case Pix        = 'pix';
    case BankSlip   = 'bank_slip';

    /**
     * Get the human-readable label.
     */
    public function label(): string
    {
        return match ($this) {
            self::CreditCard => 'Cartao de Credito',
            self::Pix        => 'Pix',
            self::BankSlip   => 'Boleto Bancario',
        };
    }

    /**
     * Get the icon for UI display.
     */
    public function icon(): string
    {
        return match ($this) {
            self::CreditCard => 'credit-card',
            self::Pix        => 'qr-code',
            self::BankSlip   => 'document-text',
        };
    }

    /**
     * Get the description for UI display.
     */
    public function description(): string
    {
        return match ($this) {
            self::CreditCard => 'Parcele em ate 12x',
            self::Pix        => 'Aprovacao imediata',
            self::BankSlip   => 'Vencimento em 3 dias uteis',
        };
    }

    /**
     * Get the color for UI display.
     */
    public function color(): string
    {
        return match ($this) {
            self::CreditCard => 'sky',
            self::Pix        => 'teal',
            self::BankSlip   => 'amber',
        };
    }

    /**
     * Check if payment requires instant confirmation.
     */
    public function requiresInstantConfirmation(): bool
    {
        return $this === self::CreditCard;
    }

    /**
     * Check if payment is asynchronous (webhook-based).
     */
    public function isAsynchronous(): bool
    {
        return in_array($this, [
            self::Pix,
            self::BankSlip,
        ], true);
    }

    /**
     * Get the default expiration time in minutes.
     */
    public function defaultExpirationMinutes(): ?int
    {
        return match ($this) {
            self::CreditCard => null,
            self::Pix        => 30,
            self::BankSlip   => 4320, // 3 days
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
}
