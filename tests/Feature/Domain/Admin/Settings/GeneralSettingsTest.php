<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Settings\GeneralSettings;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('settings page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.settings.index'));

    $response->assertOk()
        ->assertSee('Configuracoes')
        ->assertSee('Geral');
});

test('GeneralSettings component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->assertSee('Nome da Loja')
        ->assertSee('Email de Contato')
        ->assertSee('Telefone')
        ->assertSee('CNPJ')
        ->assertSee('Logo da Loja')
        ->assertSee('Favicon');
});

test('GeneralSettings loads existing values', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('general', [
        'store_name'  => 'Minha Loja Teste',
        'store_email' => 'contato@minhaloja.com',
        'store_phone' => '11999998888',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->assertSet('store_name', 'Minha Loja Teste')
        ->assertSet('store_email', 'contato@minhaloja.com')
        ->assertSet('store_phone', '11999998888');
});

test('GeneralSettings can save settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', 'Nova Loja')
        ->set('store_email', 'nova@loja.com')
        ->set('store_phone', '11988887777')
        ->set('store_cnpj', '12.345.678/0001-90')
        ->set('store_address', 'Rua Teste, 123')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('general.store_name'))->toBe('Nova Loja');
    expect($settingsService->get('general.store_email'))->toBe('nova@loja.com');
});

test('GeneralSettings validates required fields', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', '')
        ->set('store_email', '')
        ->call('save')
        ->assertHasErrors(['store_name', 'store_email']);
});

test('GeneralSettings validates email format', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', 'Loja')
        ->set('store_email', 'email-invalido')
        ->call('save')
        ->assertHasErrors(['store_email']);
});

test('GeneralSettings can upload logo', function () {
    Storage::fake('public');

    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $file = UploadedFile::fake()->image('logo.png', 200, 200);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', 'Loja')
        ->set('store_email', 'test@test.com')
        ->set('logo', $file)
        ->call('save')
        ->assertDispatched('settings-saved');

    Storage::disk('public')->assertExists('settings/' . $file->hashName());
});

test('GeneralSettings can upload favicon', function () {
    Storage::fake('public');

    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $file = UploadedFile::fake()->image('favicon.png', 32, 32);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', 'Loja')
        ->set('store_email', 'test@test.com')
        ->set('favicon', $file)
        ->call('save')
        ->assertDispatched('settings-saved');

    Storage::disk('public')->assertExists('settings/' . $file->hashName());
});

test('GeneralSettings validates logo size', function () {
    Storage::fake('public');

    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $file = UploadedFile::fake()->image('logo.png')->size(3000);

    Livewire::test(GeneralSettings::class)
        ->set('store_name', 'Loja')
        ->set('store_email', 'test@test.com')
        ->set('logo', $file)
        ->call('save')
        ->assertHasErrors(['logo']);
});

test('GeneralSettings can delete logo', function () {
    Storage::fake('public');

    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $file = UploadedFile::fake()->image('logo.png');
    $path = $file->store('settings', 'public');
    $settingsService->set('general.store_logo', $path);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->assertSet('currentLogo', $path)
        ->call('deleteLogo')
        ->assertSet('currentLogo', '');

    Storage::disk('public')->assertMissing($path);
});

test('GeneralSettings can delete favicon', function () {
    Storage::fake('public');

    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $file = UploadedFile::fake()->image('favicon.ico');
    $path = $file->store('settings', 'public');
    $settingsService->set('general.store_favicon', $path);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(GeneralSettings::class)
        ->assertSet('currentFavicon', $path)
        ->call('deleteFavicon')
        ->assertSet('currentFavicon', '');

    Storage::disk('public')->assertMissing($path);
});
