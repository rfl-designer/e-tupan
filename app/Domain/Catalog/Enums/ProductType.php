<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Enums;

enum ProductType: string
{
    case Simple   = 'simple';
    case Variable = 'variable';

    public function label(): string
    {
        return match ($this) {
            self::Simple   => 'Produto Simples',
            self::Variable => 'Produto Variável',
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
