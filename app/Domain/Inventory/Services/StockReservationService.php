<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Services;

use App\Domain\Inventory\Contracts\StockableInterface;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockReservationService
{
    public function __construct(
        protected StockService $stockService,
    ) {
    }

    /**
     * Reserve stock for a stockable item.
     *
     * @throws InsufficientStockException
     */
    public function reserve(
        StockableInterface $stockable,
        int $quantity,
        ?string $cartId = null,
    ): StockReservation {
        return DB::transaction(function () use ($stockable, $quantity, $cartId) {
            $available = $this->getAvailableQuantity($stockable);

            if ($available < $quantity) {
                throw new InsufficientStockException(
                    stockable: $stockable,
                    requestedQuantity: $quantity,
                    availableQuantity: $available,
                );
            }

            $ttl = (int) config('inventory.reservation_ttl', 30);

            $reservation = StockReservation::query()->create([
                'stockable_type' => $stockable->getStockableType(),
                'stockable_id'   => $stockable->getStockableId(),
                'quantity'       => $quantity,
                'cart_id'        => $cartId,
                'expires_at'     => now()->addMinutes($ttl),
            ]);

            // Record the reservation movement
            $this->recordMovement(
                stockable: $stockable,
                quantity: -$quantity,
                type: MovementType::Reservation,
                reference: $reservation,
                notes: $cartId ? "Reserva para carrinho {$cartId}" : 'Reserva de estoque',
            );

            return $reservation;
        });
    }

    /**
     * Release a reservation.
     */
    public function release(StockReservation $reservation): void
    {
        if ($reservation->isConverted()) {
            return;
        }

        DB::transaction(function () use ($reservation) {
            /** @var StockableInterface&Model $stockable */
            $stockable = $reservation->stockable;

            // Record the release movement
            $this->recordMovement(
                stockable: $stockable,
                quantity: $reservation->quantity,
                type: MovementType::ReservationRelease,
                reference: $reservation,
                notes: 'Liberacao de reserva',
            );

            $reservation->delete();
        });
    }

    /**
     * Release all active reservations for a cart.
     */
    public function releaseByCart(string $cartId): void
    {
        $reservations = StockReservation::query()
            ->forCart($cartId)
            ->active()
            ->get();

        foreach ($reservations as $reservation) {
            $this->release($reservation);
        }
    }

    /**
     * Convert a reservation to a sale.
     */
    public function convertToSale(StockReservation $reservation, ?int $orderId = null): void
    {
        if ($reservation->isConverted()) {
            return;
        }

        if ($reservation->isExpired()) {
            return;
        }

        DB::transaction(function () use ($reservation, $orderId) {
            /** @var StockableInterface&Model $stockable */
            $stockable = $reservation->stockable;

            // Deduct stock
            $this->stockService->adjust(
                stockable: $stockable,
                quantity: -$reservation->quantity,
                type: MovementType::Sale,
                notes: $orderId ? "Venda - Pedido #{$orderId}" : 'Venda',
            );

            // Mark reservation as converted
            $reservation->update([
                'converted_at' => now(),
            ]);
        });
    }

    /**
     * Get available quantity (stock minus active reservations).
     */
    public function getAvailableQuantity(StockableInterface $stockable): int
    {
        $stock    = $stockable->getStockQuantity();
        $reserved = StockReservation::query()
            ->forStockable($stockable->getStockableType(), $stockable->getStockableId())
            ->active()
            ->sum('quantity');

        return max(0, $stock - (int) $reserved);
    }

    /**
     * Extend the expiration time of a reservation.
     */
    public function extendReservation(StockReservation $reservation, Carbon $newExpiry): void
    {
        $reservation->update([
            'expires_at' => $newExpiry,
        ]);
    }

    /**
     * Record a stock movement.
     */
    protected function recordMovement(
        StockableInterface $stockable,
        int $quantity,
        MovementType $type,
        ?Model $reference = null,
        ?string $notes = null,
    ): void {
        $stockable->stockMovements()->create([
            'movement_type'   => $type,
            'quantity'        => $quantity,
            'quantity_before' => $stockable->getStockQuantity(),
            'quantity_after'  => $stockable->getStockQuantity(),
            'reference_type'  => $reference?->getMorphClass(),
            'reference_id'    => $reference?->getKey(),
            'notes'           => $notes,
        ]);
    }
}
