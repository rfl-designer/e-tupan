<?php

declare(strict_types=1);

namespace App\Domain\Admin\Enums;

enum EmailDriver: string
{
    case Log = 'log';
    case Smtp = 'smtp';
    case Mailgun = 'mailgun';
    case Ses = 'ses';
    case Postmark = 'postmark';
    case Resend = 'resend';

    public function label(): string
    {
        return match ($this) {
            self::Log => 'Log (Desenvolvimento)',
            self::Smtp => 'SMTP',
            self::Mailgun => 'Mailgun',
            self::Ses => 'Amazon SES',
            self::Postmark => 'Postmark',
            self::Resend => 'Resend',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $case): array => [$case->value => $case->label()])
            ->all();
    }
}
