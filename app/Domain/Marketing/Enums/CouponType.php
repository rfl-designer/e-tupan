<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Enums;

enum CouponType: string
{
    case Percentage   = 'percentage';
    case Fixed        = 'fixed';
    case FreeShipping = 'free_shipping';

    /**
     * Get the label for display.
     */
    public function label(): string
    {
        return match ($this) {
            self::Percentage   => 'Porcentagem',
            self::Fixed        => 'Valor Fixo',
            self::FreeShipping => 'Frete Gratis',
        };
    }

    /**
     * Get the description.
     */
    public function description(): string
    {
        return match ($this) {
            self::Percentage   => 'Desconto em porcentagem do subtotal',
            self::Fixed        => 'Desconto em valor fixo',
            self::FreeShipping => 'Frete gratis para o pedido',
        };
    }

    /**
     * Check if this type has a value (percentage or fixed).
     */
    public function hasValue(): bool
    {
        return $this !== self::FreeShipping;
    }

    /**
     * Get all cases as array for select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, CouponType $type) => $carry + [$type->value => $type->label()],
            [],
        );
    }
}
