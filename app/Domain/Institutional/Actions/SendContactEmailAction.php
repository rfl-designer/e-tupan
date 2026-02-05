<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Actions;

use App\Domain\Institutional\Mail\ContactFormMail;
use Illuminate\Support\Facades\Mail;

class SendContactEmailAction
{
    /**
     * @param array{name: string, company: string|null, email: string, topic: string, message: string} $payload
     */
    public function execute(array $payload): void
    {
        $recipient = config('mail.from.address');

        if ($recipient === null || $recipient === '') {
            return;
        }

        Mail::to($recipient)->send(new ContactFormMail(
            name: $payload['name'],
            company: $payload['company'],
            email: $payload['email'],
            topic: $payload['topic'],
            message: $payload['message'],
        ));
    }
}
