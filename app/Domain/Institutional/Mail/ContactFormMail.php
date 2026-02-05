<?php

declare(strict_types = 1);

namespace App\Domain\Institutional\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;

class ContactFormMail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $name,
        public readonly ?string $company,
        public readonly string $email,
        public readonly string $topic,
        public readonly string $message,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Contato institucional - {$this->topic}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.institutional.contact-form',
            with: [
                'name'    => $this->name,
                'company' => $this->company,
                'email'   => $this->email,
                'topic'   => $this->topic,
                'message' => $this->message,
            ],
        );
    }
}
