<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

readonly class AddressData
{
    public function __construct(
        public string $postalCode,
        public string $street,
        public string $number,
        public ?string $complement,
        public string $neighborhood,
        public string $city,
        public string $stateAbbr,
        public string $country = 'BR',
    ) {
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'postal_code' => preg_replace('/\D/', '', $this->postalCode),
            'address'     => $this->street,
            'number'      => $this->number,
            'complement'  => $this->complement,
            'district'    => $this->neighborhood,
            'city'        => $this->city,
            'state_abbr'  => $this->stateAbbr,
            'country_id'  => $this->country,
        ];
    }
}
