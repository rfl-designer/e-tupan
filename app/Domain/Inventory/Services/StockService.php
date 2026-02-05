<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Services;

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Contracts\StockableInterface;
use App\Domain\Inventory\DTOs\{StockValidationItem, StockValidationResult};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Jobs\SendLowStockAlertsJob;
use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\{Auth, DB, Log};

class StockService
{
    /**
     * Adjust stock for a stockable item.
     *
     * @throws InsufficientStockException
     */
    public function adjust(
        StockableInterface $stockable,
        int $quantity,
        MovementType $type,
        ?string $notes = null,
        ?Model $reference = null,
    ): StockMovement {
        return DB::transaction(function () use ($stockable, $quantity, $type, $notes, $reference) {
            /** @var Model&StockableInterface $stockable */
            // Lock the record to prevent concurrent modifications
            $stockable = $stockable->lockForUpdate()->find($stockable->getStockableId());

            $quantityBefore = $stockable->getStockQuantity();
            $quantityAfter  = $quantityBefore + $quantity;

            // Validate stock doesn't go negative (unless configured to allow)
            if ($quantityAfter < 0 && !config('inventory.allow_negative_stock', false)) {
                throw new InsufficientStockException(
                    stockable: $stockable,
                    requestedQuantity: abs($quantity),
                    availableQuantity: $quantityBefore,
                );
            }

            // Update stock
            $stockable->setStockQuantity($quantityAfter);
            $stockable->save();

            // Record movement
            return $stockable->stockMovements()->create([
                'movement_type'   => $type,
                'quantity'        => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after'  => $quantityAfter,
                'reference_type'  => $reference?->getMorphClass(),
                'reference_id'    => $reference?->getKey(),
                'notes'           => $notes,
                'created_by'      => Auth::guard('admin')->id(),
            ]);
        });
    }

    /**
     * Get the available quantity for a stockable (stock - reservations).
     */
    public function getAvailableQuantity(StockableInterface $stockable): int
    {
        $stock    = $stockable->getStockQuantity();
        $reserved = $stockable->stockReservations()
            ->where('expires_at', '>', now())
            ->whereNull('converted_at')
            ->sum('quantity');

        return max(0, $stock - (int) $reserved);
    }

    /**
     * Refund stock (add back to inventory).
     *
     * @throws InsufficientStockException
     */
    public function refund(
        StockableInterface $stockable,
        int $quantity,
        ?Model $reference = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->adjust(
            stockable: $stockable,
            quantity: abs($quantity), // Always positive for refund
            type: MovementType::Refund,
            notes: $notes,
            reference: $reference,
        );
    }

    /**
     * Record a sale (deduct from inventory).
     *
     * @throws InsufficientStockException
     */
    public function recordSale(
        StockableInterface $stockable,
        int $quantity,
        ?Model $reference = null,
        ?string $notes = null,
    ): StockMovement {
        return $this->adjust(
            stockable: $stockable,
            quantity: -abs($quantity), // Always negative for sale
            type: MovementType::Sale,
            notes: $notes,
            reference: $reference,
        );
    }

    /**
     * Get stockable from type and ID.
     */
    public function resolveStockable(string $type, int $id): ?StockableInterface
    {
        return match ($type) {
            'product', Product::class => Product::find($id),
            'variant', ProductVariant::class => ProductVariant::find($id),
            default => null,
        };
    }

    /**
     * Get the stockable type string from a model class.
     */
    public function getStockableTypeString(string $class): string
    {
        return match ($class) {
            Product::class        => 'product',
            ProductVariant::class => 'variant',
            default               => $class,
        };
    }

    /**
     * Validate stock availability for checkout.
     *
     * @param  array<int, array{stockable: StockableInterface, quantity: int}>  $items
     */
    public function validateForCheckout(array $items): StockValidationResult
    {
        if (empty($items)) {
            return StockValidationResult::success([]);
        }

        $validationItems = [];
        $allValid        = true;

        foreach ($items as $item) {
            $stockable         = $item['stockable'];
            $requestedQuantity = $item['quantity'];

            $validationItem    = $this->createValidationItem($stockable, $requestedQuantity);
            $validationItems[] = $validationItem;

            if (!$validationItem->isAvailable) {
                $allValid = false;
            }
        }

        if (!$allValid) {
            $this->logFailedCheckoutValidation($validationItems);
        }

        return $allValid
            ? StockValidationResult::success($validationItems)
            : StockValidationResult::failure($validationItems);
    }

    /**
     * Check if a specific quantity is available for a stockable.
     */
    public function checkAvailability(StockableInterface $stockable, int $quantity): bool
    {
        if ($quantity <= 0) {
            return true;
        }

        if (!$stockable->isManagingStock()) {
            return true;
        }

        if ($stockable->allowsBackorders()) {
            return true;
        }

        $availableQuantity = $this->getAvailableQuantity($stockable);

        return $availableQuantity >= $quantity;
    }

    /**
     * Confirm a sale by deducting stock.
     *
     * @throws InsufficientStockException
     */
    public function confirmSale(
        StockableInterface $stockable,
        int $quantity,
        ?int $orderId = null,
        ?int $reservationId = null,
    ): StockMovement {
        return DB::transaction(function () use ($stockable, $quantity, $orderId, $reservationId) {
            $notes = $orderId ? "Venda - Pedido #{$orderId}" : 'Venda';

            // Convert reservation if provided
            if ($reservationId !== null) {
                $this->convertReservationIfValid($stockable, $reservationId);
            }

            // Record the sale
            $movement = $this->adjust(
                stockable: $stockable,
                quantity: -abs($quantity),
                type: MovementType::Sale,
                notes: $notes,
            );

            // Refresh stockable to get updated quantity
            /** @var Model&StockableInterface $stockable */
            $stockable = $stockable->fresh();

            // Check if low stock alert should be triggered
            $this->checkAndTriggerLowStockAlert($stockable);

            return $movement;
        });
    }

    /**
     * Refund stock when an order is cancelled.
     */
    public function refundStock(
        StockableInterface $stockable,
        int $quantity,
        ?int $orderId = null,
        ?string $reason = null,
        bool $recordMovement = true,
    ): ?StockMovement {
        if (!$recordMovement) {
            return null;
        }

        $notes = $this->buildRefundNotes($orderId, $reason);

        return $this->adjust(
            stockable: $stockable,
            quantity: abs($quantity),
            type: MovementType::Refund,
            notes: $notes,
        );
    }

    /**
     * Create a validation item for a stockable.
     */
    protected function createValidationItem(StockableInterface $stockable, int $requestedQuantity): StockValidationItem
    {
        // Skip validation for items not managing stock
        if (!$stockable->isManagingStock()) {
            return new StockValidationItem(
                stockable: $stockable,
                requestedQuantity: $requestedQuantity,
                availableQuantity: $requestedQuantity,
                isAvailable: true,
            );
        }

        // Allow backorders
        if ($stockable->allowsBackorders()) {
            return new StockValidationItem(
                stockable: $stockable,
                requestedQuantity: $requestedQuantity,
                availableQuantity: $this->getAvailableQuantity($stockable),
                isAvailable: true,
            );
        }

        $availableQuantity = $this->getAvailableQuantity($stockable);
        $isAvailable       = $availableQuantity >= $requestedQuantity;
        $message           = null;

        if (!$isAvailable) {
            $message = $this->buildUnavailableMessage($stockable, $requestedQuantity, $availableQuantity);
        }

        return new StockValidationItem(
            stockable: $stockable,
            requestedQuantity: $requestedQuantity,
            availableQuantity: $availableQuantity,
            isAvailable: $isAvailable,
            message: $message,
        );
    }

    /**
     * Build an unavailable message for a stockable.
     */
    protected function buildUnavailableMessage(
        StockableInterface $stockable,
        int $requestedQuantity,
        int $availableQuantity,
    ): string {
        /** @var Model&StockableInterface $stockable */
        $name = $stockable->name ?? 'Item';

        if ($availableQuantity === 0) {
            return "{$name} esta fora de estoque.";
        }

        return "{$name}: apenas {$availableQuantity} unidades disponiveis (solicitado: {$requestedQuantity}).";
    }

    /**
     * Log failed checkout validation attempts.
     *
     * @param  array<int, StockValidationItem>  $validationItems
     */
    protected function logFailedCheckoutValidation(array $validationItems): void
    {
        $unavailableItems = array_filter(
            $validationItems,
            fn (StockValidationItem $item) => !$item->isAvailable,
        );

        $details = array_map(fn (StockValidationItem $item) => [
            'stockable_type' => $item->stockable->getStockableType(),
            'stockable_id'   => $item->stockable->getStockableId(),
            'requested'      => $item->requestedQuantity,
            'available'      => $item->availableQuantity,
        ], $unavailableItems);

        Log::info('Checkout validation failed: insufficient stock', [
            'items'     => $details,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Convert a reservation to a sale if it's valid.
     */
    protected function convertReservationIfValid(StockableInterface $stockable, int $reservationId): void
    {
        $reservation = StockReservation::find($reservationId);

        if ($reservation === null) {
            return;
        }

        // Check if reservation belongs to the correct stockable
        if (
            $reservation->stockable_type !== $stockable->getStockableType() ||
            $reservation->stockable_id !== $stockable->getStockableId()
        ) {
            return;
        }

        // Check if reservation is already converted
        if ($reservation->isConverted()) {
            return;
        }

        // Check if reservation is expired
        if ($reservation->isExpired()) {
            return;
        }

        // Mark as converted
        $reservation->update([
            'converted_at' => now(),
        ]);
    }

    /**
     * Check if low stock alert should be triggered and dispatch job if needed.
     */
    protected function checkAndTriggerLowStockAlert(StockableInterface $stockable): void
    {
        if (!$stockable->isLowStock()) {
            return;
        }

        /** @var Model&StockableInterface $stockable */
        $notifyLowStock = $stockable->notify_low_stock ?? false;

        if (!$notifyLowStock) {
            return;
        }

        SendLowStockAlertsJob::dispatch();
    }

    /**
     * Build refund notes from order ID and reason.
     */
    protected function buildRefundNotes(?int $orderId, ?string $reason): string
    {
        $parts = ['Estorno'];

        if ($orderId !== null) {
            $parts[] = "- Pedido #{$orderId}";
        }

        if ($reason !== null && $reason !== '') {
            $parts[] = "- {$reason}";
        }

        return implode(' ', $parts);
    }
}
