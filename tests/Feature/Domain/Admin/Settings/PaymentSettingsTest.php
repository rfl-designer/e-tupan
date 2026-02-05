<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Settings\PaymentSettings;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('PaymentSettings component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->assertSee('Gateway de pagamento ativo')
        ->assertSee('Ambiente')
        ->assertSee('Credenciais de pagamento');
});

test('PaymentSettings loads default values', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->assertSet('active_gateway', 'mercadopago')
        ->assertSet('payment_environment', 'sandbox');
});

test('PaymentSettings loads existing values', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('payment', [
        'active_gateway'      => 'stripe',
        'payment_environment' => 'production',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->assertSet('active_gateway', 'stripe')
        ->assertSet('payment_environment', 'production');
});

test('PaymentSettings can save settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->set('active_gateway', 'pagseguro')
        ->set('payment_environment', 'production')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('payment.active_gateway'))->toBe('pagseguro');
    expect($settingsService->get('payment.payment_environment'))->toBe('production');
});

test('PaymentSettings validates gateway options', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->set('active_gateway', 'invalid_gateway')
        ->call('save')
        ->assertHasErrors(['active_gateway']);
});

test('PaymentSettings validates environment options', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->set('payment_environment', 'invalid_env')
        ->call('save')
        ->assertHasErrors(['payment_environment']);
});

test('PaymentSettings shows production warning when production selected', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->set('payment_environment', 'production')
        ->assertSee('Ambiente de producao')
        ->assertSee('Todas as transacoes serao reais');
});

test('PaymentSettings hides production warning when sandbox selected', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(PaymentSettings::class)
        ->set('payment_environment', 'sandbox')
        ->assertDontSee('Ambiente de producao');
});

test('PaymentSettings returns correct gateway options', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(PaymentSettings::class);

    $options = $component->instance()->getGatewayOptions();

    expect($options)->toHaveKey('mercadopago', 'Mercado Pago');
    expect($options)->toHaveKey('pagseguro', 'PagSeguro');
    expect($options)->toHaveKey('stripe', 'Stripe');
});

test('PaymentSettings returns correct environment options', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(PaymentSettings::class);

    $options = $component->instance()->getEnvironmentOptions();

    expect($options)->toHaveKey('sandbox', 'Sandbox (Testes)');
    expect($options)->toHaveKey('production', 'Producao');
});
