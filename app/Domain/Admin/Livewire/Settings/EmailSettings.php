<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Enums\EmailProvider;
use App\Domain\Admin\Jobs\SendTestEmailJob;
use App\Domain\Admin\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Validate;
use Livewire\Component;

class EmailSettings extends Component
{
    private const array SETTING_KEYS = [
        'sender_name',
        'sender_email',
        'driver',
        'smtp_host',
        'smtp_port',
        'smtp_username',
        'smtp_password',
        'smtp_encryption',
        'mailgun_domain',
        'mailgun_secret',
        'mailgun_endpoint',
        'ses_key',
        'ses_secret',
        'ses_region',
        'postmark_token',
        'resend_key',
        'notify_status_processing',
        'notify_status_shipped',
        'notify_status_completed',
        'notify_status_cancelled',
        'notify_status_refunded',
    ];

    private const array ENCRYPTION_OPTIONS = [
        'tls' => 'TLS',
        'ssl' => 'SSL',
        '' => 'Nenhuma',
    ];

    private const array MAILGUN_ENDPOINTS = [
        'api.mailgun.net' => 'US (api.mailgun.net)',
        'api.eu.mailgun.net' => 'EU (api.eu.mailgun.net)',
    ];

    private const array SES_REGIONS = [
        'us-east-1' => 'US East (N. Virginia)',
        'us-east-2' => 'US East (Ohio)',
        'us-west-1' => 'US West (N. California)',
        'us-west-2' => 'US West (Oregon)',
        'eu-west-1' => 'EU (Ireland)',
        'eu-west-2' => 'EU (London)',
        'eu-central-1' => 'EU (Frankfurt)',
        'sa-east-1' => 'South America (Sao Paulo)',
    ];

    public string $sender_name = '';

    #[Validate('required|email|max:255')]
    public string $sender_email = '';

    #[Validate('required|in:smtp,mailgun,resend')]
    public string $provider = 'smtp';

    // SMTP
    #[Validate('required_if:provider,smtp|nullable|string|max:255')]
    public string $smtp_host = '';

    #[Validate('required_if:provider,smtp|nullable|integer|min:1|max:65535')]
    public int $smtp_port = 587;

    #[Validate('nullable|string|max:255')]
    public string $smtp_username = '';

    #[Validate('nullable|string|max:255')]
    public string $smtp_password = '';

    #[Validate('required_if:provider,smtp|nullable|in:tls,ssl,none')]
    public string $smtp_encryption = 'tls';

    // Mailgun
    #[Validate('required_if:provider,mailgun|nullable|string|max:255')]
    public string $mailgun_domain = '';

    #[Validate('required_if:provider,mailgun|nullable|string|max:255')]
    public string $mailgun_secret = '';

    #[Validate('required_if:provider,mailgun|nullable|in:api.mailgun.net,api.eu.mailgun.net')]
    public string $mailgun_endpoint = 'api.mailgun.net';

    // Resend
    #[Validate('required_if:provider,resend|nullable|string|max:255')]
    public string $resend_api_key = '';

    // Test Email Modal
    public bool $showTestEmailModal = false;

    public string $resend_key = '';

    public bool $notify_status_processing = true;

    public bool $notify_status_shipped = true;

    public bool $notify_status_completed = true;

    public bool $notify_status_cancelled = true;

    public bool $notify_status_refunded = true;

    public string $testEmailRecipient = '';

    public function mount(SettingsService $settingsService): void
    {
        $settings = $settingsService->getByGroup('email');

        $this->sender_name      = $settings['sender_name'] ?? '';
        $this->sender_email     = $settings['sender_email'] ?? '';
        $this->provider         = $settings['provider'] ?? 'smtp';
        $this->smtp_host        = $settings['smtp_host'] ?? '';
        $this->smtp_port        = (int) ($settings['smtp_port'] ?? 587);
        $this->smtp_username    = $settings['smtp_username'] ?? '';
        $this->smtp_password    = $settings['smtp_password'] ?? '';
        $this->smtp_encryption  = $settings['smtp_encryption'] ?? 'tls';
        $this->mailgun_domain   = $settings['mailgun_domain'] ?? '';
        $this->mailgun_secret   = $settings['mailgun_secret'] ?? '';
        $this->mailgun_endpoint = $settings['mailgun_endpoint'] ?? 'api.mailgun.net';
        $this->resend_api_key   = $settings['resend_api_key'] ?? '';
    }

    public function save(SettingsService $settingsService): void
    {
        $this->validate();

        $data = [
            'sender_name'      => $this->sender_name,
            'sender_email'     => $this->sender_email,
            'provider'         => $this->provider,
            'smtp_host'        => $this->smtp_host,
            'smtp_port'        => $this->smtp_port,
            'smtp_username'    => $this->smtp_username,
            'smtp_encryption'  => $this->smtp_encryption,
            'mailgun_domain'   => $this->mailgun_domain,
            'mailgun_endpoint' => $this->mailgun_endpoint,
        ];

        // Only update sensitive fields if they have a value
        if ($this->smtp_password !== '') {
            $data['smtp_password'] = $this->smtp_password;
        }

        if ($this->mailgun_secret !== '') {
            $data['mailgun_secret'] = $this->mailgun_secret;
        }

        if ($this->resend_api_key !== '') {
            $data['resend_api_key'] = $this->resend_api_key;
        }

        $settingsService->saveGroup('email', $data);

        // Clear settings cache and reapply email configuration
        $settingsService->clearCache();
        \App\Providers\EmailServiceProvider::applyEmailSettings();

        $this->dispatch('settings-saved');
    }

    /**
     * @return array<string, string>
     */
    public function getProviderOptionsProperty(): array
    {
        return EmailProvider::options();
    }

    /**
     * @return array<string, string>
     */
    public function getEncryptionOptionsProperty(): array
    {
        return [
            'tls'  => 'TLS',
            'ssl'  => 'SSL',
            'none' => 'Nenhum',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function getMailgunEndpointOptionsProperty(): array
    {
        return [
            'api.mailgun.net'    => 'Estados Unidos (api.mailgun.net)',
            'api.eu.mailgun.net' => 'Europa (api.eu.mailgun.net)',
        ];
    }

    public function openTestEmailModal(): void
    {
        $this->showTestEmailModal = true;
    }

    public function closeTestEmailModal(): void
    {
        $this->showTestEmailModal = false;
        $this->testEmail          = '';
        $this->resetValidation('testEmail');
    }

    public function sendTestEmail(): void
    {
        $this->validate([
            'sender_name'  => 'required|string|max:255',
            'sender_email' => 'required|email|max:255',
            'testEmail'    => 'required|email|max:255',
        ]);

        SendTestEmailJob::dispatch($this->testEmail);

        $this->testEmail          = '';
        $this->showTestEmailModal = false;

        $this->dispatch('test-email-sent');
    }

    public function render(): View
    {
        return view('livewire.admin.settings.email-settings', [
            'drivers' => EmailDriver::options(),
            'encryptionOptions' => self::ENCRYPTION_OPTIONS,
            'mailgunEndpoints' => self::MAILGUN_ENDPOINTS,
            'sesRegions' => self::SES_REGIONS,
        ]);
    }
}
