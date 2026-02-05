<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\{Product, ProductVariant};

describe('CartItem Model', function () {
    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $item = new CartItem();

            expect($item->getFillable())->toBe([
                'cart_id',
                'product_id',
                'variant_id',
                'quantity',
                'unit_price',
                'sale_price',
            ]);
        });
    });

    describe('casts', function () {
        it('casts quantity to integer', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create(['quantity' => 5]);

            expect($item->quantity)->toBeInt()->toBe(5);
        });

        it('casts prices to integer', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 9999,
                'sale_price' => 7999,
            ]);

            expect($item->unit_price)->toBeInt()->toBe(9999)
                ->and($item->sale_price)->toBeInt()->toBe(7999);
        });
    });

    describe('relationships', function () {
        it('belongs to a cart', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create();

            expect($item->cart)->toBeInstanceOf(Cart::class)
                ->and($item->cart->id)->toBe($cart->id);
        });

        it('belongs to a product', function () {
            $product = Product::factory()->create();
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->product)->toBeInstanceOf(Product::class)
                ->and($item->product->id)->toBe($product->id);
        });

        it('optionally belongs to a variant', function () {
            $product = Product::factory()->variable()->create();
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forVariant($variant)->create();

            expect($item->variant)->toBeInstanceOf(ProductVariant::class)
                ->and($item->variant->id)->toBe($variant->id);
        });

        it('has null variant for simple products', function () {
            $product = Product::factory()->simple()->create();
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->variant)->toBeNull();
        });
    });

    describe('price methods', function () {
        it('returns unit price as effective price when no sale', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => null,
            ]);

            expect($item->getEffectivePrice())->toBe(10000);
        });

        it('returns sale price as effective price when on sale', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 8000,
            ]);

            expect($item->getEffectivePrice())->toBe(8000);
        });

        it('calculates subtotal correctly', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 5000,
                'sale_price' => null,
                'quantity'   => 3,
            ]);

            expect($item->getSubtotal())->toBe(15000);
        });

        it('calculates subtotal with sale price', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 5000,
                'sale_price' => 4000,
                'quantity'   => 3,
            ]);

            expect($item->getSubtotal())->toBe(12000);
        });
    });

    describe('price accessors', function () {
        it('returns unit price in reais', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create(['unit_price' => 9999]);

            expect($item->unit_price_in_reais)->toBe(99.99);
        });

        it('returns sale price in reais', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create(['sale_price' => 7999]);

            expect($item->sale_price_in_reais)->toBe(79.99);
        });

        it('returns null for sale price in reais when null', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create(['sale_price' => null]);

            expect($item->sale_price_in_reais)->toBeNull();
        });

        it('returns effective price in reais', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 8000,
            ]);

            expect($item->effective_price_in_reais)->toBe(80.00);
        });

        it('returns subtotal in reais', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 5000,
                'sale_price' => null,
                'quantity'   => 2,
            ]);

            expect($item->subtotal_in_reais)->toBe(100.00);
        });
    });

    describe('sale detection', function () {
        it('detects item on sale', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 8000,
            ]);

            expect($item->isOnSale())->toBeTrue();
        });

        it('detects item not on sale when no sale price', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => null,
            ]);

            expect($item->isOnSale())->toBeFalse();
        });

        it('detects item not on sale when sale price equals unit price', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 10000,
            ]);

            expect($item->isOnSale())->toBeFalse();
        });

        it('detects item not on sale when sale price higher than unit price', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 12000,
            ]);

            expect($item->isOnSale())->toBeFalse();
        });
    });

    describe('discount percentage', function () {
        it('calculates discount percentage correctly', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => 7500,
            ]);

            expect($item->getDiscountPercentage())->toBe(25);
        });

        it('returns null when not on sale', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 10000,
                'sale_price' => null,
            ]);

            expect($item->getDiscountPercentage())->toBeNull();
        });

        it('returns null when unit price is zero', function () {
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->create([
                'unit_price' => 0,
                'sale_price' => 0,
            ]);

            expect($item->getDiscountPercentage())->toBeNull();
        });
    });

    describe('display name', function () {
        it('returns product name for simple product', function () {
            $product = Product::factory()->simple()->create(['name' => 'Camiseta Básica']);
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->getDisplayName())->toBe('Camiseta Básica');
        });

        it('returns variant name for variable product', function () {
            $product = Product::factory()->variable()->create(['name' => 'Camiseta']);
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forVariant($variant)->create();

            // Variant getName() returns product name + attributes
            expect($item->getDisplayName())->toContain('Camiseta');
        });
    });

    describe('stockable', function () {
        it('returns product as stockable for simple product', function () {
            $product = Product::factory()->simple()->create();
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->getStockable())->toBeInstanceOf(Product::class)
                ->and($item->getStockable()->id)->toBe($product->id);
        });

        it('returns variant as stockable for variable product', function () {
            $product = Product::factory()->variable()->create();
            $variant = ProductVariant::factory()->create(['product_id' => $product->id]);
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forVariant($variant)->create();

            expect($item->getStockable())->toBeInstanceOf(ProductVariant::class)
                ->and($item->getStockable()->id)->toBe($variant->id);
        });
    });

    describe('available stock', function () {
        it('returns stock quantity for managed stock product', function () {
            $product = Product::factory()->create([
                'manage_stock'   => true,
                'stock_quantity' => 50,
            ]);
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->getAvailableStock())->toBe(50);
        });

        it('returns unlimited stock for unmanaged stock product', function () {
            $product = Product::factory()->unlimitedStock()->create();
            $cart    = Cart::factory()->create();
            $item    = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            expect($item->getAvailableStock())->toBe(PHP_INT_MAX);
        });

        it('returns variant stock for variable product', function () {
            $product = Product::factory()->variable()->create();
            $variant = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'stock_quantity' => 25,
            ]);
            $cart = Cart::factory()->create();
            $item = CartItem::factory()->forCart($cart)->forVariant($variant)->create();

            expect($item->getAvailableStock())->toBe(25);
        });
    });
});
