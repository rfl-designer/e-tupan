<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\{Component, WithFileUploads};

class GeneralSettings extends Component
{
    use WithFileUploads;

    public string $store_name = '';

    public string $store_email = '';

    public string $store_phone = '';

    public string $store_cnpj = '';

    public string $store_address = '';

    public $logo;

    public $favicon;

    public string $currentLogo = '';

    public string $currentFavicon = '';

    public function mount(SettingsService $settingsService): void
    {
        $settings = $settingsService->getByGroup('general');

        $this->store_name     = $settings['store_name'] ?? '';
        $this->store_email    = $settings['store_email'] ?? '';
        $this->store_phone    = $settings['store_phone'] ?? '';
        $this->store_cnpj     = $settings['store_cnpj'] ?? '';
        $this->store_address  = $settings['store_address'] ?? '';
        $this->currentLogo    = $settings['store_logo'] ?? '';
        $this->currentFavicon = $settings['store_favicon'] ?? '';
    }

    public function save(SettingsService $settingsService): void
    {
        $this->validate([
            'store_name'    => 'required|string|max:255',
            'store_email'   => 'required|email|max:255',
            'store_phone'   => 'nullable|string|max:20',
            'store_cnpj'    => 'nullable|string|max:18',
            'store_address' => 'nullable|string|max:500',
            'logo'          => 'nullable|image|max:2048',
            'favicon'       => 'nullable|image|max:512',
        ]);

        $settingsService->saveGroup('general', [
            'store_name'    => $this->store_name,
            'store_email'   => $this->store_email,
            'store_phone'   => $this->store_phone,
            'store_cnpj'    => $this->store_cnpj,
            'store_address' => $this->store_address,
        ]);

        if ($this->logo) {
            $this->currentLogo = $settingsService->uploadFile('general.store_logo', $this->logo);
            $this->logo        = null;
        }

        if ($this->favicon) {
            $this->currentFavicon = $settingsService->uploadFile('general.store_favicon', $this->favicon);
            $this->favicon        = null;
        }

        $this->dispatch('settings-saved');
    }

    public function deleteLogo(SettingsService $settingsService): void
    {
        $settingsService->deleteFile('general.store_logo');
        $this->currentLogo = '';
    }

    public function deleteFavicon(SettingsService $settingsService): void
    {
        $settingsService->deleteFile('general.store_favicon');
        $this->currentFavicon = '';
    }

    public function render(): View
    {
        return view('livewire.admin.settings.general-settings');
    }
}
