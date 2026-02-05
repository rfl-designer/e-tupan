<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Cart\Services\CartValidationService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Marketing\Enums\CouponType;
use App\Domain\Marketing\Models\Coupon;
use App\Models\User;

beforeEach(function () {
    $this->validationService = new CartValidationService();
});

describe('CartValidationService', function () {
    describe('validateCart', function () {
        it('returns empty alerts for valid cart', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(2)->create();
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart);

            expect($result['alerts'])->toBeEmpty()
                ->and($result['removed_items'])->toBeEmpty()
                ->and($result['updated_items'])->toBeEmpty();
        });

        it('removes items with deleted products', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->create([
                'price' => 5000,
            ]);

            $item = CartItem::factory()->forCart($cart)->forProduct($product)->create();
            $cart->calculateTotals()->save();

            // Soft delete the product
            $product->delete();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['removed_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("O produto \"{$product->name}\" foi removido do carrinho pois não está mais disponível.");
        });

        it('removes items with inactive products', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->inactive()->create([
                'price' => 5000,
            ]);

            CartItem::factory()->forCart($cart)->forProduct($product)->create();
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['removed_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("O produto \"{$product->name}\" foi removido do carrinho pois não está mais disponível.");
        });

        it('adjusts quantity when stock is insufficient', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 3,
                'manage_stock'   => true,
            ]);

            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(10)->create();
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['updated_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("A quantidade de \"{$product->name}\" foi ajustada de 10 para 3 devido ao estoque disponível.");

            // Check the item was actually updated
            $cart->refresh();
            expect($cart->items->first()->quantity)->toBe(3);
        });

        it('removes item when stock is zero', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 0,
                'manage_stock'   => true,
            ]);

            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(5)->create();
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['removed_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("O produto \"{$product->name}\" foi removido do carrinho pois está esgotado.");
        });

        it('updates prices when product price changed', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            // Create item with old price
            CartItem::factory()->forCart($cart)->create([
                'product_id' => $product->id,
                'quantity'   => 2,
                'unit_price' => 4000, // Old price
                'sale_price' => null,
            ]);
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['updated_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("O preço de \"{$product->name}\" foi atualizado de R$ 40,00 para R$ 50,00.");

            // Check the item was actually updated
            $cart->refresh();
            expect($cart->items->first()->unit_price)->toBe(5000);
        });

        it('updates sale price when product goes on sale', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->onSale(4000)->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            // Create item without sale price
            CartItem::factory()->forCart($cart)->create([
                'product_id' => $product->id,
                'quantity'   => 1,
                'unit_price' => 5000,
                'sale_price' => null, // No sale price
            ]);
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['updated_items'])->toHaveCount(1)
                ->and($result['alerts'])->toContain("O produto \"{$product->name}\" entrou em promoção! Novo preço: R$ 40,00.");

            // Check the item was actually updated
            $cart->refresh();
            expect($cart->items->first()->sale_price)->toBe(4000);
        });

        it('removes expired coupon', function () {
            $user   = User::factory()->create();
            $coupon = Coupon::factory()->create([
                'type'       => CouponType::Percentage,
                'value'      => 10,
                'is_active'  => true,
                'expires_at' => now()->subDay(),
            ]);
            $cart = Cart::factory()->forUser($user)->create([
                'coupon_id' => $coupon->id,
                'discount'  => 500,
            ]);

            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['alerts'])->toContain("O cupom \"{$coupon->code}\" expirou e foi removido.")
                ->and($cart->refresh()->coupon_id)->toBeNull()
                ->and($cart->discount)->toBe(0);
        });

        it('removes inactive coupon', function () {
            $user   = User::factory()->create();
            $coupon = Coupon::factory()->create([
                'type'      => CouponType::Fixed,
                'value'     => 1000,
                'is_active' => false,
            ]);
            $cart = Cart::factory()->forUser($user)->create([
                'coupon_id' => $coupon->id,
                'discount'  => 1000,
            ]);

            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['alerts'])->toContain("O cupom \"{$coupon->code}\" não está mais disponível e foi removido.")
                ->and($cart->refresh()->coupon_id)->toBeNull();
        });

        it('removes coupon when cart is below minimum value', function () {
            $user   = User::factory()->create();
            $coupon = Coupon::factory()->create([
                'type'                => CouponType::Percentage,
                'value'               => 10,
                'is_active'           => true,
                'minimum_order_value' => 10000, // R$ 100
            ]);
            $cart = Cart::factory()->forUser($user)->create([
                'coupon_id' => $coupon->id,
                'discount'  => 500,
                'subtotal'  => 5000, // R$ 50
            ]);

            $product = Product::factory()->active()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['alerts'])->toContain("O cupom \"{$coupon->code}\" requer um pedido mínimo de R$ 100,00 e foi removido.");
        });

        it('recalculates totals after validation', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create([
                'subtotal' => 10000,
                'total'    => 10000,
            ]);
            $product = Product::factory()->active()->create([
                'price'          => 3000, // Changed from 5000
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            CartItem::factory()->forCart($cart)->create([
                'product_id' => $product->id,
                'quantity'   => 2,
                'unit_price' => 5000, // Old price
            ]);

            $this->validationService->validateCart($cart->fresh());

            $cart->refresh();
            expect($cart->subtotal)->toBe(6000) // 2 x 3000
                ->and($cart->total)->toBe(6000);
        });

        it('handles cart with unlimited stock products', function () {
            $user    = User::factory()->create();
            $cart    = Cart::factory()->forUser($user)->create();
            $product = Product::factory()->active()->unlimitedStock()->create([
                'price' => 5000,
            ]);

            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(100)->create();
            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['alerts'])->toBeEmpty()
                ->and($result['removed_items'])->toBeEmpty()
                ->and($result['updated_items'])->toBeEmpty();
        });

        it('handles empty cart', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create();

            $result = $this->validationService->validateCart($cart);

            expect($result['alerts'])->toBeEmpty()
                ->and($result['removed_items'])->toBeEmpty()
                ->and($result['updated_items'])->toBeEmpty();
        });

        it('handles multiple validation issues', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create();

            // Product 1: Deleted
            $product1 = Product::factory()->active()->create(['price' => 5000]);
            CartItem::factory()->forCart($cart)->forProduct($product1)->create();
            $product1->delete();

            // Product 2: Inactive
            $product2 = Product::factory()->inactive()->create(['price' => 3000]);
            CartItem::factory()->forCart($cart)->forProduct($product2)->create();

            // Product 3: Low stock
            $product3 = Product::factory()->active()->create([
                'price'          => 2000,
                'stock_quantity' => 2,
                'manage_stock'   => true,
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product3)->withQuantity(10)->create();

            // Product 4: Price changed
            $product4 = Product::factory()->active()->create([
                'price'          => 4000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);
            CartItem::factory()->forCart($cart)->create([
                'product_id' => $product4->id,
                'quantity'   => 1,
                'unit_price' => 3000,
            ]);

            $cart->calculateTotals()->save();

            $result = $this->validationService->validateCart($cart->fresh());

            expect($result['removed_items'])->toHaveCount(2) // Deleted + Inactive
                ->and($result['updated_items'])->toHaveCount(2) // Low stock + Price changed
                ->and($result['alerts'])->toHaveCount(4);
        });
    });
});
