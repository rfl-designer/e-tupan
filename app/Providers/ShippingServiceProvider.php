<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Domain\Shipping\Contracts\ShippingProviderInterface;
use App\Domain\Shipping\Providers\{MelhorEnvioProvider, MockShippingProvider};
use Illuminate\Support\ServiceProvider;

class ShippingServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(ShippingProviderInterface::class, function () {
            $provider = config('shipping.default_provider', 'mock');

            // Use MelhorEnvio only if token is configured
            if ($provider === 'melhor_envio') {
                $melhorEnvioProvider = new MelhorEnvioProvider();

                if ($melhorEnvioProvider->isAvailable()) {
                    return $melhorEnvioProvider;
                }

                // Fallback to mock if token is not configured
                return new MockShippingProvider();
            }

            return new MockShippingProvider();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
