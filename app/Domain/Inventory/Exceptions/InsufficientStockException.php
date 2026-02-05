<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Exceptions;

use App\Domain\Inventory\Contracts\StockableInterface;
use Exception;

class InsufficientStockException extends Exception
{
    public function __construct(
        public readonly StockableInterface $stockable,
        public readonly int $requestedQuantity,
        public readonly int $availableQuantity,
        ?string $message = null,
    ) {
        $message ??= sprintf(
            'Insufficient stock. Requested: %d, Available: %d',
            $requestedQuantity,
            $availableQuantity,
        );

        parent::__construct($message);
    }

    /**
     * Get the stockable item.
     */
    public function getStockable(): StockableInterface
    {
        return $this->stockable;
    }

    /**
     * Get the requested quantity.
     */
    public function getRequestedQuantity(): int
    {
        return $this->requestedQuantity;
    }

    /**
     * Get the available quantity.
     */
    public function getAvailableQuantity(): int
    {
        return $this->availableQuantity;
    }
}
