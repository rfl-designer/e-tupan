<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ShippingSetting extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::saved(function () {
            Cache::forget('shipping_settings');
        });

        static::deleted(function () {
            Cache::forget('shipping_settings');
        });
    }

    /**
     * Get a setting value by key.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $settings = self::getAllCached();

        $setting = $settings->firstWhere('key', $key);

        if ($setting === null) {
            return $default;
        }

        return self::castValue($setting->value, $setting->type);
    }

    /**
     * Set a setting value.
     */
    public static function set(string $key, mixed $value, string $type = 'string', string $group = 'general'): self
    {
        $stringValue = self::stringifyValue($value, $type);

        return self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $stringValue,
                'type'  => $type,
                'group' => $group,
            ],
        );
    }

    /**
     * Get settings by group.
     *
     * @return array<string, mixed>
     */
    public static function getGroup(string $group): array
    {
        $settings = self::getAllCached()->where('group', $group);

        $result = [];

        foreach ($settings as $setting) {
            $result[$setting->key] = self::castValue($setting->value, $setting->type);
        }

        return $result;
    }

    /**
     * Get all settings from cache.
     *
     * @return \Illuminate\Support\Collection<int, self>
     */
    public static function getAllCached(): \Illuminate\Support\Collection
    {
        return Cache::remember('shipping_settings', 3600, function () {
            return self::query()->get();
        });
    }

    /**
     * Cast value based on type.
     */
    private static function castValue(?string $value, string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'integer', 'int' => (int) $value,
            'boolean', 'bool' => in_array(strtolower($value), ['true', '1', 'yes']),
            'json', 'array' => json_decode($value, true),
            'float', 'decimal' => (float) $value,
            default => $value,
        };
    }

    /**
     * Convert value to string for storage.
     */
    private static function stringifyValue(mixed $value, string $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => $value ? 'true' : 'false',
            'json', 'array' => json_encode($value),
            default => (string) $value,
        };
    }
}
