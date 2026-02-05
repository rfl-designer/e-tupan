<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Enums;

enum ActivityAction: string
{
    case Created       = 'created';
    case Updated       = 'updated';
    case Deleted       = 'deleted';
    case Restored      = 'restored';
    case StatusChanged = 'status_changed';
    case LoggedIn      = 'logged_in';
    case LoggedOut     = 'logged_out';
    case Exported      = 'exported';
    case Imported      = 'imported';
    case Refunded      = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Created       => 'Criou',
            self::Updated       => 'Atualizou',
            self::Deleted       => 'Excluiu',
            self::Restored      => 'Restaurou',
            self::StatusChanged => 'Alterou status',
            self::LoggedIn      => 'Fez login',
            self::LoggedOut     => 'Fez logout',
            self::Exported      => 'Exportou',
            self::Imported      => 'Importou',
            self::Refunded      => 'Reembolsou',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Created       => 'plus-circle',
            self::Updated       => 'pencil',
            self::Deleted       => 'trash',
            self::Restored      => 'arrow-uturn-left',
            self::StatusChanged => 'arrow-path',
            self::LoggedIn      => 'arrow-right-end-on-rectangle',
            self::LoggedOut     => 'arrow-right-start-on-rectangle',
            self::Exported      => 'arrow-down-tray',
            self::Imported      => 'arrow-up-tray',
            self::Refunded      => 'receipt-refund',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Created       => 'green',
            self::Updated       => 'blue',
            self::Deleted       => 'red',
            self::Restored      => 'purple',
            self::StatusChanged => 'orange',
            self::LoggedIn      => 'cyan',
            self::LoggedOut     => 'gray',
            self::Exported      => 'indigo',
            self::Imported      => 'teal',
            self::Refunded      => 'yellow',
        };
    }
}
