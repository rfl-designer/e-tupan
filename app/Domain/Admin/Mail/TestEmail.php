<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\{Content, Envelope};
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class TestEmail extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $storeName,
        public readonly Carbon $sentAt,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Email de Teste - {$this->storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.admin.test',
            with: [
                'storeName' => $this->storeName,
                'sentAt'    => $this->sentAt->setTimezone('America/Sao_Paulo'),
            ],
        );
    }
}
