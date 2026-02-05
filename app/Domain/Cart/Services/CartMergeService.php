<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Models\StockReservation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CartMergeService
{
    public function __construct(
        protected CartService $cartService = new CartService(),
    ) {
    }

    /**
     * Merge session cart into user cart when user logs in.
     */
    public function mergeOnLogin(User $user, string $sessionId): Cart
    {
        return DB::transaction(function () use ($user, $sessionId) {
            $userCart    = $this->cartService->getForUser($user->id);
            $sessionCart = $this->cartService->getForSession($sessionId);

            // Case 1: No carts exist - create new user cart
            if ($userCart === null && $sessionCart === null) {
                return $this->cartService->getOrCreate(userId: $user->id);
            }

            // Case 2: Only user cart exists - return it
            if ($sessionCart === null) {
                return $userCart;
            }

            // Case 3: Only session cart exists - transfer to user
            if ($userCart === null) {
                return $this->transferCartToUser($sessionCart, $user);
            }

            // Case 4: Both carts exist - merge session into user
            return $this->mergeCartsAndDelete($userCart, $sessionCart);
        });
    }

    /**
     * Transfer a session cart to a user.
     */
    protected function transferCartToUser(Cart $cart, User $user): Cart
    {
        $cart->update([
            'user_id'    => $user->id,
            'session_id' => null,
        ]);

        return $cart->fresh();
    }

    /**
     * Merge items from source cart into target cart, then delete source.
     */
    protected function mergeCartsAndDelete(Cart $targetCart, Cart $sourceCart): Cart
    {
        // Load items for both carts
        $targetCart->load('items');
        $sourceCart->load('items.product', 'items.variant');

        foreach ($sourceCart->items as $sourceItem) {
            $this->mergeItem($targetCart, $sourceItem);
        }

        // Delete the source cart and its items/reservations
        $this->deleteCartAndRelated($sourceCart);

        // Reload items and recalculate target cart totals
        $targetCart->load('items');
        $targetCart->calculateTotals();
        $targetCart->save();

        return $targetCart->fresh();
    }

    /**
     * Merge a single item into the target cart.
     */
    protected function mergeItem(Cart $targetCart, CartItem $sourceItem): void
    {
        // Check if same product/variant exists in target cart
        $existingItem = $targetCart->items
            ->where('product_id', $sourceItem->product_id)
            ->where('variant_id', $sourceItem->variant_id)
            ->first();

        if ($existingItem !== null) {
            // Combine quantities respecting stock limit
            $this->combineQuantities($existingItem, $sourceItem);
        } else {
            // Move item to target cart
            $this->moveItemToCart($targetCart, $sourceItem);
        }
    }

    /**
     * Combine quantities for same product, respecting stock limit.
     */
    protected function combineQuantities(CartItem $targetItem, CartItem $sourceItem): void
    {
        $stockable = $sourceItem->variant ?? $sourceItem->product;

        // Get total stock
        $totalStock = $this->getTotalStock($stockable);

        // Get reservations from OTHER carts (not target or source)
        $otherReservations = StockReservation::query()
            ->forStockable($stockable)
            ->active()
            ->whereNotIn('cart_id', [$targetItem->cart_id, $sourceItem->cart_id])
            ->sum('quantity');

        $maxStock = max(0, $totalStock - $otherReservations);

        $combinedQuantity = min(
            $targetItem->quantity + $sourceItem->quantity,
            $maxStock,
        );

        // Update the target item quantity
        $targetItem->update(['quantity' => $combinedQuantity]);

        // Update the reservation
        $this->updateReservation($stockable, $targetItem->cart_id, $combinedQuantity);
    }

    /**
     * Get total stock for a stockable.
     */
    protected function getTotalStock(Product|ProductVariant $stockable): int
    {
        if ($stockable instanceof Product && !$stockable->manage_stock) {
            return 99;
        }

        return $stockable->stock_quantity ?? 0;
    }

    /**
     * Move an item to a different cart.
     */
    protected function moveItemToCart(Cart $targetCart, CartItem $sourceItem): void
    {
        $stockable = $sourceItem->variant ?? $sourceItem->product;

        // Get total stock excluding source cart's reservation (which we're moving)
        $totalStock        = $this->getTotalStock($stockable);
        $otherReservations = StockReservation::query()
            ->forStockable($stockable)
            ->active()
            ->whereNotIn('cart_id', [$targetCart->id, $sourceItem->cart_id])
            ->sum('quantity');

        $maxStock = max(0, $totalStock - $otherReservations);
        $quantity = min($sourceItem->quantity, $maxStock);

        if ($quantity <= 0) {
            return;
        }

        // Create new item in target cart
        CartItem::create([
            'cart_id'    => $targetCart->id,
            'product_id' => $sourceItem->product_id,
            'variant_id' => $sourceItem->variant_id,
            'quantity'   => $quantity,
            'unit_price' => $sourceItem->unit_price,
            'sale_price' => $sourceItem->sale_price,
        ]);

        // Create reservation
        $this->createReservation($stockable, $targetCart->id, $quantity);
    }

    /**
     * Delete cart and all related records.
     */
    protected function deleteCartAndRelated(Cart $cart): void
    {
        // Delete reservations
        StockReservation::where('cart_id', $cart->id)->delete();

        // Delete items
        $cart->items()->delete();

        // Delete cart
        $cart->delete();
    }

    /**
     * Get maximum available stock for a stockable.
     */
    protected function getMaxStock(Product|ProductVariant $stockable, string $excludeCartId): int
    {
        if ($stockable instanceof Product && !$stockable->manage_stock) {
            return 99;
        }

        $totalStock = $stockable->stock_quantity ?? 0;

        $reservedByOthers = StockReservation::query()
            ->forStockable($stockable)
            ->active()
            ->where('cart_id', '!=', $excludeCartId)
            ->sum('quantity');

        return max(0, $totalStock - $reservedByOthers);
    }

    /**
     * Update or create a stock reservation.
     */
    protected function updateReservation(Product|ProductVariant $stockable, string $cartId, int $quantity): void
    {
        StockReservation::updateOrCreate(
            [
                'stockable_type' => get_class($stockable),
                'stockable_id'   => $stockable->id,
                'cart_id'        => $cartId,
            ],
            [
                'quantity'   => $quantity,
                'expires_at' => now()->addMinutes(config('cart.reservation_ttl_minutes', 30)),
            ],
        );
    }

    /**
     * Create a stock reservation.
     */
    protected function createReservation(Product|ProductVariant $stockable, string $cartId, int $quantity): void
    {
        StockReservation::create([
            'stockable_type' => get_class($stockable),
            'stockable_id'   => $stockable->id,
            'cart_id'        => $cartId,
            'quantity'       => $quantity,
            'expires_at'     => now()->addMinutes(config('cart.reservation_ttl_minutes', 30)),
        ]);
    }
}
