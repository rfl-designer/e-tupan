<?php

declare(strict_types=1);

namespace App\Domain\Admin\Services;

use Illuminate\Support\Facades\Config;

/**
 * Centralizes mail configuration logic for runtime configuration.
 */
class MailConfigurationService
{
    /**
     * @param  array<string, mixed>  $settings
     */
    public function applyConfiguration(array $settings): void
    {
        $this->applyFromAddress($settings);

        $driver = (string) ($settings['driver'] ?? 'log');
        Config::set('mail.default', $driver);

        $this->applyDriverConfig($driver, $settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyFromAddress(array $settings): void
    {
        if (! empty($settings['sender_email'])) {
            Config::set('mail.from.address', $settings['sender_email']);
        }

        if (! empty($settings['sender_name'])) {
            Config::set('mail.from.name', $settings['sender_name']);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyDriverConfig(string $driver, array $settings): void
    {
        match ($driver) {
            'smtp' => $this->applySmtpConfig($settings),
            'mailgun' => $this->applyMailgunConfig($settings),
            'ses' => $this->applySesConfig($settings),
            'postmark' => $this->applyPostmarkConfig($settings),
            'resend' => $this->applyResendConfig($settings),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applySmtpConfig(array $settings): void
    {
        Config::set('mail.mailers.smtp.host', $settings['smtp_host'] ?? '');
        Config::set('mail.mailers.smtp.port', (int) ($settings['smtp_port'] ?? '587'));
        Config::set('mail.mailers.smtp.username', $settings['smtp_username'] ?? '');
        Config::set('mail.mailers.smtp.password', $settings['smtp_password'] ?? '');

        $encryption = (string) ($settings['smtp_encryption'] ?? 'tls');
        if ($encryption !== '') {
            Config::set('mail.mailers.smtp.encryption', $encryption);
        }
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyMailgunConfig(array $settings): void
    {
        Config::set('services.mailgun.domain', $settings['mailgun_domain'] ?? '');
        Config::set('services.mailgun.secret', $settings['mailgun_secret'] ?? '');
        Config::set('services.mailgun.endpoint', $settings['mailgun_endpoint'] ?? 'api.mailgun.net');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applySesConfig(array $settings): void
    {
        Config::set('services.ses.key', $settings['ses_key'] ?? '');
        Config::set('services.ses.secret', $settings['ses_secret'] ?? '');
        Config::set('services.ses.region', $settings['ses_region'] ?? 'us-east-1');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyPostmarkConfig(array $settings): void
    {
        Config::set('services.postmark.key', $settings['postmark_token'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function applyResendConfig(array $settings): void
    {
        Config::set('services.resend.key', $settings['resend_key'] ?? '');
    }
}
