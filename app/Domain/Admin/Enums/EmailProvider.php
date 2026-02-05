<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Enums;

enum EmailProvider: string
{
    case Smtp    = 'smtp';
    case Mailgun = 'mailgun';
    case Resend  = 'resend';

    public function label(): string
    {
        return match ($this) {
            self::Smtp    => 'SMTP',
            self::Mailgun => 'Mailgun',
            self::Resend  => 'Resend',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Smtp    => 'Servidor SMTP tradicional (Gmail, SendGrid SMTP, etc.)',
            self::Mailgun => 'API do Mailgun para envio de emails',
            self::Resend  => 'API do Resend para envio de emails',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $provider) => [$provider->value => $provider->label()])
            ->toArray();
    }
}
