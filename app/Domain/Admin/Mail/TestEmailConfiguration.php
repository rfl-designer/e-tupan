<?php

declare(strict_types=1);

namespace App\Domain\Admin\Mail;

use App\Mail\Concerns\QueueableMail;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class TestEmailConfiguration extends Mailable implements ShouldQueue
{
    use QueueableMail;

    public Carbon $sentAt;

    public function __construct(
        public string $storeName,
        public string $driver,
    ) {
        $this->sentAt = now();
        $this->initializeQueueableMail();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Teste de Configuracao - {$this->storeName}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.test-configuration',
            with: [
                'storeName' => $this->storeName,
                'driver' => $this->driver,
                'sentAt' => $this->sentAt,
            ],
        );
    }
}
