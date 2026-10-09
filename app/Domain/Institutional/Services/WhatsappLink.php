<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Services;

class WhatsappLink
{
    public static function make(?string $message = null): string
    {
        $baseUrl = (string) config('institutional.contact.whatsapp', 'https://wa.me/');

        if ($message === null || $message === '') {
            return $baseUrl;
        }

        $separator = str_contains($baseUrl, '?') ? '&' : '?';

        return $baseUrl . $separator . 'text=' . rawurlencode($message);
    }
}
