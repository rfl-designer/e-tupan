<?php

declare(strict_types = 1);

namespace App\Providers;

use App\Domain\Admin\Models\StoreSetting;
use Illuminate\Support\ServiceProvider;

class EmailServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Apply email configuration from database settings, overriding .env values.
     * Falls back to .env values when no database settings exist.
     */
    public function boot(): void
    {
        // Skip during migrations or when database is not ready
        if ($this->app->runningInConsole() && !$this->app->runningUnitTests()) {
            try {
                if (!\Illuminate\Support\Facades\Schema::hasTable('store_settings')) {
                    return;
                }
            } catch (\Exception) {
                return;
            }
        }

        static::applyEmailSettings();
    }

    /**
     * Apply email settings from database.
     * This is a static method so it can be called from outside the service provider.
     *
     * Only applies settings that are EXPLICITLY saved in database.
     * If no settings exist, falls back to .env values.
     */
    public static function applyEmailSettings(): void
    {
        try {
            // Get only settings that are actually saved in the database
            // This returns an empty array if no settings exist
            $dbSettings = StoreSetting::getByGroup('email');
        } catch (\Exception) {
            // Database not ready
            return;
        }

        // If no settings in database, keep .env values
        if (empty($dbSettings)) {
            return;
        }

        // Convert keys from 'email.key' to 'key'
        $settings = [];

        foreach ($dbSettings as $key => $value) {
            $shortKey            = str_replace('email.', '', $key);
            $settings[$shortKey] = $value;
        }

        // Apply from address if configured
        static::applyFromAddress($settings);

        // Get provider from settings - only if explicitly set
        $provider = $settings['provider'] ?? null;

        if (!$provider) {
            return;
        }

        // Apply provider-specific settings
        match ($provider) {
            'smtp'    => static::applySmtpSettings($settings),
            'mailgun' => static::applyMailgunSettings($settings),
            'resend'  => static::applyResendSettings($settings),
            default   => null,
        };
    }

    /**
     * Apply from address configuration.
     *
     * @param array<string, mixed> $settings
     */
    private static function applyFromAddress(array $settings): void
    {
        $senderName  = $settings['sender_name'] ?? '';
        $senderEmail = $settings['sender_email'] ?? '';

        if ($senderName !== '') {
            config(['mail.from.name' => $senderName]);
        }

        if ($senderEmail !== '') {
            config(['mail.from.address' => $senderEmail]);
        }
    }

    /**
     * Apply SMTP mailer settings.
     *
     * @param array<string, mixed> $settings
     */
    private static function applySmtpSettings(array $settings): void
    {
        config(['mail.default' => 'smtp']);

        $host       = $settings['smtp_host'] ?? '';
        $port       = $settings['smtp_port'] ?? 587;
        $username   = $settings['smtp_username'] ?? '';
        $password   = $settings['smtp_password'] ?? '';
        $encryption = $settings['smtp_encryption'] ?? 'tls';

        if ($host !== '') {
            config(['mail.mailers.smtp.host' => $host]);
        }

        if ($port) {
            config(['mail.mailers.smtp.port' => (int) $port]);
        }

        if ($username !== '') {
            config(['mail.mailers.smtp.username' => $username]);
        }

        if ($password !== '') {
            config(['mail.mailers.smtp.password' => $password]);
        }

        // Handle encryption: 'none' should be null in config
        $encryptionValue = $encryption === 'none' ? null : $encryption;
        config(['mail.mailers.smtp.encryption' => $encryptionValue]);
    }

    /**
     * Apply Mailgun mailer settings.
     *
     * @param array<string, mixed> $settings
     */
    private static function applyMailgunSettings(array $settings): void
    {
        config(['mail.default' => 'mailgun']);

        $domain   = $settings['mailgun_domain'] ?? '';
        $secret   = $settings['mailgun_secret'] ?? '';
        $endpoint = $settings['mailgun_endpoint'] ?? 'api.mailgun.net';

        if ($domain !== '') {
            config(['services.mailgun.domain' => $domain]);
        }

        if ($secret !== '') {
            config(['services.mailgun.secret' => $secret]);
        }

        config(['services.mailgun.endpoint' => $endpoint]);
    }

    /**
     * Apply Resend mailer settings.
     *
     * @param array<string, mixed> $settings
     */
    private static function applyResendSettings(array $settings): void
    {
        config(['mail.default' => 'resend']);

        $apiKey = $settings['resend_api_key'] ?? '';

        if ($apiKey !== '') {
            config(['services.resend.key' => $apiKey]);
        }
    }
}
