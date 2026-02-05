<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Enums;

enum AttributeType: string
{
    case Select = 'select';
    case Color  = 'color';
    case Text   = 'text';

    public function label(): string
    {
        return match ($this) {
            self::Select => 'Seleção',
            self::Color  => 'Cor',
            self::Text   => 'Texto',
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
