<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

readonly class ShippingOption
{
    public function __construct(
        public string $code,
        public string $name,
        public int $price,
        public int $deliveryDaysMin,
        public int $deliveryDaysMax,
        public ?string $carrier = null,
        public bool $isFreeShipping = false,
    ) {
    }

    /**
     * Get price formatted in BRL.
     */
    public function formattedPrice(): string
    {
        return 'R$ ' . number_format($this->price / 100, 2, ',', '.');
    }

    /**
     * Get delivery time description.
     */
    public function deliveryTimeDescription(): string
    {
        if ($this->deliveryDaysMin === $this->deliveryDaysMax) {
            return "{$this->deliveryDaysMin} dias uteis";
        }

        return "{$this->deliveryDaysMin} a {$this->deliveryDaysMax} dias uteis";
    }

    /**
     * Get delivery days average for display.
     */
    public function deliveryDays(): int
    {
        return (int) ceil(($this->deliveryDaysMin + $this->deliveryDaysMax) / 2);
    }

    /**
     * Convert to array.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'code'              => $this->code,
            'name'              => $this->name,
            'price'             => $this->price,
            'formatted_price'   => $this->formattedPrice(),
            'delivery_days_min' => $this->deliveryDaysMin,
            'delivery_days_max' => $this->deliveryDaysMax,
            'delivery_days'     => $this->deliveryDays(),
            'delivery_time'     => $this->deliveryTimeDescription(),
            'carrier'           => $this->carrier,
            'is_free_shipping'  => $this->isFreeShipping,
        ];
    }
}
