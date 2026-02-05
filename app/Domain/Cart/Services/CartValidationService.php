<?php

declare(strict_types = 1);

namespace App\Domain\Cart\Services;

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Support\Facades\DB;

class CartValidationService
{
    /**
     * Validate cart and return validation result.
     *
     * @return array{alerts: list<string>, removed_items: list<CartItem>, updated_items: list<CartItem>}
     */
    public function validateCart(Cart $cart): array
    {
        $alerts       = [];
        $removedItems = [];
        $updatedItems = [];

        // Load items with trashed products to detect deleted ones
        $cart->load([
            'items.product' => fn ($query) => $query->withTrashed(),
            'items.variant',
            'coupon',
        ]);

        if ($cart->items->isEmpty()) {
            return [
                'alerts'        => $alerts,
                'removed_items' => $removedItems,
                'updated_items' => $updatedItems,
            ];
        }

        DB::transaction(function () use ($cart, &$alerts, &$removedItems, &$updatedItems) {
            foreach ($cart->items as $item) {
                $validationResult = $this->validateItem($item, $cart->id);

                if ($validationResult['should_remove']) {
                    $removedItems[] = $item;
                    $alerts[]       = $validationResult['message'];
                    $this->removeItem($item);

                    continue;
                }

                if ($validationResult['should_update']) {
                    $updatedItems[] = $item;
                    $alerts         = array_merge($alerts, $validationResult['messages']);
                    $this->updateItem($item, $validationResult['updates']);
                }
            }

            // Validate coupon after items
            $couponValidation = $this->validateCoupon($cart);

            if ($couponValidation['should_remove']) {
                $alerts[] = $couponValidation['message'];
                $this->removeCoupon($cart);
            }

            // Recalculate totals
            $cart->load('items');
            $cart->calculateTotals();
            $cart->save();
        });

        return [
            'alerts'        => $alerts,
            'removed_items' => $removedItems,
            'updated_items' => $updatedItems,
        ];
    }

    /**
     * Validate a single cart item.
     *
     * @return array{should_remove: bool, should_update: bool, message?: string, messages?: list<string>, updates?: array<string, mixed>}
     */
    protected function validateItem(CartItem $item, string $cartId): array
    {
        $product = $item->product;

        // Check if product was deleted
        if ($product === null || $product->trashed()) {
            return [
                'should_remove' => true,
                'should_update' => false,
                'message'       => "O produto \"{$item->getDisplayName()}\" foi removido do carrinho pois não está mais disponível.",
            ];
        }

        // Check if product is inactive
        if ($product->status !== ProductStatus::Active) {
            return [
                'should_remove' => true,
                'should_update' => false,
                'message'       => "O produto \"{$product->name}\" foi removido do carrinho pois não está mais disponível.",
            ];
        }

        // For variants, check if variant exists
        if ($item->variant_id !== null && $item->variant === null) {
            return [
                'should_remove' => true,
                'should_update' => false,
                'message'       => "A variação do produto \"{$product->name}\" não está mais disponível.",
            ];
        }

        $updates  = [];
        $messages = [];

        // Check stock availability
        $stockable      = $item->variant ?? $product;
        $availableStock = $this->getAvailableStock($stockable, $cartId);

        if ($availableStock === 0) {
            return [
                'should_remove' => true,
                'should_update' => false,
                'message'       => "O produto \"{$product->name}\" foi removido do carrinho pois está esgotado.",
            ];
        }

        if ($item->quantity > $availableStock) {
            $updates['quantity'] = $availableStock;
            $messages[]          = "A quantidade de \"{$product->name}\" foi ajustada de {$item->quantity} para {$availableStock} devido ao estoque disponível.";
        }

        // Check price changes
        $currentPrice     = $item->variant?->getEffectivePrice() ?? $product->price;
        $currentSalePrice = $product->isOnSale() && $item->variant === null ? $product->sale_price : null;

        if ($item->unit_price !== $currentPrice) {
            $oldPriceFormatted     = number_format($item->unit_price / 100, 2, ',', '.');
            $newPriceFormatted     = number_format($currentPrice / 100, 2, ',', '.');
            $updates['unit_price'] = $currentPrice;
            $messages[]            = "O preço de \"{$product->name}\" foi atualizado de R$ {$oldPriceFormatted} para R$ {$newPriceFormatted}.";
        }

        // Check if product went on sale
        if ($item->sale_price !== $currentSalePrice) {
            $updates['sale_price'] = $currentSalePrice;

            if ($currentSalePrice !== null && $item->sale_price === null) {
                $salePriceFormatted = number_format($currentSalePrice / 100, 2, ',', '.');
                $messages[]         = "O produto \"{$product->name}\" entrou em promoção! Novo preço: R$ {$salePriceFormatted}.";
            } elseif ($currentSalePrice === null && $item->sale_price !== null) {
                $messages[] = "A promoção do produto \"{$product->name}\" acabou.";
            }
        }

        if (!empty($updates)) {
            return [
                'should_remove' => false,
                'should_update' => true,
                'messages'      => $messages,
                'updates'       => $updates,
            ];
        }

        return [
            'should_remove' => false,
            'should_update' => false,
        ];
    }

    /**
     * Validate the cart coupon.
     *
     * @return array{should_remove: bool, message?: string}
     */
    protected function validateCoupon(Cart $cart): array
    {
        if ($cart->coupon_id === null || $cart->coupon === null) {
            return ['should_remove' => false];
        }

        $coupon = $cart->coupon;

        // Check if coupon is active
        if (!$coupon->is_active) {
            return [
                'should_remove' => true,
                'message'       => "O cupom \"{$coupon->code}\" não está mais disponível e foi removido.",
            ];
        }

        // Check if coupon is expired
        if (!$coupon->isWithinDateRange()) {
            return [
                'should_remove' => true,
                'message'       => "O cupom \"{$coupon->code}\" expirou e foi removido.",
            ];
        }

        // Check usage limit
        if ($coupon->hasReachedUsageLimit()) {
            return [
                'should_remove' => true,
                'message'       => "O cupom \"{$coupon->code}\" atingiu o limite de uso e foi removido.",
            ];
        }

        // Check minimum order value
        if (!$coupon->meetsMinimumOrderValue($cart->subtotal)) {
            $minValueFormatted = number_format($coupon->minimum_order_value / 100, 2, ',', '.');

            return [
                'should_remove' => true,
                'message'       => "O cupom \"{$coupon->code}\" requer um pedido mínimo de R$ {$minValueFormatted} e foi removido.",
            ];
        }

        return ['should_remove' => false];
    }

    /**
     * Get available stock for a stockable.
     */
    protected function getAvailableStock(Product|ProductVariant $stockable, string $excludeCartId): int
    {
        $totalStock = $this->getTotalStock($stockable);

        if ($totalStock === PHP_INT_MAX) {
            return PHP_INT_MAX;
        }

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
            if (!$stockable->product->manage_stock) {
                return PHP_INT_MAX;
            }

            return $stockable->stock_quantity ?? 0;
        }

        if (!$stockable->manage_stock) {
            return PHP_INT_MAX;
        }

        return $stockable->stock_quantity ?? 0;
    }

    /**
     * Remove an item from the cart.
     */
    protected function removeItem(CartItem $item): void
    {
        $stockable = $item->variant ?? $item->product;

        if ($stockable !== null) {
            StockReservation::query()
                ->forStockable($stockable)
                ->forCart($item->cart_id)
                ->delete();
        }

        $item->delete();
    }

    /**
     * Update a cart item.
     *
     * @param  array<string, mixed>  $updates
     */
    protected function updateItem(CartItem $item, array $updates): void
    {
        $item->update($updates);

        // Update stock reservation if quantity changed
        if (isset($updates['quantity'])) {
            $stockable = $item->variant ?? $item->product;

            StockReservation::query()
                ->forStockable($stockable)
                ->forCart($item->cart_id)
                ->active()
                ->update(['quantity' => $updates['quantity']]);
        }
    }

    /**
     * Remove coupon from cart.
     */
    protected function removeCoupon(Cart $cart): void
    {
        $cart->coupon_id = null;
        $cart->discount  = 0;
        $cart->save();
    }
}
