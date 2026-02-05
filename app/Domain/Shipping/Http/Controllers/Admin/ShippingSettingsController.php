<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Http\Controllers\Admin;

use App\Domain\Shipping\Enums\ShippingCarrier;
use App\Domain\Shipping\Providers\MelhorEnvioProvider;
use App\Domain\Shipping\Services\ShippingConfigService;
use Illuminate\Http\{RedirectResponse, Request};
use Illuminate\View\View;

class ShippingSettingsController
{
    public function __construct(
        private readonly ShippingConfigService $configService,
    ) {
    }

    /**
     * Display the shipping settings page.
     */
    public function index(): View
    {
        $carriersConfig     = $this->configService->getCarriersConfig();
        $freeShippingConfig = $this->configService->getFreeShippingConfig();
        $originAddress      = $this->configService->getOriginAddress();
        $handlingDays       = $this->configService->getHandlingDays();

        $carriers = [];

        foreach (ShippingCarrier::cases() as $carrier) {
            $config = $carriersConfig[$carrier->value] ?? [
                'enabled'         => false,
                'additional_days' => 0,
                'price_margin'    => 0,
                'position'        => 0,
            ];

            $carriers[] = [
                'carrier' => $carrier,
                'config'  => $config,
            ];
        }

        usort($carriers, fn ($a, $b) => ($a['config']['position'] ?? 0) <=> ($b['config']['position'] ?? 0));

        return view('admin.shipping.settings', [
            'carriers'           => $carriers,
            'freeShippingConfig' => $freeShippingConfig,
            'originAddress'      => $originAddress,
            'handlingDays'       => $handlingDays,
            'carrierOptions'     => ShippingCarrier::options(),
        ]);
    }

    /**
     * Update carrier settings.
     */
    public function updateCarriers(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'carriers'                   => 'required|array',
            'carriers.*.enabled'         => 'boolean',
            'carriers.*.additional_days' => 'integer|min:0|max:30',
            'carriers.*.price_margin'    => 'numeric|min:0|max:100',
            'carriers.*.position'        => 'integer|min:0',
        ]);

        $carriersConfig = [];

        foreach ($validated['carriers'] as $key => $config) {
            $carriersConfig[$key] = [
                'enabled'         => (bool) ($config['enabled'] ?? false),
                'additional_days' => (int) ($config['additional_days'] ?? 0),
                'price_margin'    => (float) ($config['price_margin'] ?? 0),
                'position'        => (int) ($config['position'] ?? 0),
            ];
        }

        $this->configService->updateCarriersConfig($carriersConfig);

        return redirect()
            ->route('admin.shipping.settings')
            ->with('success', 'Configuracoes de transportadoras atualizadas com sucesso.');
    }

    /**
     * Update free shipping settings.
     */
    public function updateFreeShipping(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'enabled'    => 'boolean',
            'min_amount' => 'required_if:enabled,true|nullable|numeric|min:0',
            'carrier'    => 'required_if:enabled,true|nullable|string',
        ]);

        $this->configService->updateFreeShippingConfig([
            'enabled'    => (bool) ($validated['enabled'] ?? false),
            'min_amount' => (int) (($validated['min_amount'] ?? 0) * 100), // Convert to cents
            'carrier'    => $validated['carrier'] ?? 'correios_pac',
        ]);

        return redirect()
            ->route('admin.shipping.settings')
            ->with('success', 'Configuracoes de frete gratis atualizadas com sucesso.');
    }

    /**
     * Update origin address settings.
     */
    public function updateOrigin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'zipcode'      => 'required|string|size:8',
            'street'       => 'required|string|max:255',
            'number'       => 'required|string|max:20',
            'complement'   => 'nullable|string|max:100',
            'neighborhood' => 'required|string|max:100',
            'city'         => 'required|string|max:100',
            'state'        => 'required|string|size:2',
        ]);

        $this->configService->updateOriginAddress($validated);

        return redirect()
            ->route('admin.shipping.settings')
            ->with('success', 'Endereco de origem atualizado com sucesso.');
    }

    /**
     * Update handling days setting.
     */
    public function updateHandling(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'handling_days' => 'required|integer|min:0|max:30',
        ]);

        $this->configService->setHandlingDays($validated['handling_days']);

        return redirect()
            ->route('admin.shipping.settings')
            ->with('success', 'Prazo de manuseio atualizado com sucesso.');
    }

    /**
     * Test Melhor Envio connection.
     */
    public function testConnection(): RedirectResponse
    {
        $provider = new MelhorEnvioProvider();

        if (!$provider->isAvailable()) {
            return redirect()
                ->route('admin.shipping.settings')
                ->with('error', 'Token do Melhor Envio nao configurado. Configure a variavel MELHOR_ENVIO_TOKEN no arquivo .env');
        }

        $connected = $provider->testConnection();

        if ($connected) {
            return redirect()
                ->route('admin.shipping.settings')
                ->with('success', 'Conexao com Melhor Envio estabelecida com sucesso!');
        }

        return redirect()
            ->route('admin.shipping.settings')
            ->with('error', 'Falha ao conectar com Melhor Envio. Verifique o token e tente novamente.');
    }
}
