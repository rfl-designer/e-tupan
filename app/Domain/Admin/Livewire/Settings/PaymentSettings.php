<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class PaymentSettings extends Component
{
    public string $active_gateway = 'mercadopago';

    public string $payment_environment = 'sandbox';

    public function mount(SettingsService $settingsService): void
    {
        $settings = $settingsService->getByGroup('payment');

        $this->active_gateway      = $settings['active_gateway'] ?? 'mercadopago';
        $this->payment_environment = $settings['payment_environment'] ?? 'sandbox';
    }

    public function save(SettingsService $settingsService): void
    {
        $this->validate([
            'active_gateway'      => 'required|in:mercadopago,pagseguro,stripe',
            'payment_environment' => 'required|in:sandbox,production',
        ]);

        $settingsService->saveGroup('payment', [
            'active_gateway'      => $this->active_gateway,
            'payment_environment' => $this->payment_environment,
        ]);

        $this->dispatch('settings-saved');
    }

    public function getGatewayOptions(): array
    {
        return [
            'mercadopago' => 'Mercado Pago',
            'pagseguro'   => 'PagSeguro',
            'stripe'      => 'Stripe',
        ];
    }

    public function getEnvironmentOptions(): array
    {
        return [
            'sandbox'    => 'Sandbox (Testes)',
            'production' => 'Producao',
        ];
    }

    public function render(): View
    {
        return view('livewire.admin.settings.payment-settings');
    }
}
