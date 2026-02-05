<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Enums;

enum ShippingCarrier: string
{
    case CorreiosPac     = 'correios_pac';
    case CorreiosSedex   = 'correios_sedex';
    case CorreiosSedex10 = 'correios_sedex_10';
    case CorreiosSedex12 = 'correios_sedex_12';
    case JadlogPackage   = 'jadlog_package';
    case JadlogCom       = 'jadlog_com';
    case Loggi           = 'loggi';
    case AzulCargo       = 'azul_cargo';
    case LatamCargo      = 'latam_cargo';

    /**
     * Get the human-readable label for the carrier.
     */
    public function label(): string
    {
        return match ($this) {
            self::CorreiosPac     => 'Correios PAC',
            self::CorreiosSedex   => 'Correios SEDEX',
            self::CorreiosSedex10 => 'Correios SEDEX 10',
            self::CorreiosSedex12 => 'Correios SEDEX 12',
            self::JadlogPackage   => 'Jadlog Package',
            self::JadlogCom       => 'Jadlog .Com',
            self::Loggi           => 'Loggi',
            self::AzulCargo       => 'Azul Cargo',
            self::LatamCargo      => 'LATAM Cargo',
        };
    }

    /**
     * Get the Melhor Envio service code for the carrier.
     */
    public function melhorEnvioCode(): int
    {
        return match ($this) {
            self::CorreiosPac     => 1,
            self::CorreiosSedex   => 2,
            self::CorreiosSedex10 => 15,
            self::CorreiosSedex12 => 16,
            self::JadlogPackage   => 3,
            self::JadlogCom       => 4,
            self::Loggi           => 5,
            self::AzulCargo       => 6,
            self::LatamCargo      => 7,
        };
    }

    /**
     * Get the carrier company name.
     */
    public function company(): string
    {
        return match ($this) {
            self::CorreiosPac, self::CorreiosSedex, self::CorreiosSedex10, self::CorreiosSedex12 => 'Correios',
            self::JadlogPackage, self::JadlogCom => 'Jadlog',
            self::Loggi      => 'Loggi',
            self::AzulCargo  => 'Azul Cargo',
            self::LatamCargo => 'LATAM Cargo',
        };
    }

    /**
     * Get the default average delivery days for the carrier.
     */
    public function defaultDeliveryDays(): int
    {
        return match ($this) {
            self::CorreiosSedex10 => 1,
            self::CorreiosSedex12 => 1,
            self::CorreiosSedex   => 3,
            self::JadlogCom       => 4,
            self::Loggi           => 3,
            self::AzulCargo       => 2,
            self::LatamCargo      => 2,
            self::CorreiosPac     => 8,
            self::JadlogPackage   => 6,
        };
    }

    /**
     * Check if carrier is express (fast delivery).
     */
    public function isExpress(): bool
    {
        return match ($this) {
            self::CorreiosSedex, self::CorreiosSedex10, self::CorreiosSedex12, self::JadlogCom, self::AzulCargo, self::LatamCargo => true,
            default => false,
        };
    }

    /**
     * Get all carriers as array for select options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $carrier) {
            $options[$carrier->value] = $carrier->label();
        }

        return $options;
    }

    /**
     * Get all enabled carriers from config.
     *
     * @return array<self>
     */
    public static function enabled(): array
    {
        $carriers       = [];
        $configCarriers = config('shipping.carriers', []);

        foreach (self::cases() as $carrier) {
            if (($configCarriers[$carrier->value]['enabled'] ?? false) === true) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Get carrier by Melhor Envio service ID.
     */
    public static function fromMelhorEnvioCode(int $code): ?self
    {
        foreach (self::cases() as $carrier) {
            if ($carrier->melhorEnvioCode() === $code) {
                return $carrier;
            }
        }

        return null;
    }

    /**
     * Get the external tracking URL for the carrier.
     */
    public function getTrackingUrl(string $trackingNumber): ?string
    {
        return match ($this) {
            self::CorreiosPac, self::CorreiosSedex, self::CorreiosSedex10, self::CorreiosSedex12 => "https://www.linkcorreios.com.br/?id={$trackingNumber}",
            self::JadlogPackage, self::JadlogCom => "https://www.jadlog.com.br/jadlog/tracking?cte={$trackingNumber}",
            self::Loggi => null, // Loggi não tem rastreamento público por código
            self::AzulCargo => "https://www.azulcargo.com.br/rastreamento?conhecimento={$trackingNumber}",
            self::LatamCargo => "https://www.latamcargo.com/pt/trackshipment?docNumber={$trackingNumber}",
        };
    }

    /**
     * Try to create a carrier from a string name/code.
     */
    public static function tryFromName(string $name): ?self
    {
        $normalized = strtolower(trim($name));

        // Try exact value match first
        $carrier = self::tryFrom($normalized);
        if ($carrier !== null) {
            return $carrier;
        }

        // Try to match by company name
        foreach (self::cases() as $carrier) {
            if (strtolower($carrier->company()) === $normalized) {
                return $carrier;
            }
            if (strtolower($carrier->label()) === $normalized) {
                return $carrier;
            }
        }

        // Try partial matches for common names
        if (str_contains($normalized, 'correios') || str_contains($normalized, 'pac')) {
            return self::CorreiosPac;
        }
        if (str_contains($normalized, 'sedex')) {
            return self::CorreiosSedex;
        }
        if (str_contains($normalized, 'jadlog')) {
            return self::JadlogPackage;
        }

        return null;
    }
}
