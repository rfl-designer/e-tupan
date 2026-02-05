<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\StoreSetting;
use App\Domain\Admin\Services\SettingsService;
use App\Providers\EmailServiceProvider;

beforeEach(function () {
    StoreSetting::query()->where('group', 'email')->delete();
    StoreSetting::clearCache('email');

    // Reset config to original values for each test
    config([
        'mail.default'                 => 'log',
        'mail.mailers.smtp.host'       => '127.0.0.1',
        'mail.mailers.smtp.port'       => 2525,
        'mail.mailers.smtp.username'   => null,
        'mail.mailers.smtp.password'   => null,
        'mail.mailers.smtp.encryption' => null,
        'mail.from.name'               => 'Example',
        'mail.from.address'            => 'hello@example.com',
        'services.mailgun.domain'      => null,
        'services.mailgun.secret'      => null,
        'services.mailgun.endpoint'    => 'api.mailgun.net',
        'services.resend.key'          => null,
    ]);
});

describe('EmailServiceProvider', function () {
    describe('SMTP configuration', function () {
        it('overrides smtp config from database settings', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'        => 'smtp',
                'smtp_host'       => 'smtp.example.com',
                'smtp_port'       => 465,
                'smtp_username'   => 'user@example.com',
                'smtp_password'   => 'secret123',
                'smtp_encryption' => 'ssl',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.default'))->toBe('smtp');
            expect(config('mail.mailers.smtp.host'))->toBe('smtp.example.com');
            expect(config('mail.mailers.smtp.port'))->toBe(465);
            expect(config('mail.mailers.smtp.username'))->toBe('user@example.com');
            expect(config('mail.mailers.smtp.password'))->toBe('secret123');
            expect(config('mail.mailers.smtp.encryption'))->toBe('ssl');
        });

        it('uses tls encryption when configured', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'        => 'smtp',
                'smtp_host'       => 'smtp.gmail.com',
                'smtp_port'       => 587,
                'smtp_encryption' => 'tls',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.mailers.smtp.encryption'))->toBe('tls');
        });

        it('sets encryption to null when set to none', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'        => 'smtp',
                'smtp_host'       => 'localhost',
                'smtp_port'       => 25,
                'smtp_encryption' => 'none',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.mailers.smtp.encryption'))->toBeNull();
        });
    });

    describe('Mailgun configuration', function () {
        it('overrides mailgun config from database settings', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'         => 'mailgun',
                'mailgun_domain'   => 'mg.example.com',
                'mailgun_secret'   => 'key-abc123',
                'mailgun_endpoint' => 'api.eu.mailgun.net',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.default'))->toBe('mailgun');
            expect(config('services.mailgun.domain'))->toBe('mg.example.com');
            expect(config('services.mailgun.secret'))->toBe('key-abc123');
            expect(config('services.mailgun.endpoint'))->toBe('api.eu.mailgun.net');
        });

        it('uses us endpoint by default for mailgun', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'         => 'mailgun',
                'mailgun_domain'   => 'mg.example.com',
                'mailgun_secret'   => 'key-abc123',
                'mailgun_endpoint' => 'api.mailgun.net',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('services.mailgun.endpoint'))->toBe('api.mailgun.net');
        });
    });

    describe('Resend configuration', function () {
        it('overrides resend config from database settings', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'provider'       => 'resend',
                'resend_api_key' => 're_abc123xyz',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.default'))->toBe('resend');
            expect(config('services.resend.key'))->toBe('re_abc123xyz');
        });
    });

    describe('from address configuration', function () {
        it('overrides from address from database settings', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'sender_name'  => 'Minha Loja',
                'sender_email' => 'contato@minhaloja.com',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.from.name'))->toBe('Minha Loja');
            expect(config('mail.from.address'))->toBe('contato@minhaloja.com');
        });

        it('does not override from address when database settings are empty', function () {
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'sender_name'  => '',
                'sender_email' => '',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            // Should keep original values
            expect(config('mail.from.name'))->toBe('Example');
            expect(config('mail.from.address'))->toBe('hello@example.com');
        });
    });

    describe('fallback to env', function () {
        it('uses env values when no database settings exist', function () {
            // Clear any email settings
            StoreSetting::query()->where('group', 'email')->delete();
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            // Should keep original values since no settings exist
            expect(config('mail.mailers.smtp.host'))->toBe('127.0.0.1');
            expect(config('mail.mailers.smtp.port'))->toBe(2525);
            expect(config('mail.default'))->toBe('log');
        });

        it('falls back to env when provider not configured in database', function () {
            // Set only sender info, not provider
            $settingsService = app(SettingsService::class);
            $settingsService->saveGroup('email', [
                'sender_name'  => 'Test Store',
                'sender_email' => 'test@store.com',
            ]);
            StoreSetting::clearCache('email');

            EmailServiceProvider::applyEmailSettings();

            // Provider should not change since it wasn't set in database
            expect(config('mail.default'))->toBe('log');
            expect(config('mail.from.name'))->toBe('Test Store');
            expect(config('mail.from.address'))->toBe('test@store.com');
        });
    });

    describe('cache clearing', function () {
        it('applies changes immediately after settings are saved', function () {
            $settingsService = app(SettingsService::class);

            // First configuration
            $settingsService->saveGroup('email', [
                'provider'  => 'smtp',
                'smtp_host' => 'first.example.com',
                'smtp_port' => 587,
            ]);
            StoreSetting::clearCache('email');
            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.mailers.smtp.host'))->toBe('first.example.com');

            // Second configuration
            $settingsService->saveGroup('email', [
                'smtp_host' => 'second.example.com',
                'smtp_port' => 465,
            ]);
            StoreSetting::clearCache('email');
            EmailServiceProvider::applyEmailSettings();

            expect(config('mail.mailers.smtp.host'))->toBe('second.example.com');
            expect(config('mail.mailers.smtp.port'))->toBe(465);
        });
    });
});

describe('EmailSettings component', function () {
    it('clears config cache when saving settings', function () {
        $admin = \App\Domain\Admin\Models\Admin::factory()->create();
        Livewire::actingAs($admin, 'admin');

        Livewire::test(\App\Domain\Admin\Livewire\Settings\EmailSettings::class)
            ->set('sender_name', 'Nova Loja')
            ->set('sender_email', 'nova@loja.com')
            ->set('provider', 'smtp')
            ->set('smtp_host', 'smtp.nova.com')
            ->set('smtp_port', 587)
            ->set('smtp_encryption', 'tls')
            ->call('save')
            ->assertDispatched('settings-saved');

        // Verify settings were saved
        StoreSetting::clearCache('email');
        $settings = StoreSetting::getByGroup('email');
        expect($settings['email.sender_name'])->toBe('Nova Loja');
        expect($settings['email.sender_email'])->toBe('nova@loja.com');
    });

    it('applies email config after saving', function () {
        $admin = \App\Domain\Admin\Models\Admin::factory()->create();
        Livewire::actingAs($admin, 'admin');

        Livewire::test(\App\Domain\Admin\Livewire\Settings\EmailSettings::class)
            ->set('sender_name', 'Config Test Store')
            ->set('sender_email', 'config@test.com')
            ->set('provider', 'smtp')
            ->set('smtp_host', 'smtp.configtest.com')
            ->set('smtp_port', 587)
            ->set('smtp_encryption', 'tls')
            ->call('save');

        // Verify config was applied
        expect(config('mail.default'))->toBe('smtp');
        expect(config('mail.mailers.smtp.host'))->toBe('smtp.configtest.com');
        expect(config('mail.from.name'))->toBe('Config Test Store');
        expect(config('mail.from.address'))->toBe('config@test.com');
    });
});
