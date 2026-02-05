<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\DTOs;

use App\Domain\Inventory\Contracts\StockableInterface;

readonly class StockValidationItem
{
    public function __construct(
        public StockableInterface $stockable,
        public int $requestedQuantity,
        public int $availableQuantity,
        public bool $isAvailable,
        public ?string $message = null,
    ) {
    }

    /**
     * Get the shortage quantity (how many items are missing).
     */
    public function getShortage(): int
    {
        if ($this->isAvailable) {
            return 0;
        }

        return $this->requestedQuantity - $this->availableQuantity;
    }

    /**
     * Get the maximum quantity that can be fulfilled.
     */
    public function getFulfillableQuantity(): int
    {
        return min($this->requestedQuantity, $this->availableQuantity);
    }

    /**
     * Check if partial fulfillment is possible.
     */
    public function canPartiallyFulfill(): bool
    {
        return !$this->isAvailable && $this->availableQuantity > 0;
    }

    /**
     * Convert to array for API responses.
     *
     * @return array{
     *     stockable_type: string,
     *     stockable_id: int,
     *     requested_quantity: int,
     *     available_quantity: int,
     *     is_available: bool,
     *     message: string|null,
     *     shortage: int,
     *     can_partially_fulfill: bool
     * }
     */
    public function toArray(): array
    {
        return [
            'stockable_type'        => $this->stockable->getStockableType(),
            'stockable_id'          => $this->stockable->getStockableId(),
            'requested_quantity'    => $this->requestedQuantity,
            'available_quantity'    => $this->availableQuantity,
            'is_available'          => $this->isAvailable,
            'message'               => $this->message,
            'shortage'              => $this->getShortage(),
            'can_partially_fulfill' => $this->canPartiallyFulfill(),
        ];
    }
}
