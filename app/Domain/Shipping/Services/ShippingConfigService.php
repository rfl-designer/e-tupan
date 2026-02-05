<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Enums\ShippingCarrier;
use App\Domain\Shipping\Models\ShippingSetting;
use Illuminate\Support\Facades\Cache;

class ShippingConfigService
{
    private const string CARRIERS_CONFIG_KEY = 'carriers_config';

    private const string CARRIERS_CACHE_KEY = 'shipping:carriers_config';

    /**
     * Get all carrier configurations.
     *
     * @return array<string, array{enabled: bool, additional_days: int, price_margin: float, position: int}>
     */
    public function getCarriersConfig(): array
    {
        return Cache::remember(self::CARRIERS_CACHE_KEY, 3600, function () {
            $dbConfig = ShippingSetting::get(self::CARRIERS_CONFIG_KEY);

            if (is_array($dbConfig)) {
                return $dbConfig;
            }

            return $this->getDefaultCarriersConfig();
        });
    }

    /**
     * Get enabled carriers ordered by position.
     *
     * @return array<ShippingCarrier>
     */
    public function getEnabledCarriers(): array
    {
        $config = $this->getCarriersConfig();

        $enabled = array_filter($config, fn (array $c) => $c['enabled'] ?? false);

        uasort($enabled, fn ($a, $b) => ($a['position'] ?? 0) <=> ($b['position'] ?? 0));

        $carriers = [];

        foreach (array_keys($enabled) as $key) {
            $carrier = ShippingCarrier::tryFrom($key);

            if ($carrier !== null) {
                $carriers[] = $carrier;
            }
        }

        return $carriers;
    }

    /**
     * Get configuration for a specific carrier.
     *
     * @return array{enabled: bool, additional_days: int, price_margin: float, position: int}|null
     */
    public function getCarrierConfig(ShippingCarrier $carrier): ?array
    {
        $config = $this->getCarriersConfig();

        return $config[$carrier->value] ?? null;
    }

    /**
     * Update carrier configuration.
     *
     * @param  array<string, array{enabled?: bool, additional_days?: int, price_margin?: float, position?: int}>  $carriersConfig
     */
    public function updateCarriersConfig(array $carriersConfig): void
    {
        $currentConfig = $this->getCarriersConfig();

        foreach ($carriersConfig as $key => $settings) {
            if (!isset($currentConfig[$key])) {
                continue;
            }

            $currentConfig[$key] = array_merge($currentConfig[$key], $settings);
        }

        ShippingSetting::set(
            self::CARRIERS_CONFIG_KEY,
            $currentConfig,
            'json',
            'carriers',
        );

        Cache::forget(self::CARRIERS_CACHE_KEY);
    }

    /**
     * Enable or disable a carrier.
     */
    public function setCarrierEnabled(ShippingCarrier $carrier, bool $enabled): void
    {
        $this->updateCarriersConfig([
            $carrier->value => ['enabled' => $enabled],
        ]);
    }

    /**
     * Update carrier position (for ordering).
     *
     * @param  array<string, int>  $positions
     */
    public function updateCarrierPositions(array $positions): void
    {
        $updates = [];

        foreach ($positions as $key => $position) {
            $updates[$key] = ['position' => $position];
        }

        $this->updateCarriersConfig($updates);
    }

    /**
     * Get handling days configuration.
     */
    public function getHandlingDays(): int
    {
        $value = ShippingSetting::get('handling_days');

        if ($value !== null) {
            return (int) $value;
        }

        return (int) config('shipping.handling_days', 1);
    }

    /**
     * Set handling days configuration.
     */
    public function setHandlingDays(int $days): void
    {
        ShippingSetting::set('handling_days', $days, 'integer', 'general');
    }

    /**
     * Get free shipping configuration.
     *
     * @return array{enabled: bool, min_amount: int, carrier: string}
     */
    public function getFreeShippingConfig(): array
    {
        $enabled   = ShippingSetting::get('free_shipping_enabled');
        $minAmount = ShippingSetting::get('free_shipping_min_amount');
        $carrier   = ShippingSetting::get('free_shipping_carrier');

        return [
            'enabled'    => $enabled ?? (bool) config('shipping.free_shipping.enabled', false),
            'min_amount' => $minAmount ?? (int) config('shipping.free_shipping.min_amount', 0),
            'carrier'    => $carrier ?? config('shipping.free_shipping.carrier', 'correios_pac'),
        ];
    }

    /**
     * Update free shipping configuration.
     *
     * @param  array{enabled?: bool, min_amount?: int, carrier?: string}  $config
     */
    public function updateFreeShippingConfig(array $config): void
    {
        if (isset($config['enabled'])) {
            ShippingSetting::set('free_shipping_enabled', $config['enabled'], 'boolean', 'free_shipping');
        }

        if (isset($config['min_amount'])) {
            ShippingSetting::set('free_shipping_min_amount', $config['min_amount'], 'integer', 'free_shipping');
        }

        if (isset($config['carrier'])) {
            ShippingSetting::set('free_shipping_carrier', $config['carrier'], 'string', 'free_shipping');
        }
    }

    /**
     * Get origin address configuration.
     *
     * @return array{zipcode: string|null, street: string|null, number: string|null, complement: string|null, neighborhood: string|null, city: string|null, state: string|null}
     */
    public function getOriginAddress(): array
    {
        return [
            'zipcode'      => ShippingSetting::get('origin_zipcode') ?? config('shipping.origin.zipcode'),
            'street'       => ShippingSetting::get('origin_street') ?? config('shipping.origin.street'),
            'number'       => ShippingSetting::get('origin_number') ?? config('shipping.origin.number'),
            'complement'   => ShippingSetting::get('origin_complement') ?? config('shipping.origin.complement'),
            'neighborhood' => ShippingSetting::get('origin_neighborhood') ?? config('shipping.origin.neighborhood'),
            'city'         => ShippingSetting::get('origin_city') ?? config('shipping.origin.city'),
            'state'        => ShippingSetting::get('origin_state') ?? config('shipping.origin.state'),
        ];
    }

    /**
     * Update origin address configuration.
     *
     * @param  array<string, string|null>  $address
     */
    public function updateOriginAddress(array $address): void
    {
        $fields = ['zipcode', 'street', 'number', 'complement', 'neighborhood', 'city', 'state'];

        foreach ($fields as $field) {
            if (isset($address[$field])) {
                ShippingSetting::set("origin_{$field}", $address[$field], 'string', 'origin');
            }
        }
    }

    /**
     * Get default carriers configuration from config file.
     *
     * @return array<string, array{enabled: bool, additional_days: int, price_margin: float, position: int}>
     */
    private function getDefaultCarriersConfig(): array
    {
        $configCarriers = config('shipping.carriers', []);
        $result         = [];
        $position       = 0;

        foreach (ShippingCarrier::cases() as $carrier) {
            $configKey     = $carrier->value;
            $carrierConfig = $configCarriers[$configKey] ?? [];

            $result[$carrier->value] = [
                'enabled'         => (bool) ($carrierConfig['enabled'] ?? false),
                'additional_days' => (int) ($carrierConfig['additional_days'] ?? 0),
                'price_margin'    => (float) ($carrierConfig['price_margin'] ?? 0),
                'position'        => $position++,
            ];
        }

        return $result;
    }
}
