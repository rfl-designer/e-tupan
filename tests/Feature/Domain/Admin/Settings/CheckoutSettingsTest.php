<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Settings\CheckoutSettings;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('CheckoutSettings component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSee('Permitir checkout como visitante')
        ->assertSee('CPF obrigatorio')
        ->assertSee('Telefone obrigatorio')
        ->assertSee('Tempo de reserva de estoque');
});

test('CheckoutSettings loads default values', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSet('guest_checkout_enabled', true)
        ->assertSet('cpf_required', true)
        ->assertSet('phone_required', true)
        ->assertSet('stock_reservation_minutes', 30);
});

test('CheckoutSettings loads existing values', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('checkout', [
        'guest_checkout_enabled'    => false,
        'cpf_required'              => false,
        'phone_required'            => false,
        'stock_reservation_minutes' => 60,
        'checkout_message'          => 'Mensagem de teste',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSet('guest_checkout_enabled', false)
        ->assertSet('cpf_required', false)
        ->assertSet('phone_required', false)
        ->assertSet('stock_reservation_minutes', 60)
        ->assertSet('checkout_message', 'Mensagem de teste');
});

test('CheckoutSettings can save settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->set('guest_checkout_enabled', false)
        ->set('cpf_required', false)
        ->set('phone_required', true)
        ->set('stock_reservation_minutes', 45)
        ->set('checkout_message', 'Nova mensagem')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('checkout.guest_checkout_enabled'))->toBeFalse();
    expect($settingsService->get('checkout.cpf_required'))->toBeFalse();
    expect($settingsService->get('checkout.phone_required'))->toBeTrue();
    expect($settingsService->get('checkout.stock_reservation_minutes'))->toBe(45);
    expect($settingsService->get('checkout.checkout_message'))->toBe('Nova mensagem');
});

test('CheckoutSettings validates stock reservation minutes minimum', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->set('stock_reservation_minutes', 2)
        ->call('save')
        ->assertHasErrors(['stock_reservation_minutes']);
});

test('CheckoutSettings validates stock reservation minutes maximum', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->set('stock_reservation_minutes', 150)
        ->call('save')
        ->assertHasErrors(['stock_reservation_minutes']);
});

test('CheckoutSettings validates checkout message max length', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->set('checkout_message', str_repeat('a', 501))
        ->call('save')
        ->assertHasErrors(['checkout_message']);
});

test('CheckoutSettings toggles guest checkout', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSet('guest_checkout_enabled', true)
        ->set('guest_checkout_enabled', false)
        ->assertSet('guest_checkout_enabled', false);
});

test('CheckoutSettings toggles cpf required', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSet('cpf_required', true)
        ->set('cpf_required', false)
        ->assertSet('cpf_required', false);
});

test('CheckoutSettings toggles phone required', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CheckoutSettings::class)
        ->assertSet('phone_required', true)
        ->set('phone_required', false)
        ->assertSet('phone_required', false);
});
