<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Exceptions\{InsufficientStockException, ProductNotAvailableException};
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class CartService
{
    /**
     * Default reservation TTL in minutes.
     */
    protected int $reservationTtl = 30;

    public function __construct()
    {
        $this->reservationTtl = config('cart.reservation_ttl', 30);
    }

    /**
     * Get or create an active cart for the current user or session.
     */
    public function getOrCreate(?int $userId = null, ?string $sessionId = null): Cart
    {
        if ($userId !== null) {
            return $this->getOrCreateForUser($userId);
        }

        if ($sessionId !== null) {
            return $this->getOrCreateForSession($sessionId);
        }

        throw new \InvalidArgumentException('Either userId or sessionId must be provided');
    }

    /**
     * Get or create an active cart for a user.
     */
    public function getOrCreateForUser(int $userId): Cart
    {
        $cart = Cart::query()
            ->forUser($userId)
            ->active()
            ->first();

        if ($cart !== null) {
            return $cart;
        }

        return Cart::create([
            'user_id'          => $userId,
            'session_id'       => null,
            'status'           => CartStatus::Active,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Get or create an active cart for a session.
     */
    public function getOrCreateForSession(string $sessionId): Cart
    {
        $cart = Cart::query()
            ->forSession($sessionId)
            ->active()
            ->first();

        if ($cart !== null) {
            return $cart;
        }

        return Cart::create([
            'user_id'          => null,
            'session_id'       => $sessionId,
            'status'           => CartStatus::Active,
            'last_activity_at' => now(),
        ]);
    }

    /**
     * Add an item to the cart.
     *
     * @throws InsufficientStockException
     * @throws ProductNotAvailableException
     */
    public function addItem(
        Cart $cart,
        Product $product,
        int $quantity = 1,
        ?ProductVariant $variant = null,
    ): CartItem {
        $this->validateProduct($product);
        $stockable      = $variant ?? $product;
        $availableStock = $this->getAvailableStock($stockable, $cart->id);

        if ($quantity > $availableStock) {
            throw new InsufficientStockException(
                productName: $product->name,
                requestedQuantity: $quantity,
                availableQuantity: $availableStock,
            );
        }

        return DB::transaction(function () use ($cart, $product, $quantity, $variant, $stockable) {
            // Check if item already exists in cart
            $existingItem = $cart->items()
                ->where('product_id', $product->id)
                ->where('variant_id', $variant?->id)
                ->first();

            if ($existingItem !== null) {
                return $this->updateItemQuantity($existingItem, $existingItem->quantity + $quantity);
            }

            // Create new cart item
            $price     = $variant?->getEffectivePrice() ?? $product->price;
            $salePrice = $product->isOnSale() && $variant === null ? $product->sale_price : null;

            $item = $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity'   => $quantity,
                'unit_price' => $price,
                'sale_price' => $salePrice,
            ]);

            // Create stock reservation
            $this->createReservation($stockable, $cart->id, $quantity);

            // Recalculate totals
            $cart->load('items');
            $cart->calculateTotals();
            $cart->last_activity_at = now();
            $cart->save();

            return $item;
        });
    }

    /**
     * Update the quantity of a cart item.
     *
     * @throws InsufficientStockException
     */
    public function updateItem(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);

            return $item;
        }

        return $this->updateItemQuantity($item, $quantity);
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(CartItem $item): void
    {
        DB::transaction(function () use ($item) {
            $stockable = $item->getStockable();

            // Release stock reservation
            $this->releaseReservation($stockable, $item->cart_id);

            // Delete the item
            $item->delete();

            // Recalculate totals
            $cart = $item->cart;
            $cart->load('items');
            $cart->calculateTotals();
            $cart->last_activity_at = now();
            $cart->save();
        });
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Cart $cart): void
    {
        DB::transaction(function () use ($cart) {
            // Release all reservations for this cart
            StockReservation::query()
                ->forCart($cart->id)
                ->active()
                ->delete();

            // Delete all items
            $cart->items()->delete();

            // Reset totals
            $cart->subtotal         = 0;
            $cart->discount         = 0;
            $cart->total            = 0;
            $cart->shipping_cost    = null;
            $cart->shipping_method  = null;
            $cart->shipping_zipcode = null;
            $cart->shipping_days    = null;
            $cart->coupon_id        = null;
            $cart->last_activity_at = now();
            $cart->save();
        });
    }

    /**
     * Get a cart by ID.
     */
    public function getById(string $cartId): ?Cart
    {
        return Cart::with('items.product', 'items.variant')->find($cartId);
    }

    /**
     * Get the current cart for a user.
     */
    public function getForUser(int $userId): ?Cart
    {
        return Cart::query()
            ->with('items.product', 'items.variant')
            ->forUser($userId)
            ->active()
            ->first();
    }

    /**
     * Get the current cart for a session.
     */
    public function getForSession(string $sessionId): ?Cart
    {
        return Cart::query()
            ->with('items.product', 'items.variant')
            ->forSession($sessionId)
            ->active()
            ->first();
    }

    /**
     * Update item quantity with stock validation.
     *
     * @throws InsufficientStockException
     */
    protected function updateItemQuantity(CartItem $item, int $newQuantity): CartItem
    {
        $stockable          = $item->getStockable();
        $currentReservation = $this->getReservationQuantity($stockable, $item->cart_id);
        $quantityDiff       = $newQuantity - $item->quantity;

        // If increasing quantity, check available stock
        if ($quantityDiff > 0) {
            $availableStock = $this->getAvailableStock($stockable, $item->cart_id);

            if ($quantityDiff > $availableStock) {
                throw new InsufficientStockException(
                    productName: $item->product->name,
                    requestedQuantity: $newQuantity,
                    availableQuantity: $item->quantity + $availableStock,
                );
            }
        }

        return DB::transaction(function () use ($item, $newQuantity, $stockable) {
            // Update item quantity
            $item->quantity = $newQuantity;
            $item->save();

            // Update stock reservation
            $this->updateReservation($stockable, $item->cart_id, $newQuantity);

            // Recalculate totals
            $cart = $item->cart;
            $cart->load('items');
            $cart->calculateTotals();
            $cart->last_activity_at = now();
            $cart->save();

            return $item->fresh();
        });
    }

    /**
     * Validate that a product can be added to cart.
     *
     * @throws ProductNotAvailableException
     */
    protected function validateProduct(Product $product): void
    {
        if ($product->status !== ProductStatus::Active) {
            throw new ProductNotAvailableException($product->name);
        }
    }

    /**
     * Get available stock for a stockable, excluding current cart's reservation.
     */
    protected function getAvailableStock(Product|ProductVariant $stockable, string $excludeCartId): int
    {
        $totalStock = $this->getTotalStock($stockable);

        if ($totalStock === PHP_INT_MAX) {
            return PHP_INT_MAX; // Unlimited stock
        }

        // Get all active reservations except for the current cart
        $reservedQuantity = StockReservation::query()
            ->forStockable($stockable)
            ->active()
            ->where('cart_id', '!=', $excludeCartId)
            ->sum('quantity');

        return max(0, $totalStock - $reservedQuantity);
    }

    /**
     * Get total stock for a stockable.
     */
    protected function getTotalStock(Product|ProductVariant $stockable): int
    {
        if ($stockable instanceof ProductVariant) {
            // Check if parent product manages stock
            if (!$stockable->product->manage_stock) {
                return PHP_INT_MAX;
            }

            return $stockable->stock_quantity ?? 0;
        }

        // Product
        if (!$stockable->manage_stock) {
            return PHP_INT_MAX;
        }

        return $stockable->stock_quantity ?? 0;
    }

    /**
     * Create a stock reservation.
     */
    protected function createReservation(Product|ProductVariant $stockable, string $cartId, int $quantity): StockReservation
    {
        return StockReservation::create([
            'stockable_type' => get_class($stockable),
            'stockable_id'   => $stockable->getKey(),
            'cart_id'        => $cartId,
            'quantity'       => $quantity,
            'expires_at'     => now()->addMinutes($this->reservationTtl),
        ]);
    }

    /**
     * Update an existing stock reservation.
     */
    protected function updateReservation(Product|ProductVariant $stockable, string $cartId, int $quantity): void
    {
        StockReservation::query()
            ->forStockable($stockable)
            ->forCart($cartId)
            ->active()
            ->update([
                'quantity'   => $quantity,
                'expires_at' => now()->addMinutes($this->reservationTtl),
            ]);
    }

    /**
     * Release (delete) a stock reservation.
     */
    protected function releaseReservation(Product|ProductVariant $stockable, string $cartId): void
    {
        StockReservation::query()
            ->forStockable($stockable)
            ->forCart($cartId)
            ->delete();
    }

    /**
     * Get the current reservation quantity for a stockable in a cart.
     */
    protected function getReservationQuantity(Product|ProductVariant $stockable, string $cartId): int
    {
        return (int) StockReservation::query()
            ->forStockable($stockable)
            ->forCart($cartId)
            ->active()
            ->sum('quantity');
    }
}
