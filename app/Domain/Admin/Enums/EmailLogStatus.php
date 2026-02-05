<?php

declare(strict_types=1);

namespace App\Domain\Admin\Enums;

enum EmailLogStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendente',
            self::Sent => 'Enviado',
            self::Failed => 'Falhou',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'zinc',
            self::Sent => 'green',
            self::Failed => 'red',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Pending => 'clock',
            self::Sent => 'check-circle',
            self::Failed => 'x-circle',
        };
    }
}
