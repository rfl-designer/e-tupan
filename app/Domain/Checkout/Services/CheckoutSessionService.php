<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Services;

use Illuminate\Support\Facades\Session;

class CheckoutSessionService
{
    /**
     * Session key prefix.
     */
    protected const PREFIX = 'checkout_';

    /**
     * Session timeout in minutes.
     */
    protected const TIMEOUT_MINUTES = 30;

    /**
     * Save checkout data to session.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): void
    {
        Session::put(self::PREFIX . 'data', $data);
        Session::put(self::PREFIX . 'timestamp', now()->timestamp);
    }

    /**
     * Get checkout data from session.
     *
     * @return array<string, mixed>
     */
    public function get(): array
    {
        if ($this->isExpired()) {
            $this->clear();

            return [];
        }

        return Session::get(self::PREFIX . 'data', []);
    }

    /**
     * Clear checkout data from session.
     */
    public function clear(): void
    {
        Session::forget(self::PREFIX . 'data');
        Session::forget(self::PREFIX . 'timestamp');
    }

    /**
     * Check if checkout data has expired.
     */
    public function isExpired(): bool
    {
        $timestamp = Session::get(self::PREFIX . 'timestamp');

        if ($timestamp === null) {
            return true;
        }

        $expiresAt = $timestamp + (self::TIMEOUT_MINUTES * 60);

        return now()->timestamp > $expiresAt;
    }

    /**
     * Save data for a specific step.
     *
     * @param  array<string, mixed>  $data
     */
    public function saveStep(string $step, array $data): void
    {
        $currentData        = Session::get(self::PREFIX . 'data', []);
        $currentData[$step] = $data;

        Session::put(self::PREFIX . 'data', $currentData);
        Session::put(self::PREFIX . 'timestamp', now()->timestamp);
    }

    /**
     * Get data for a specific step.
     *
     * @param  array<string, mixed>  $default
     * @return array<string, mixed>
     */
    public function getStep(string $step, array $default = []): array
    {
        $data = $this->get();

        return $data[$step] ?? $default;
    }

    /**
     * Check if checkout has valid data.
     */
    public function hasData(): bool
    {
        if ($this->isExpired()) {
            return false;
        }

        return Session::has(self::PREFIX . 'data');
    }

    /**
     * Refresh the session timestamp.
     */
    public function refreshTimestamp(): void
    {
        if (Session::has(self::PREFIX . 'data')) {
            Session::put(self::PREFIX . 'timestamp', now()->timestamp);
        }
    }
}
