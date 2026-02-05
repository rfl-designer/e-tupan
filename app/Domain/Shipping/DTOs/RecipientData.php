<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

readonly class RecipientData
{
    public function __construct(
        public string $name,
        public ?string $phone = null,
        public ?string $email = null,
        public ?string $document = null,
    ) {
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'name'     => $this->name,
            'phone'    => $this->phone,
            'email'    => $this->email,
            'document' => $this->document,
        ]);
    }
}
