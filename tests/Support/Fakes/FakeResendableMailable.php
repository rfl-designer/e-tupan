<?php

declare(strict_types=1);

namespace Tests\Support\Fakes;

use App\Mail\Concerns\QueueableMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class FakeResendableMailable extends Mailable implements ShouldQueue
{
    use QueueableMail;

    public function __construct()
    {
        $this->initializeQueueableMail();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Fake Resendable Email',
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<p>Fake email content for testing resend functionality.</p>',
        );
    }
}
