<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\DTOs;

readonly class CardData
{
    public function __construct(
        public string $token,
        public string $holderName,
        public int $installments = 1,
        public ?string $cardBrand = null,
        public ?string $lastFourDigits = null,
    ) {
    }

    /**
     * Create from array.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            token: $data['token'],
            holderName: $data['holder_name'] ?? $data['holderName'],
            installments: (int) ($data['installments'] ?? 1),
            cardBrand: $data['card_brand'] ?? $data['cardBrand'] ?? null,
            lastFourDigits: $data['last_four_digits'] ?? $data['lastFourDigits'] ?? null,
        );
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'token'            => $this->token,
            'holder_name'      => $this->holderName,
            'installments'     => $this->installments,
            'card_brand'       => $this->cardBrand,
            'last_four_digits' => $this->lastFourDigits,
        ];
    }
}
