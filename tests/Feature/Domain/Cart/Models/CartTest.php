<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Models\User;

describe('Cart Model', function () {
    describe('fillable attributes', function () {
        it('has correct fillable attributes', function () {
            $cart = new Cart();

            expect($cart->getFillable())->toBe([
                'user_id',
                'session_id',
                'coupon_id',
                'shipping_zipcode',
                'shipping_method',
                'shipping_cost',
                'shipping_days',
                'shipping_quote_id',
                'status',
                'subtotal',
                'discount',
                'total',
                'last_activity_at',
                'abandoned_at',
            ]);
        });
    });

    describe('casts', function () {
        it('casts status to CartStatus enum', function () {
            $cart = Cart::factory()->create(['status' => 'active']);

            expect($cart->status)->toBeInstanceOf(CartStatus::class)
                ->and($cart->status)->toBe(CartStatus::Active);
        });

        it('casts monetary values to integer', function () {
            $cart = Cart::factory()->create([
                'subtotal'      => 10000,
                'discount'      => 500,
                'total'         => 9500,
                'shipping_cost' => 1500,
            ]);

            expect($cart->subtotal)->toBeInt()->toBe(10000)
                ->and($cart->discount)->toBeInt()->toBe(500)
                ->and($cart->total)->toBeInt()->toBe(9500)
                ->and($cart->shipping_cost)->toBeInt()->toBe(1500);
        });

        it('casts dates correctly', function () {
            $cart = Cart::factory()->create([
                'last_activity_at' => now(),
                'abandoned_at'     => now(),
            ]);

            expect($cart->last_activity_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class)
                ->and($cart->abandoned_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
        });
    });

    describe('uuid primary key', function () {
        it('uses uuid as primary key', function () {
            $cart = Cart::factory()->create();

            expect($cart->id)->toBeString()
                ->and(strlen($cart->id))->toBe(36);
        });

        it('does not auto increment', function () {
            $cart = new Cart();

            expect($cart->incrementing)->toBeFalse();
        });
    });

    describe('relationships', function () {
        it('belongs to a user', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create();

            expect($cart->user)->toBeInstanceOf(User::class)
                ->and($cart->user->id)->toBe($user->id);
        });

        it('has many items', function () {
            $cart    = Cart::factory()->create();
            $product = Product::factory()->create();
            CartItem::factory()->forCart($cart)->forProduct($product)->create();
            CartItem::factory()->forCart($cart)->create();

            expect($cart->items)->toHaveCount(2)
                ->and($cart->items->first())->toBeInstanceOf(CartItem::class);
        });
    });

    describe('cart state methods', function () {
        it('detects empty cart', function () {
            $cart = Cart::factory()->create();

            expect($cart->isEmpty())->toBeTrue();
        });

        it('detects non-empty cart', function () {
            $cart = Cart::factory()->create();
            CartItem::factory()->forCart($cart)->create();

            expect($cart->isEmpty())->toBeFalse();
        });

        it('counts items correctly', function () {
            $cart = Cart::factory()->create();
            CartItem::factory()->forCart($cart)->withQuantity(3)->create();
            CartItem::factory()->forCart($cart)->withQuantity(2)->create();

            expect($cart->itemCount())->toBe(5);
        });

        it('counts unique items correctly', function () {
            $cart = Cart::factory()->create();
            CartItem::factory()->forCart($cart)->withQuantity(3)->create();
            CartItem::factory()->forCart($cart)->withQuantity(2)->create();

            expect($cart->uniqueItemCount())->toBe(2);
        });
    });

    describe('status helpers', function () {
        it('detects active cart', function () {
            $cart = Cart::factory()->active()->create();

            expect($cart->isActive())->toBeTrue()
                ->and($cart->isAbandoned())->toBeFalse()
                ->and($cart->isConverted())->toBeFalse();
        });

        it('detects abandoned cart', function () {
            $cart = Cart::factory()->abandoned()->create();

            expect($cart->isAbandoned())->toBeTrue()
                ->and($cart->isActive())->toBeFalse()
                ->and($cart->isConverted())->toBeFalse();
        });

        it('detects converted cart', function () {
            $cart = Cart::factory()->converted()->create();

            expect($cart->isConverted())->toBeTrue()
                ->and($cart->isActive())->toBeFalse()
                ->and($cart->isAbandoned())->toBeFalse();
        });
    });

    describe('scopes', function () {
        it('filters active carts', function () {
            Cart::factory()->count(2)->active()->create();
            Cart::factory()->abandoned()->create();
            Cart::factory()->converted()->create();

            expect(Cart::active()->count())->toBe(2);
        });

        it('filters abandoned carts', function () {
            Cart::factory()->count(2)->active()->create();
            Cart::factory()->abandoned()->create();

            expect(Cart::abandoned()->count())->toBe(1);
        });

        it('filters by user', function () {
            $user = User::factory()->create();
            Cart::factory()->forUser($user)->count(2)->create();
            Cart::factory()->create();

            expect(Cart::forUser($user->id)->count())->toBe(2);
        });

        it('filters by session', function () {
            $sessionId = 'test-session-123';
            Cart::factory()->forSession($sessionId)->count(2)->create();
            Cart::factory()->create();

            expect(Cart::forSession($sessionId)->count())->toBe(2);
        });
    });

    describe('price accessors', function () {
        it('returns subtotal in reais', function () {
            $cart = Cart::factory()->create(['subtotal' => 9999]);

            expect($cart->subtotal_in_reais)->toBe(99.99);
        });

        it('returns discount in reais', function () {
            $cart = Cart::factory()->create(['discount' => 1500]);

            expect($cart->discount_in_reais)->toBe(15.00);
        });

        it('returns total in reais', function () {
            $cart = Cart::factory()->create(['total' => 8499]);

            expect($cart->total_in_reais)->toBe(84.99);
        });

        it('returns shipping cost in reais', function () {
            $cart = Cart::factory()->withShipping(2500)->create();

            expect($cart->shipping_cost_in_reais)->toBe(25.00);
        });

        it('returns null for shipping cost in reais when null', function () {
            $cart = Cart::factory()->create(['shipping_cost' => null]);

            expect($cart->shipping_cost_in_reais)->toBeNull();
        });
    });

    describe('calculate totals', function () {
        it('calculates totals correctly', function () {
            $cart = Cart::factory()->create(['discount' => 500, 'shipping_cost' => 1500]);
            CartItem::factory()->forCart($cart)->withPrice(5000)->withQuantity(2)->create();
            CartItem::factory()->forCart($cart)->withPrice(3000)->withQuantity(1)->create();

            $cart->load('items');
            $cart->calculateTotals();

            // Subtotal: (5000 * 2) + (3000 * 1) = 13000
            // Total: 13000 - 500 + 1500 = 14000
            expect($cart->subtotal)->toBe(13000)
                ->and($cart->total)->toBe(14000);
        });

        it('calculates totals with sale prices', function () {
            $cart = Cart::factory()->create(['discount' => 0, 'shipping_cost' => null]);
            CartItem::factory()->forCart($cart)->withPrice(5000, 4000)->withQuantity(2)->create();

            $cart->load('items');
            $cart->calculateTotals();

            // Subtotal: 4000 * 2 = 8000 (uses sale_price)
            expect($cart->subtotal)->toBe(8000)
                ->and($cart->total)->toBe(8000);
        });
    });

    describe('touch last activity', function () {
        it('updates last activity timestamp', function () {
            $cart        = Cart::factory()->create(['last_activity_at' => now()->subHour()]);
            $oldActivity = $cart->last_activity_at;

            $cart->touchLastActivity();

            expect($cart->last_activity_at)->toBeGreaterThan($oldActivity);
        });
    });
});
