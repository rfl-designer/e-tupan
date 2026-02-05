<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Factories;

use App\Domain\Checkout\Contracts\PaymentGatewayInterface;
use App\Domain\Checkout\Gateways\{MercadoPagoGateway, MockPaymentGateway};
use InvalidArgumentException;

class PaymentGatewayFactory
{
    /**
     * Create a payment gateway instance.
     */
    public static function make(?string $gateway = null): PaymentGatewayInterface
    {
        $gateway = $gateway ?? config('payment.default', 'mock');

        return match ($gateway) {
            'mock'        => new MockPaymentGateway(),
            'mercadopago' => new MercadoPagoGateway(),
            default       => throw new InvalidArgumentException("Unknown payment gateway: {$gateway}"),
        };
    }

    /**
     * Get the default gateway.
     */
    public static function default(): PaymentGatewayInterface
    {
        return self::make();
    }

    /**
     * Get all available gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public static function all(): array
    {
        return [
            'mock'        => new MockPaymentGateway(),
            'mercadopago' => new MercadoPagoGateway(),
        ];
    }

    /**
     * Get list of available gateway names.
     *
     * @return array<string>
     */
    public static function available(): array
    {
        return array_keys(
            array_filter(
                self::all(),
                fn (PaymentGatewayInterface $gateway) => $gateway->isAvailable(),
            ),
        );
    }
}
