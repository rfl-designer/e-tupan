<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Enums;

enum CartStatus: string
{
    case Active    = 'active';
    case Abandoned = 'abandoned';
    case Converted = 'converted';

    public function label(): string
    {
        return match ($this) {
            self::Active    => 'Ativo',
            self::Abandoned => 'Abandonado',
            self::Converted => 'Convertido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active    => 'lime',
            self::Abandoned => 'amber',
            self::Converted => 'sky',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case) => [$case->value => $case->label()])
            ->toArray();
    }
}
