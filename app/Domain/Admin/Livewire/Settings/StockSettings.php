<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class StockSettings extends Component
{
    public int $low_stock_threshold = 5;

    public bool $allow_backorders = false;

    public string $stock_alert_email = '';

    public string $stock_alert_frequency = 'daily';

    public function mount(SettingsService $settingsService): void
    {
        $settings = $settingsService->getByGroup('stock');

        $this->low_stock_threshold   = (int) ($settings['low_stock_threshold'] ?? 5);
        $this->allow_backorders      = (bool) ($settings['allow_backorders'] ?? false);
        $this->stock_alert_email     = $settings['stock_alert_email'] ?? '';
        $this->stock_alert_frequency = $settings['stock_alert_frequency'] ?? 'daily';
    }

    public function save(SettingsService $settingsService): void
    {
        $this->validate([
            'low_stock_threshold'   => 'required|integer|min:1|max:1000',
            'stock_alert_email'     => 'nullable|email|max:255',
            'stock_alert_frequency' => 'required|in:realtime,daily,weekly',
        ]);

        $settingsService->saveGroup('stock', [
            'low_stock_threshold'   => $this->low_stock_threshold,
            'allow_backorders'      => $this->allow_backorders,
            'stock_alert_email'     => $this->stock_alert_email,
            'stock_alert_frequency' => $this->stock_alert_frequency,
        ]);

        $this->dispatch('settings-saved');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.stock-settings');
    }
}
