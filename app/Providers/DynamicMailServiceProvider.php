<?php

declare(strict_types=1);

namespace App\Providers;

use App\Domain\Admin\Models\StoreSetting;
use App\Domain\Admin\Services\MailConfigurationService;
use Illuminate\Support\ServiceProvider;

class DynamicMailServiceProvider extends ServiceProvider
{
    private const array EXCLUDED_COMMANDS = [
        'migrate',
        'db:seed',
        'config:cache',
        'config:clear',
        'optimize',
        'package:discover',
    ];

    public function register(): void
    {
        $this->app->singleton(MailConfigurationService::class);
    }

    public function boot(): void
    {
        if (! $this->shouldConfigureDynamically()) {
            return;
        }

        $settings = StoreSetting::getByGroup('email');
        $driver = $settings['email.driver'] ?? null;

        if (! $driver || $driver === 'log') {
            return;
        }

        $this->app->make(MailConfigurationService::class)
            ->applyConfiguration($this->normalizeSettings($settings));
    }

    private function shouldConfigureDynamically(): bool
    {
        if ($this->isExcludedConsoleCommand()) {
            return false;
        }

        try {
            return StoreSetting::hasEmailConfiguration();
        } catch (\Throwable) {
            return false;
        }
    }

    private function isExcludedConsoleCommand(): bool
    {
        if (! $this->app->runningInConsole() || $this->app->runningUnitTests()) {
            return false;
        }

        /** @var array<int, string> $argv */
        $argv = $_SERVER['argv'] ?? [];
        $command = $argv[1] ?? '';

        foreach (self::EXCLUDED_COMMANDS as $excluded) {
            if (str_contains($command, $excluded)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Normalize settings keys from "email.key" to "key" format.
     *
     * @param  array<string, mixed>  $settings
     * @return array<string, mixed>
     */
    private function normalizeSettings(array $settings): array
    {
        $normalized = [];

        foreach ($settings as $key => $value) {
            $shortKey = str_starts_with($key, 'email.') ? substr($key, 6) : $key;
            $normalized[$shortKey] = $value;
        }

        return $normalized;
    }
}
