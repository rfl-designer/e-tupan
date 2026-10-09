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
        public readonly ?string $role = null,
        public readonly ?string $location = null,
        public readonly ?string $phone = null,
        public readonly ?string $origin = null,
        public readonly ?string $subjectLine = null,
    ) {
    }

    public function envelope(): Envelope
    {
        $subject = $this->subjectLine !== null && $this->subjectLine !== ''
            ? "{$this->subjectLine} - {$this->topic}"
            : "Contato institucional - {$this->topic}";

        return new Envelope(
            subject: $subject,
            tags: $this->origin !== null ? ["origem:{$this->origin}"] : [],
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.institutional.contact-form',
            with: [
                'name'     => $this->name,
                'company'  => $this->company,
                'email'    => $this->email,
                'topic'    => $this->topic,
                'message'  => $this->message,
                'role'     => $this->role,
                'location' => $this->location,
                'phone'    => $this->phone,
                'origin'   => $this->origin,
            ],
        );
    }
}
