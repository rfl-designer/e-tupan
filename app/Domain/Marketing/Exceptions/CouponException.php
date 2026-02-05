<?php

declare(strict_types = 1);

namespace App\Domain\Marketing\Exceptions;

use Exception;

class CouponException extends Exception
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
    ) {
        parent::__construct($message);
    }

    public static function notFound(string $code): self
    {
        return new self(
            errorCode: 'coupon_not_found',
            message: "Cupom '$code' nao encontrado.",
        );
    }

    public static function inactive(): self
    {
        return new self(
            errorCode: 'coupon_inactive',
            message: 'Este cupom esta inativo.',
        );
    }

    public static function expired(): self
    {
        return new self(
            errorCode: 'coupon_expired',
            message: 'Este cupom expirou.',
        );
    }

    public static function notStarted(): self
    {
        return new self(
            errorCode: 'coupon_not_started',
            message: 'Este cupom ainda nao esta valido.',
        );
    }

    public static function usageLimitReached(): self
    {
        return new self(
            errorCode: 'coupon_usage_limit_reached',
            message: 'Este cupom atingiu o limite de uso.',
        );
    }

    public static function userLimitReached(): self
    {
        return new self(
            errorCode: 'coupon_user_limit_reached',
            message: 'Voce ja usou este cupom o numero maximo de vezes permitido.',
        );
    }

    public static function minimumOrderNotMet(int $minimumInCents): self
    {
        $minimumFormatted = number_format($minimumInCents / 100, 2, ',', '.');

        return new self(
            errorCode: 'coupon_minimum_not_met',
            message: "O pedido minimo para este cupom e R$ $minimumFormatted.",
        );
    }

    public static function alreadyApplied(): self
    {
        return new self(
            errorCode: 'coupon_already_applied',
            message: 'Um cupom ja esta aplicado neste carrinho.',
        );
    }

    public static function noCouponApplied(): self
    {
        return new self(
            errorCode: 'no_coupon_applied',
            message: 'Nenhum cupom aplicado neste carrinho.',
        );
    }

    public static function requiresShipping(): self
    {
        return new self(
            errorCode: 'coupon_requires_shipping',
            message: 'Este cupom de frete gratis requer que o frete seja calculado primeiro.',
        );
    }
}
