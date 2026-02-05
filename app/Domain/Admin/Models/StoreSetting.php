<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Models;

use App\Domain\Admin\Enums\SettingType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Cache, Crypt};

class StoreSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
    ];

    protected function casts(): array
    {
        return [
            'type' => SettingType::class,
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember(
            "store_setting:{$key}",
            now()->addHour(),
            fn () => static::query()->where('key', $key)->first(),
        );

        if (!$setting) {
            return $default;
        }

        return $setting->castValue();
    }

    public static function set(string $key, mixed $value, SettingType $type = SettingType::String, string $group = 'general'): static
    {
        $storedValue = match ($type) {
            SettingType::Json      => is_string($value) ? $value : json_encode($value),
            SettingType::Boolean   => $value ? '1' : '0',
            SettingType::Encrypted => $value !== '' ? Crypt::encryptString((string) $value) : '',
            default                => (string) $value,
        };

        $setting = static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $storedValue,
                'type'  => $type,
                'group' => $group,
            ],
        );

        Cache::forget("store_setting:{$key}");
        Cache::forget("store_settings:{$group}");

        return $setting;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getByGroup(string $group): array
    {
        return Cache::remember(
            "store_settings:{$group}",
            now()->addHour(),
            fn () => static::query()
                ->where('group', $group)
                ->get()
                ->mapWithKeys(fn (self $setting) => [$setting->key => $setting->castValue()])
                ->toArray(),
        );
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            SettingType::Integer   => (int) $this->value,
            SettingType::Boolean   => (bool) $this->value,
            SettingType::Json      => json_decode($this->value, true),
            SettingType::Encrypted => $this->decryptValue(),
            default                => $this->value,
        };
    }

    private function decryptValue(): string
    {
        if ($this->value === '' || $this->value === null) {
            return '';
        }

        try {
            return Crypt::decryptString($this->value);
        } catch (\Exception) {
            return '';
        }
    }

    public static function clearCache(?string $group = null): void
    {
        if ($group) {
            Cache::forget("store_settings:{$group}");
            static::query()->where('group', $group)->get()->each(
                fn (self $setting) => Cache::forget("store_setting:{$setting->key}"),
            );

            return;
        }

        static::query()->get()->each(function (self $setting) {
            Cache::forget("store_setting:{$setting->key}");
            Cache::forget("store_settings:{$setting->group}");
        });
    }

    public static function hasEmailConfiguration(): bool
    {
        $driver = static::get('email.driver');

        return filled($driver) && $driver !== 'log';
    }
}
