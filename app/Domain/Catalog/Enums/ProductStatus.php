<?php declare(strict_types = 1);

namespace App\Domain\Catalog\Enums;

enum ProductStatus: string
{
    case Draft    = 'draft';
    case Active   = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft    => 'Rascunho',
            self::Active   => 'Ativo',
            self::Inactive => 'Inativo',
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
