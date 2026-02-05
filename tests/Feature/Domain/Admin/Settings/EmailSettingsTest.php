<?php

declare(strict_types = 1);

use App\Domain\Admin\Enums\EmailProvider;
use App\Domain\Admin\Livewire\Settings\EmailSettings;
use App\Domain\Admin\Models\Admin;
use App\Domain\Admin\Services\SettingsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('EmailSettings component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSee('Nome do remetente')
        ->assertSee('Email do remetente')
        ->assertSee('Provedor de Email')
        ->assertSee('Templates de Email');
});

test('EmailSettings allows selecting provider: SMTP, Mailgun, Resend', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(EmailSettings::class);

    // Check all providers are available
    foreach (EmailProvider::options() as $value => $label) {
        $component->assertSee($label);
    }

    // Can select each provider
    $component
        ->set('provider', 'smtp')
        ->assertSet('provider', 'smtp')
        ->set('provider', 'mailgun')
        ->assertSet('provider', 'mailgun')
        ->set('provider', 'resend')
        ->assertSet('provider', 'resend');
});

test('EmailSettings shows SMTP fields when provider is smtp', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('provider', 'smtp')
        ->assertSee('Host SMTP')
        ->assertSee('Porta')
        ->assertSee('Usuario')
        ->assertSee('Senha')
        ->assertSee('Criptografia');
});

test('EmailSettings shows Mailgun fields when provider is mailgun', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('provider', 'mailgun')
        ->assertSee('Dominio')
        ->assertSee('API Key')
        ->assertSee('Endpoint (Regiao)');
});

test('EmailSettings shows Resend fields when provider is resend', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('provider', 'resend')
        ->assertSee('API Key');
});

test('EmailSettings loads default values', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSet('sender_name', '')
        ->assertSet('sender_email', '')
        ->assertSet('provider', 'smtp')
        ->assertSet('smtp_port', 587)
        ->assertSet('smtp_encryption', 'tls');
});

test('EmailSettings loads existing values', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    $settingsService->saveGroup('email', [
        'sender_name'  => 'Minha Loja',
        'sender_email' => 'noreply@minhaloja.com',
        'provider'     => 'mailgun',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSet('sender_name', 'Minha Loja')
        ->assertSet('sender_email', 'noreply@minhaloja.com')
        ->assertSet('provider', 'mailgun');
});

test('EmailSettings can save SMTP settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Nova Loja')
        ->set('sender_email', 'contato@novaloja.com')
        ->set('provider', 'smtp')
        ->set('smtp_host', 'smtp.gmail.com')
        ->set('smtp_port', 587)
        ->set('smtp_username', 'usuario@gmail.com')
        ->set('smtp_password', 'senha123')
        ->set('smtp_encryption', 'tls')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('email.sender_name'))->toBe('Nova Loja');
    expect($settingsService->get('email.sender_email'))->toBe('contato@novaloja.com');
    expect($settingsService->get('email.provider'))->toBe('smtp');
    expect($settingsService->get('email.smtp_host'))->toBe('smtp.gmail.com');
    expect($settingsService->get('email.smtp_port'))->toBe(587);
    expect($settingsService->get('email.smtp_username'))->toBe('usuario@gmail.com');
    expect($settingsService->get('email.smtp_encryption'))->toBe('tls');
});

test('EmailSettings can save Mailgun settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Nova Loja')
        ->set('sender_email', 'contato@novaloja.com')
        ->set('provider', 'mailgun')
        ->set('mailgun_domain', 'mg.exemplo.com')
        ->set('mailgun_secret', 'key-abc123')
        ->set('mailgun_endpoint', 'api.eu.mailgun.net')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('email.provider'))->toBe('mailgun');
    expect($settingsService->get('email.mailgun_domain'))->toBe('mg.exemplo.com');
    expect($settingsService->get('email.mailgun_endpoint'))->toBe('api.eu.mailgun.net');
});

test('EmailSettings can save Resend settings', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Nova Loja')
        ->set('sender_email', 'contato@novaloja.com')
        ->set('provider', 'resend')
        ->set('resend_api_key', 're_abc123xyz')
        ->call('save')
        ->assertDispatched('settings-saved');

    $settingsService = app(SettingsService::class);
    expect($settingsService->get('email.provider'))->toBe('resend');
});

test('EmailSettings validates required sender name', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', '')
        ->set('sender_email', 'test@test.com')
        ->call('save')
        ->assertHasErrors(['sender_name']);
});

test('EmailSettings validates required sender email', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', '')
        ->call('save')
        ->assertHasErrors(['sender_email']);
});

test('EmailSettings validates email format', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'email-invalido')
        ->call('save')
        ->assertHasErrors(['sender_email']);
});

test('EmailSettings validates sender name max length', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', str_repeat('a', 256))
        ->set('sender_email', 'test@test.com')
        ->call('save')
        ->assertHasErrors(['sender_name']);
});

test('EmailSettings validates sender email max length', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', str_repeat('a', 250) . '@test.com')
        ->call('save')
        ->assertHasErrors(['sender_email']);
});

test('EmailSettings validates provider value', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'test@test.com')
        ->set('provider', 'invalid_provider')
        ->call('save')
        ->assertHasErrors(['provider']);
});

test('EmailSettings validates SMTP host required when provider is smtp', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'test@test.com')
        ->set('provider', 'smtp')
        ->set('smtp_host', '')
        ->call('save')
        ->assertHasErrors(['smtp_host']);
});

test('EmailSettings validates Mailgun domain required when provider is mailgun', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'test@test.com')
        ->set('provider', 'mailgun')
        ->set('mailgun_domain', '')
        ->set('mailgun_secret', 'key-123')
        ->call('save')
        ->assertHasErrors(['mailgun_domain']);
});

test('EmailSettings validates Resend API key required when provider is resend', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'test@test.com')
        ->set('provider', 'resend')
        ->set('resend_api_key', '')
        ->call('save')
        ->assertHasErrors(['resend_api_key']);
});

test('EmailSettings shows email template list', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->assertSee('Confirmacao de Pedido')
        ->assertSee('Pagamento Aprovado')
        ->assertSee('Pedido Enviado')
        ->assertSee('Pedido Entregue')
        ->assertSee('Pedido Cancelado')
        ->assertSee('Reset de Senha');
});

test('EmailSettings encrypts sensitive fields when saving', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Test')
        ->set('sender_email', 'test@test.com')
        ->set('provider', 'smtp')
        ->set('smtp_host', 'smtp.test.com')
        ->set('smtp_password', 'secret_password')
        ->call('save');

    // Verify the password is encrypted in database
    $setting = \App\Domain\Admin\Models\StoreSetting::where('key', 'email.smtp_password')->first();
    expect($setting)->not->toBeNull();
    expect($setting->value)->not->toBe('secret_password');

    // But decrypts correctly when retrieved
    $settingsService = app(SettingsService::class);
    expect($settingsService->get('email.smtp_password'))->toBe('secret_password');
});

test('EmailSettings preserves encrypted fields when not changed', function () {
    $admin           = Admin::factory()->withTwoFactor()->create();
    $settingsService = app(SettingsService::class);

    // Set initial values
    $settingsService->saveGroup('email', [
        'sender_name'   => 'Test',
        'sender_email'  => 'test@test.com',
        'provider'      => 'smtp',
        'smtp_host'     => 'smtp.test.com',
        'smtp_password' => 'initial_password',
    ]);

    actingAsAdminWith2FA($this, $admin);

    // Update without changing password (empty string)
    Livewire::test(EmailSettings::class)
        ->set('sender_name', 'Updated Name')
        ->set('smtp_password', '')
        ->call('save');

    // Password should remain unchanged
    expect($settingsService->get('email.smtp_password'))->toBe('initial_password');
    expect($settingsService->get('email.sender_name'))->toBe('Updated Name');
});
