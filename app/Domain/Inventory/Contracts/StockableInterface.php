<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;

interface StockableInterface
{
    /**
     * Get the current stock quantity.
     */
    public function getStockQuantity(): int;

    /**
     * Set the stock quantity.
     */
    public function setStockQuantity(int $quantity): void;

    /**
     * Get the low stock threshold.
     */
    public function getLowStockThreshold(): int;

    /**
     * Check if stock is being managed.
     */
    public function isManagingStock(): bool;

    /**
     * Check if backorders are allowed.
     */
    public function allowsBackorders(): bool;

    /**
     * Check if the product is in stock.
     */
    public function isInStock(): bool;

    /**
     * Check if the product is low on stock.
     */
    public function isLowStock(): bool;

    /**
     * Get the stockable type for polymorphic relationship.
     */
    public function getStockableType(): string;

    /**
     * Get the stockable ID for polymorphic relationship.
     */
    public function getStockableId(): int;

    /**
     * Get the stock movements relationship.
     */
    public function stockMovements(): MorphMany;

    /**
     * Get the stock reservations relationship.
     */
    public function stockReservations(): MorphMany;
}
