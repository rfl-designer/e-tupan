<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\DTOs;

readonly class StockValidationResult
{
    /**
     * @param  array<int, StockValidationItem>  $items
     */
    public function __construct(
        public bool $valid,
        public array $items,
    ) {
    }

    /**
     * Check if the validation passed (all items available).
     */
    public function isValid(): bool
    {
        return $this->valid;
    }

    /**
     * Get all validation items.
     *
     * @return array<int, StockValidationItem>
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get items that are unavailable (insufficient stock).
     *
     * @return array<int, StockValidationItem>
     */
    public function getUnavailableItems(): array
    {
        return array_filter(
            $this->items,
            fn (StockValidationItem $item) => !$item->isAvailable,
        );
    }

    /**
     * Get items that are available.
     *
     * @return array<int, StockValidationItem>
     */
    public function getAvailableItems(): array
    {
        return array_filter(
            $this->items,
            fn (StockValidationItem $item) => $item->isAvailable,
        );
    }

    /**
     * Get items that can be partially fulfilled.
     *
     * @return array<int, StockValidationItem>
     */
    public function getPartiallyFulfillableItems(): array
    {
        return array_filter(
            $this->items,
            fn (StockValidationItem $item) => $item->canPartiallyFulfill(),
        );
    }

    /**
     * Get the total count of unavailable items.
     */
    public function getUnavailableCount(): int
    {
        return count($this->getUnavailableItems());
    }

    /**
     * Check if any items can be partially fulfilled.
     */
    public function hasPartiallyFulfillableItems(): bool
    {
        return count($this->getPartiallyFulfillableItems()) > 0;
    }

    /**
     * Get error messages for unavailable items.
     *
     * @return array<int, string>
     */
    public function getErrorMessages(): array
    {
        return array_values(array_filter(
            array_map(
                fn (StockValidationItem $item) => $item->message,
                $this->getUnavailableItems(),
            ),
        ));
    }

    /**
     * Convert to array for API responses.
     *
     * @return array{
     *     valid: bool,
     *     items: array<int, array<string, mixed>>,
     *     unavailable_count: int,
     *     error_messages: array<int, string>
     * }
     */
    public function toArray(): array
    {
        return [
            'valid' => $this->valid,
            'items' => array_map(
                fn (StockValidationItem $item) => $item->toArray(),
                $this->items,
            ),
            'unavailable_count' => $this->getUnavailableCount(),
            'error_messages'    => $this->getErrorMessages(),
        ];
    }

    /**
     * Create a successful validation result.
     *
     * @param  array<int, StockValidationItem>  $items
     */
    public static function success(array $items): self
    {
        return new self(valid: true, items: $items);
    }

    /**
     * Create a failed validation result.
     *
     * @param  array<int, StockValidationItem>  $items
     */
    public static function failure(array $items): self
    {
        return new self(valid: false, items: $items);
    }
}
