<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Providers;

use App\Domain\Shipping\Contracts\ShippingProviderInterface;
use App\Domain\Shipping\DTOs\{ShippingOption, ShippingQuoteRequest};

class MockShippingProvider implements ShippingProviderInterface
{
    /**
     * Base prices in cents.
     */
    private const int PAC_BASE_PRICE = 1500;

    private const int SEDEX_BASE_PRICE = 2500;

    private const int SEDEX_10_BASE_PRICE = 4500;

    /**
     * Weight price per kg in cents.
     */
    private const int WEIGHT_PRICE_PER_KG = 100;

    /**
     * Calculate shipping options for a given request.
     *
     * @return array<ShippingOption>
     */
    public function calculate(ShippingQuoteRequest $request): array
    {
        $cleanZipcode = $request->cleanZipcode();

        if (strlen($cleanZipcode) !== 8) {
            return [];
        }

        $region             = $this->getRegionFromZipcode($cleanZipcode);
        $weightSurcharge    = $this->calculateWeightSurcharge($request->totalWeight);
        $dimensionSurcharge = $this->calculateDimensionSurcharge(
            $request->totalLength,
            $request->totalWidth,
            $request->totalHeight,
        );

        $options = [];

        // PAC - Economico
        $pacPrice = self::PAC_BASE_PRICE + $weightSurcharge + $dimensionSurcharge;
        $pacPrice = $this->applyRegionMultiplier($pacPrice, $region);
        $pacDays  = $this->getPacDeliveryDays($region);

        $options[] = new ShippingOption(
            code: 'pac',
            name: 'PAC',
            price: $pacPrice,
            deliveryDaysMin: $pacDays['min'],
            deliveryDaysMax: $pacDays['max'],
            carrier: 'Correios',
        );

        // SEDEX - Expresso
        $sedexPrice = self::SEDEX_BASE_PRICE + $weightSurcharge + $dimensionSurcharge;
        $sedexPrice = $this->applyRegionMultiplier($sedexPrice, $region);
        $sedexDays  = $this->getSedexDeliveryDays($region);

        $options[] = new ShippingOption(
            code: 'sedex',
            name: 'SEDEX',
            price: $sedexPrice,
            deliveryDaysMin: $sedexDays['min'],
            deliveryDaysMax: $sedexDays['max'],
            carrier: 'Correios',
        );

        // SEDEX 10 - Express premium (apenas capitais)
        if ($this->isCapital($cleanZipcode)) {
            $sedex10Price = self::SEDEX_10_BASE_PRICE + $weightSurcharge + $dimensionSurcharge;
            $sedex10Price = $this->applyRegionMultiplier($sedex10Price, $region);

            $options[] = new ShippingOption(
                code: 'sedex_10',
                name: 'SEDEX 10',
                price: $sedex10Price,
                deliveryDaysMin: 1,
                deliveryDaysMax: 1,
                carrier: 'Correios',
            );
        }

        return $options;
    }

    /**
     * Get the provider name.
     */
    public function getName(): string
    {
        return 'Mock Shipping Provider';
    }

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool
    {
        return true;
    }

    /**
     * Get region from zipcode.
     */
    private function getRegionFromZipcode(string $zipcode): string
    {
        $prefix = (int) substr($zipcode, 0, 1);

        return match (true) {
            $prefix >= 0 && $prefix <= 3 => 'sudeste',
            $prefix >= 4 && $prefix <= 4 => 'sul',
            $prefix >= 5 && $prefix <= 5 => 'nordeste',
            $prefix >= 6 && $prefix <= 6 => 'norte',
            $prefix >= 7 && $prefix <= 7 => 'centro_oeste',
            $prefix >= 8 && $prefix <= 8 => 'sul',
            $prefix >= 9 && $prefix <= 9 => 'sul',
            default                      => 'sudeste',
        };
    }

    /**
     * Calculate weight surcharge.
     */
    private function calculateWeightSurcharge(int $weightInGrams): int
    {
        $weightInKg = $weightInGrams / 1000;

        if ($weightInKg <= 1) {
            return 0;
        }

        return (int) (($weightInKg - 1) * self::WEIGHT_PRICE_PER_KG);
    }

    /**
     * Calculate dimension surcharge.
     */
    private function calculateDimensionSurcharge(int $length, int $width, int $height): int
    {
        $cubicWeight = ($length * $width * $height) / 6000;

        if ($cubicWeight <= 1) {
            return 0;
        }

        return (int) (($cubicWeight - 1) * 50);
    }

    /**
     * Apply region price multiplier.
     */
    private function applyRegionMultiplier(int $price, string $region): int
    {
        $multiplier = match ($region) {
            'sudeste'      => 1.0,
            'sul'          => 1.1,
            'centro_oeste' => 1.3,
            'nordeste'     => 1.4,
            'norte'        => 1.6,
            default        => 1.0,
        };

        return (int) ($price * $multiplier);
    }

    /**
     * Get PAC delivery days by region.
     *
     * @return array{min: int, max: int}
     */
    private function getPacDeliveryDays(string $region): array
    {
        return match ($region) {
            'sudeste'      => ['min' => 5, 'max' => 8],
            'sul'          => ['min' => 6, 'max' => 10],
            'centro_oeste' => ['min' => 8, 'max' => 12],
            'nordeste'     => ['min' => 10, 'max' => 15],
            'norte'        => ['min' => 12, 'max' => 20],
            default        => ['min' => 8, 'max' => 15],
        };
    }

    /**
     * Get SEDEX delivery days by region.
     *
     * @return array{min: int, max: int}
     */
    private function getSedexDeliveryDays(string $region): array
    {
        return match ($region) {
            'sudeste'      => ['min' => 1, 'max' => 3],
            'sul'          => ['min' => 2, 'max' => 4],
            'centro_oeste' => ['min' => 3, 'max' => 5],
            'nordeste'     => ['min' => 4, 'max' => 6],
            'norte'        => ['min' => 5, 'max' => 8],
            default        => ['min' => 3, 'max' => 6],
        };
    }

    /**
     * Check if zipcode is from a capital city (simplified).
     */
    private function isCapital(string $zipcode): bool
    {
        $capitalPrefixes = [
            '01', '02', '03', '04', '05', // Sao Paulo
            '20', '21', '22', '23', '24', // Rio de Janeiro
            '30', '31', '32', // Belo Horizonte
            '40', '41', '42', // Salvador
            '50', '51', '52', // Recife
            '60', '61', '62', // Fortaleza
            '66', '67', // Belem
            '70', '71', '72', '73', // Brasilia
            '74', '75', // Goiania
            '78', '79', // Cuiaba
            '80', '81', '82', // Curitiba
            '88', '89', // Florianopolis
            '90', '91', '92', '93', '94', // Porto Alegre
        ];

        $prefix = substr($zipcode, 0, 2);

        return in_array($prefix, $capitalPrefixes);
    }
}
