<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CheckoutSettings extends Component
{
    public bool $guest_checkout_enabled = true;

    public bool $cpf_required = true;

    public bool $phone_required = true;

    public int $stock_reservation_minutes = 30;

    public string $checkout_message = '';

    public function mount(SettingsService $settingsService): void
    {
        $settings = $settingsService->getByGroup('checkout');

        $this->guest_checkout_enabled    = (bool) ($settings['guest_checkout_enabled'] ?? true);
        $this->cpf_required              = (bool) ($settings['cpf_required'] ?? true);
        $this->phone_required            = (bool) ($settings['phone_required'] ?? true);
        $this->stock_reservation_minutes = (int) ($settings['stock_reservation_minutes'] ?? 30);
        $this->checkout_message          = $settings['checkout_message'] ?? '';
    }

    public function save(SettingsService $settingsService): void
    {
        $this->validate([
            'stock_reservation_minutes' => 'required|integer|min:5|max:120',
            'checkout_message'          => 'nullable|string|max:500',
        ]);

        $settingsService->saveGroup('checkout', [
            'guest_checkout_enabled'    => $this->guest_checkout_enabled,
            'cpf_required'              => $this->cpf_required,
            'phone_required'            => $this->phone_required,
            'stock_reservation_minutes' => $this->stock_reservation_minutes,
            'checkout_message'          => $this->checkout_message,
        ]);

        $this->dispatch('settings-saved');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.checkout-settings');
    }
}
