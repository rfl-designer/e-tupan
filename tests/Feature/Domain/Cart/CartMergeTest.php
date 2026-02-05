<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\Cart;
use App\Domain\Cart\Services\{CartMergeService, CartService};
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockReservation;
use App\Models\User;

describe('CartMergeService', function () {
    describe('mergeOnLogin', function () {
        it('creates new user cart when no carts exist', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            expect($cart)->toBeInstanceOf(Cart::class)
                ->and($cart->user_id)->toBe($user->id)
                ->and($cart->session_id)->toBeNull()
                ->and($cart->status)->toBe(CartStatus::Active);
        });

        it('returns existing user cart when no session cart exists', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $cartService  = new CartService();
            $existingCart = $cartService->getOrCreate(userId: $user->id);

            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);
            $cartService->addItem($existingCart, $product, 2);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            expect($cart->id)->toBe($existingCart->id)
                ->and($cart->itemCount())->toBe(2);
        });

        it('transfers session cart to user when user has no cart', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $cartService = new CartService();
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);

            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);
            $cartService->addItem($sessionCart, $product, 3);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            expect($cart->id)->toBe($sessionCart->id)
                ->and($cart->user_id)->toBe($user->id)
                ->and($cart->session_id)->toBeNull()
                ->and($cart->itemCount())->toBe(3);
        });

        it('merges session cart items into user cart', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product1 = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);
            $product2 = Product::factory()->active()->simple()->create([
                'price'          => 3000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();

            // User cart with product1
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product1, 2);

            // Session cart with product2
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product2, 1);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            expect($cart->id)->toBe($userCart->id)
                ->and($cart->uniqueItemCount())->toBe(2)
                ->and($cart->itemCount())->toBe(3); // 2 + 1
        });

        it('combines quantities for same product', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 20,
            ]);

            $cartService = new CartService();

            // User cart with 2 of product
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product, 2);

            // Session cart with 3 of same product
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product, 3);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            expect($cart->uniqueItemCount())->toBe(1)
                ->and($cart->itemCount())->toBe(5); // 2 + 3
        });

        it('respects stock limit when combining quantities', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 5, // Only 5 in stock
            ]);

            $cartService = new CartService();

            // User cart with 3 of product
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product, 3);

            // Session cart with 4 of same product (would exceed stock)
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product, 2);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            // Should be limited to 5 (stock limit)
            expect($cart->itemCount())->toBe(5);
        });

        it('deletes session cart after merge', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();

            // User cart
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product, 1);

            // Session cart
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product, 2);

            $sessionCartId = $sessionCart->id;

            $mergeService = new CartMergeService();
            $mergeService->mergeOnLogin($user, $sessionId);

            expect(Cart::find($sessionCartId))->toBeNull();
        });

        it('updates stock reservations after merge', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();

            // User cart with 2 of product
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product, 2);

            // Session cart with 3 of same product
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product, 3);

            // Should have 2 reservations before merge
            expect(StockReservation::count())->toBe(2);

            $mergeService = new CartMergeService();
            $mergeService->mergeOnLogin($user, $sessionId);

            // Should have 1 reservation after merge with combined quantity
            expect(StockReservation::count())->toBe(1);
            expect(StockReservation::first()->quantity)->toBe(5);
        });

        it('recalculates cart totals after merge', function () {
            $user      = User::factory()->create();
            $sessionId = 'test-session-123';

            $product1 = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);
            $product2 = Product::factory()->active()->simple()->create([
                'price'          => 3000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();

            // User cart: 2 x R$ 50 = R$ 100
            $userCart = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($userCart, $product1, 2);

            // Session cart: 1 x R$ 30 = R$ 30
            $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($sessionCart, $product2, 1);

            $mergeService = new CartMergeService();
            $cart         = $mergeService->mergeOnLogin($user, $sessionId);

            // Total should be R$ 130 = 13000 centavos
            expect($cart->total)->toBe(13000);
        });
    });
});
