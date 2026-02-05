<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\StoreSetting;
use App\Domain\Admin\Services\SettingsService;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->service = app(SettingsService::class);
});

test('can get setting with default value when not set', function () {
    $value = $this->service->get('general.store_name');

    expect($value)->toBe('');
});

test('can get setting with custom default when not set', function () {
    $value = $this->service->get('nonexistent.key', 'custom_default');

    expect($value)->toBe('custom_default');
});

test('can set and get a string setting', function () {
    $this->service->set('general.store_name', 'Minha Loja');

    $value = $this->service->get('general.store_name');

    expect($value)->toBe('Minha Loja');
    expect(StoreSetting::where('key', 'general.store_name')->exists())->toBeTrue();
});

test('can set and get a boolean setting', function () {
    $this->service->set('checkout.guest_checkout_enabled', false);

    $value = $this->service->get('checkout.guest_checkout_enabled');

    expect($value)->toBeFalse();
});

test('can set and get an integer setting', function () {
    $this->service->set('checkout.stock_reservation_minutes', 45);

    $value = $this->service->get('checkout.stock_reservation_minutes');

    expect($value)->toBe(45);
});

test('can get settings by group', function () {
    $this->service->set('general.store_name', 'Loja Teste');
    $this->service->set('general.store_email', 'contato@teste.com');

    $settings = $this->service->getByGroup('general');

    expect($settings)->toHaveKey('store_name', 'Loja Teste');
    expect($settings)->toHaveKey('store_email', 'contato@teste.com');
});

test('getByGroup returns defaults for unset values', function () {
    $settings = $this->service->getByGroup('checkout');

    expect($settings)->toHaveKey('guest_checkout_enabled', true);
    expect($settings)->toHaveKey('stock_reservation_minutes', 30);
});

test('can save multiple settings in a group', function () {
    $this->service->saveGroup('general', [
        'store_name'  => 'Nova Loja',
        'store_email' => 'nova@loja.com',
        'store_phone' => '11999999999',
    ]);

    $settings = $this->service->getByGroup('general');

    expect($settings['store_name'])->toBe('Nova Loja');
    expect($settings['store_email'])->toBe('nova@loja.com');
    expect($settings['store_phone'])->toBe('11999999999');
});

test('saveGroup overwrites existing values', function () {
    $this->service->set('general.store_name', 'Loja Antiga');

    $this->service->saveGroup('general', [
        'store_name' => 'Loja Nova',
    ]);

    expect($this->service->get('general.store_name'))->toBe('Loja Nova');
});

test('getGroups returns all available groups', function () {
    $groups = $this->service->getGroups();

    expect($groups)->toContain('general');
    expect($groups)->toContain('checkout');
    expect($groups)->toContain('stock');
    expect($groups)->toContain('payment');
    expect($groups)->toContain('email');
});

test('clearCache clears the settings cache', function () {
    $this->service->set('general.store_name', 'Loja Cache');

    $this->service->clearCache();

    $value = $this->service->get('general.store_name');
    expect($value)->toBe('Loja Cache');
});
