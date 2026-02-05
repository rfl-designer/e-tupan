<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

readonly class PackageData
{
    public function __construct(
        public float $weight,
        public int $height,
        public int $width,
        public int $length,
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
            'weight' => $this->weight,
            'height' => $this->height,
            'width'  => $this->width,
            'length' => $this->length,
        ];
    }
}
