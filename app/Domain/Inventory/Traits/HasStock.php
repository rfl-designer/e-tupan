<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Traits;

use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasStock
{
    /**
     * Get the current stock quantity.
     */
    public function getStockQuantity(): int
    {
        return (int) $this->stock_quantity;
    }

    /**
     * Set the stock quantity.
     */
    public function setStockQuantity(int $quantity): void
    {
        $this->stock_quantity = $quantity;
    }

    /**
     * Get the low stock threshold.
     */
    public function getLowStockThreshold(): int
    {
        // Use product-specific threshold, or fall back to config default
        return (int) ($this->low_stock_threshold ?? config('inventory.default_low_stock_threshold', 5));
    }

    /**
     * Check if stock is being managed.
     */
    public function isManagingStock(): bool
    {
        // For ProductVariant, delegate to parent product
        if (property_exists($this, 'product_id') && $this->product_id !== null) {
            return $this->product->manage_stock ?? true;
        }

        return (bool) ($this->manage_stock ?? true);
    }

    /**
     * Check if backorders are allowed.
     */
    public function allowsBackorders(): bool
    {
        // For ProductVariant, delegate to parent product
        if (property_exists($this, 'product_id') && $this->product_id !== null) {
            return $this->product->allow_backorders ?? false;
        }

        return (bool) ($this->allow_backorders ?? false);
    }

    /**
     * Check if the product is low on stock.
     */
    public function isLowStock(): bool
    {
        if (!$this->isManagingStock()) {
            return false;
        }

        $threshold = $this->getLowStockThreshold();

        return $this->getStockQuantity() > 0 && $this->getStockQuantity() <= $threshold;
    }

    /**
     * Get the stockable type for polymorphic relationship.
     */
    public function getStockableType(): string
    {
        return static::class;
    }

    /**
     * Get the stockable ID for polymorphic relationship.
     */
    public function getStockableId(): int
    {
        return (int) $this->getKey();
    }

    /**
     * Get the stock movements relationship.
     */
    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'stockable');
    }

    /**
     * Get the stock reservations relationship.
     */
    public function stockReservations(): MorphMany
    {
        return $this->morphMany(StockReservation::class, 'stockable');
    }

    /**
     * Get active (non-expired, non-converted) reservations.
     */
    public function activeReservations(): MorphMany
    {
        return $this->stockReservations()
            ->where('expires_at', '>', now())
            ->whereNull('converted_at');
    }

    /**
     * Get the total reserved quantity.
     */
    public function getReservedQuantity(): int
    {
        return (int) $this->activeReservations()->sum('quantity');
    }

    /**
     * Get available quantity (stock - reserved).
     */
    public function getAvailableQuantity(): int
    {
        return max(0, $this->getStockQuantity() - $this->getReservedQuantity());
    }
}
