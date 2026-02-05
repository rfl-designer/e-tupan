<?php

declare(strict_types = 1);

use App\Domain\Cart\Jobs\CleanOldCartsJob;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockReservation;
use App\Models\User;

describe('CleanOldCartsJob', function () {
    describe('cleaning abandoned carts', function () {
        it('removes abandoned carts older than 90 days', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDays(91),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->toBeNull();
        });

        it('does not remove abandoned carts newer than 90 days', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDays(85),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->not->toBeNull();
        });

        it('removes cart items along with abandoned cart', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDays(100),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(3)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(CartItem::where('cart_id', $cart->id)->count())->toBe(0);
        });

        it('releases stock reservations for abandoned carts', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDays(95),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(3)->create();

            // Create stock reservation
            StockReservation::create([
                'stockable_type' => Product::class,
                'stockable_id'   => $product->id,
                'cart_id'        => $cart->id,
                'quantity'       => 3,
                'expires_at'     => now()->subHours(24),
            ]);

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(StockReservation::forCart($cart->id)->count())->toBe(0);
        });
    });

    describe('cleaning empty carts', function () {
        it('removes empty active carts older than 7 days', function () {
            $user = User::factory()->create();

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subDays(8),
            ]);

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->toBeNull();
        });

        it('does not remove empty carts newer than 7 days', function () {
            $user = User::factory()->create();

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subDays(5),
            ]);

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->not->toBeNull();
        });

        it('does not remove carts with items', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subDays(10),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->not->toBeNull();
        });

        it('removes empty abandoned carts older than empty retention', function () {
            $user = User::factory()->create();

            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'last_activity_at' => now()->subDays(10),
                'abandoned_at'     => now()->subDays(10),
            ]);

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->toBeNull();
        });
    });

    describe('job execution', function () {
        it('handles multiple carts in a single run', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 30]);

            // Old abandoned carts
            for ($i = 0; $i < 5; $i++) {
                $user = User::factory()->create();
                $cart = Cart::factory()->forUser($user)->abandoned()->create([
                    'abandoned_at' => now()->subDays(100 + $i),
                ]);
                CartItem::factory()->forCart($cart)->forProduct($product)->create();
            }

            // Old empty carts
            for ($i = 0; $i < 3; $i++) {
                $user = User::factory()->create();
                Cart::factory()->forUser($user)->active()->create([
                    'last_activity_at' => now()->subDays(10 + $i),
                ]);
            }

            // Should remain
            $activeUser = User::factory()->create();
            $activeCart = Cart::factory()->forUser($activeUser)->active()->create([
                'last_activity_at' => now()->subDays(2),
            ]);
            CartItem::factory()->forCart($activeCart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            // Only the active cart with items should remain
            expect(Cart::count())->toBe(1)
                ->and(Cart::first()->id)->toBe($activeCart->id);
        });

        it('does not remove converted carts', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->converted()->create([
                'created_at' => now()->subDays(120),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->not->toBeNull();
        });

        it('uses configurable retention days for abandoned carts', function () {
            config(['cart.abandoned_retention_days' => 30]);

            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            // 35 days old abandoned cart
            $cart = Cart::factory()->forUser($user)->abandoned()->create([
                'abandoned_at' => now()->subDays(35),
            ]);
            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->toBeNull();
        });

        it('uses configurable retention days for empty carts', function () {
            config(['cart.empty_retention_days' => 3]);

            $user = User::factory()->create();

            // 5 days old empty cart
            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subDays(5),
            ]);

            $job = new CleanOldCartsJob();
            $job->handle();

            expect(Cart::find($cart->id))->toBeNull();
        });
    });
});
