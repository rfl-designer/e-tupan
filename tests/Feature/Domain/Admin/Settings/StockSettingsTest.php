<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Settings\StockSettings;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('StockSettings component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->assertSee('Limite de estoque baixo')
        ->assertSee('Permitir backorders')
        ->assertSee('Email para alertas de estoque')
        ->assertSee('Frequencia de alertas');
});

test('StockSettings loads default values', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->assertSet('low_stock_threshold', 5)
        ->assertSet('allow_backorders', false)
        ->assertSet('stock_alert_email', '')
        ->assertSet('stock_alert_frequency', 'daily');
});

test('StockSettings loads existing values', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('stock', [
        'low_stock_threshold'   => 10,
        'allow_backorders'      => true,
        'stock_alert_email'     => 'estoque@loja.com',
        'stock_alert_frequency' => 'realtime',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->assertSet('low_stock_threshold', 10)
        ->assertSet('allow_backorders', true)
        ->assertSet('stock_alert_email', 'estoque@loja.com')
        ->assertSet('stock_alert_frequency', 'realtime');
});

test('StockSettings can save settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('low_stock_threshold', 15)
        ->set('allow_backorders', true)
        ->set('stock_alert_email', 'alerts@loja.com')
        ->set('stock_alert_frequency', 'weekly')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('stock.low_stock_threshold'))->toBe(15);
    expect($settingsService->get('stock.allow_backorders'))->toBeTrue();
    expect($settingsService->get('stock.stock_alert_email'))->toBe('alerts@loja.com');
    expect($settingsService->get('stock.stock_alert_frequency'))->toBe('weekly');
});

test('StockSettings validates low stock threshold minimum', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('low_stock_threshold', 0)
        ->call('save')
        ->assertHasErrors(['low_stock_threshold']);
});

test('StockSettings validates low stock threshold maximum', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('low_stock_threshold', 1001)
        ->call('save')
        ->assertHasErrors(['low_stock_threshold']);
});

test('StockSettings validates email format', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('stock_alert_email', 'email-invalido')
        ->call('save')
        ->assertHasErrors(['stock_alert_email']);
});

test('StockSettings allows empty email', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('stock_alert_email', '')
        ->call('save')
        ->assertHasNoErrors(['stock_alert_email']);
});

test('StockSettings validates alert frequency options', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->set('stock_alert_frequency', 'invalid')
        ->call('save')
        ->assertHasErrors(['stock_alert_frequency']);
});

test('StockSettings toggles allow backorders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(StockSettings::class)
        ->assertSet('allow_backorders', false)
        ->set('allow_backorders', true)
        ->assertSet('allow_backorders', true);
});
