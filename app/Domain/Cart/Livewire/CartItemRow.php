<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Livewire;

use App\Domain\Cart\Exceptions\InsufficientStockException;
use App\Domain\Cart\Models\CartItem;
use App\Domain\Cart\Services\CartService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class CartItemRow extends Component
{
    public int $itemId;

    public int $quantity = 1;

    public int $maxQuantity = 99;

    public ?string $errorMessage = null;

    protected CartService $cartService;

    public function boot(CartService $cartService): void
    {
        $this->cartService = $cartService;
    }

    public function mount(int $itemId): void
    {
        $this->itemId = $itemId;
        $this->loadItem();
    }

    /**
     * Load item data.
     */
    protected function loadItem(): void
    {
        $item = $this->item;

        if ($item === null) {
            return;
        }

        $this->quantity = $item->quantity;
        $this->calculateMaxQuantity();
    }

    /**
     * Calculate the maximum quantity available for this item.
     */
    protected function calculateMaxQuantity(): void
    {
        $item = $this->item;

        if ($item === null) {
            return;
        }

        $stockable = $item->getStockable();

        // For products that don't manage stock
        if ($stockable instanceof \App\Domain\Catalog\Models\Product && !$stockable->manage_stock) {
            $this->maxQuantity = 99;

            return;
        }

        // Get total stock
        $totalStock = $stockable->stock_quantity ?? 0;

        // Get reservations from OTHER carts only
        $otherReservations = \App\Domain\Inventory\Models\StockReservation::query()
            ->forStockable($stockable)
            ->active()
            ->where('cart_id', '!=', $item->cart_id)
            ->sum('quantity');

        $this->maxQuantity = max(0, $totalStock - $otherReservations);
    }

    /**
     * Get the cart item.
     */
    #[Computed]
    public function item(): ?CartItem
    {
        return CartItem::with(['product.images', 'variant'])
            ->find($this->itemId);
    }

    /**
     * Get the subtotal for this item.
     */
    #[Computed]
    public function subtotal(): int
    {
        return $this->item?->getSubtotal() ?? 0;
    }

    /**
     * Increment quantity by one.
     */
    public function increment(): void
    {
        if ($this->quantity >= $this->maxQuantity) {
            return;
        }

        $this->quantity++;
        $this->saveQuantity();
    }

    /**
     * Decrement quantity by one.
     */
    public function decrement(): void
    {
        if ($this->quantity <= 1) {
            return;
        }

        $this->quantity--;
        $this->saveQuantity();
    }

    /**
     * Update quantity from direct input.
     */
    public function updateQuantity(): void
    {
        // Enforce minimum
        if ($this->quantity < 1) {
            $this->quantity = 1;
        }

        // Enforce maximum
        if ($this->quantity > $this->maxQuantity) {
            $this->quantity = $this->maxQuantity;
        }

        $this->saveQuantity();
    }

    /**
     * Save the quantity to the database.
     */
    protected function saveQuantity(): void
    {
        $this->errorMessage = null;
        $item               = $this->item;

        if ($item === null) {
            return;
        }

        try {
            $this->cartService->updateItem($item, $this->quantity);

            // Clear computed cache
            unset($this->item, $this->subtotal);

            // Dispatch event for other components
            $this->dispatch('cart-updated');

        } catch (InsufficientStockException $e) {
            $this->errorMessage = "Estoque insuficiente. Disponivel: {$e->availableQuantity} unidades.";
            $this->quantity     = $e->availableQuantity;
        }
    }

    /**
     * Remove the item from the cart.
     */
    public function remove(): void
    {
        $item = $this->item;

        if ($item === null) {
            return;
        }

        // Store data for undo functionality
        $removedItemData = [
            'itemId'      => $item->id,
            'productId'   => $item->product_id,
            'variantId'   => $item->variant_id,
            'quantity'    => $item->quantity,
            'productName' => $item->product->name,
        ];

        // Remove the item
        $this->cartService->removeItem($item);

        // Dispatch events
        $this->dispatch('cart-updated');
        $this->dispatch('item-removed', ...$removedItemData);
    }

    public function render(): View
    {
        return view('livewire.cart.cart-item-row');
    }
}
