<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Enums\SettingType;
use App\Domain\Admin\Models\StoreSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class SettingsService
{
    /**
     * Default settings structure.
     *
     * @var array<string, array<string, array{default: mixed, type: SettingType}>>
     */
    private array $defaults = [
        'general' => [
            'store_name'    => ['default' => '', 'type' => SettingType::String],
            'store_email'   => ['default' => '', 'type' => SettingType::String],
            'store_phone'   => ['default' => '', 'type' => SettingType::String],
            'store_cnpj'    => ['default' => '', 'type' => SettingType::String],
            'store_address' => ['default' => '', 'type' => SettingType::String],
            'store_logo'    => ['default' => '', 'type' => SettingType::File],
            'store_favicon' => ['default' => '', 'type' => SettingType::File],
            'primary_color' => ['default' => '#059669', 'type' => SettingType::String],
        ],
        'checkout' => [
            'guest_checkout_enabled'    => ['default' => true, 'type' => SettingType::Boolean],
            'cpf_required'              => ['default' => true, 'type' => SettingType::Boolean],
            'phone_required'            => ['default' => true, 'type' => SettingType::Boolean],
            'stock_reservation_minutes' => ['default' => 30, 'type' => SettingType::Integer],
            'checkout_message'          => ['default' => '', 'type' => SettingType::String],
        ],
        'stock' => [
            'low_stock_threshold'   => ['default' => 5, 'type' => SettingType::Integer],
            'allow_backorders'      => ['default' => false, 'type' => SettingType::Boolean],
            'stock_alert_email'     => ['default' => '', 'type' => SettingType::String],
            'stock_alert_frequency' => ['default' => 'daily', 'type' => SettingType::String],
        ],
        'payment' => [
            'active_gateway'      => ['default' => 'mercadopago', 'type' => SettingType::String],
            'payment_environment' => ['default' => 'sandbox', 'type' => SettingType::String],
        ],
        'email' => [
            'sender_name'  => ['default' => '', 'type' => SettingType::String],
            'sender_email' => ['default' => '', 'type' => SettingType::String],
            'provider'     => ['default' => 'smtp', 'type' => SettingType::String],
            // SMTP
            'smtp_host'       => ['default' => '', 'type' => SettingType::String],
            'smtp_port'       => ['default' => 587, 'type' => SettingType::Integer],
            'smtp_username'   => ['default' => '', 'type' => SettingType::String],
            'smtp_password'   => ['default' => '', 'type' => SettingType::Encrypted],
            'smtp_encryption' => ['default' => 'tls', 'type' => SettingType::String],
            // Mailgun
            'mailgun_domain'   => ['default' => '', 'type' => SettingType::String],
            'mailgun_secret'   => ['default' => '', 'type' => SettingType::Encrypted],
            'mailgun_endpoint' => ['default' => 'api.mailgun.net', 'type' => SettingType::String],
            // Resend
            'resend_key' => ['default' => '', 'type' => SettingType::Encrypted],
            // Order status email notifications (default: all enabled)
            'notify_status_processing' => ['default' => true, 'type' => SettingType::Boolean],
            'notify_status_shipped' => ['default' => true, 'type' => SettingType::Boolean],
            'notify_status_completed' => ['default' => true, 'type' => SettingType::Boolean],
            'notify_status_cancelled' => ['default' => true, 'type' => SettingType::Boolean],
            'notify_status_refunded' => ['default' => true, 'type' => SettingType::Boolean],
        ],
    ];

    public function get(string $key, mixed $default = null): mixed
    {
        $value = StoreSetting::get($key);

        if ($value !== null) {
            return $value;
        }

        [$group, $settingKey] = $this->parseKey($key);

        if (isset($this->defaults[$group][$settingKey])) {
            return $this->defaults[$group][$settingKey]['default'];
        }

        return $default;
    }

    public function set(string $key, mixed $value): void
    {
        [$group, $settingKey] = $this->parseKey($key);

        $type = $this->getSettingType($group, $settingKey);

        StoreSetting::set($key, $value, $type, $group);
    }

    /**
     * @return array<string, mixed>
     */
    public function getByGroup(string $group): array
    {
        $saved    = StoreSetting::getByGroup($group);
        $defaults = $this->defaults[$group] ?? [];

        $result = [];

        foreach ($defaults as $key => $config) {
            $fullKey      = "{$group}.{$key}";
            $result[$key] = $saved[$fullKey] ?? $config['default'];
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function saveGroup(string $group, array $data): void
    {
        foreach ($data as $key => $value) {
            $fullKey = "{$group}.{$key}";
            $this->set($fullKey, $value);
        }
    }

    public function uploadFile(string $key, UploadedFile $file): string
    {
        $oldValue = $this->get($key);

        if ($oldValue && Storage::disk('public')->exists($oldValue)) {
            Storage::disk('public')->delete($oldValue);
        }

        $path = $file->store('settings', 'public');
        $this->set($key, $path);

        return $path;
    }

    public function deleteFile(string $key): void
    {
        $path = $this->get($key);

        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
        $this->set($key, '');
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function parseKey(string $key): array
    {
        $parts = explode('.', $key, 2);

        return count($parts) === 2 ? $parts : ['general', $key];
    }

    private function getSettingType(string $group, string $key): SettingType
    {
        return $this->defaults[$group][$key]['type'] ?? SettingType::String;
    }

    /**
     * @return array<int, string>
     */
    public function getGroups(): array
    {
        return array_keys($this->defaults);
    }

    public function clearCache(): void
    {
        StoreSetting::clearCache();
    }
}
