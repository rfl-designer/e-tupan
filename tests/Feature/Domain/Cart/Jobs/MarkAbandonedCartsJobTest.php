<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Jobs\MarkAbandonedCartsJob;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Helper to create cart item without triggering observers.
 */
function createCartItemWithoutObserver(Cart $cart, Product $product, int $quantity = 1): CartItem
{
    $item = CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity($quantity)->make();
    DB::table('cart_items')->insert([
        'cart_id'    => $cart->id,
        'product_id' => $item->product_id,
        'variant_id' => $item->variant_id,
        'quantity'   => $item->quantity,
        'unit_price' => $item->unit_price,
        'sale_price' => $item->sale_price,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return CartItem::where('cart_id', $cart->id)->first();
}

describe('MarkAbandonedCartsJob', function () {
    describe('marking carts as abandoned', function () {
        it('marks active carts with items as abandoned after 24 hours', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(25),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Abandoned)
                ->and($cart->abandoned_at)->not->toBeNull();
        });

        it('does not mark carts with recent activity', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(12),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Active)
                ->and($cart->abandoned_at)->toBeNull();
        });

        it('does not mark empty carts as abandoned', function () {
            $user = User::factory()->create();

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(48),
            ]);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Active)
                ->and($cart->abandoned_at)->toBeNull();
        });

        it('does not mark already abandoned carts', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $abandonedAt = now()->subDays(3);
            $cart        = Cart::factory()->forUser($user)->abandoned()->create([
                'last_activity_at' => now()->subDays(5),
                'abandoned_at'     => $abandonedAt,
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Abandoned)
                ->and($cart->abandoned_at->timestamp)->toBe($abandonedAt->timestamp);
        });

        it('does not mark converted carts as abandoned', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->converted()->create([
                'last_activity_at' => now()->subDays(5),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Converted);
        });

        it('marks multiple eligible carts in a single run', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 20]);

            $carts = collect();

            for ($i = 0; $i < 5; $i++) {
                $user = User::factory()->create();
                $cart = Cart::factory()->forUser($user)->active()->create([
                    'last_activity_at' => now()->subHours(30 + $i),
                ]);
                createCartItemWithoutObserver($cart, $product);
                $carts->push($cart);
            }

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            foreach ($carts as $cart) {
                $cart->refresh();
                expect($cart->status)->toBe(CartStatus::Abandoned)
                    ->and($cart->abandoned_at)->not->toBeNull();
            }
        });

        it('handles carts at exactly 24 hours threshold', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            // Cart at exactly 24 hours should be marked
            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(24),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Abandoned);
        });

        it('sets abandoned_at to current timestamp', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(48),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $beforeJob = now()->subSecond();
            $job       = new MarkAbandonedCartsJob();
            $job->handle();
            $afterJob = now()->addSecond();

            $cart->refresh();
            expect($cart->abandoned_at)->not->toBeNull()
                ->and($cart->abandoned_at->gte($beforeJob))->toBeTrue()
                ->and($cart->abandoned_at->lte($afterJob))->toBeTrue();
        });

        it('handles guest carts (session-based)', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $cart = Cart::factory()->forSession('guest-session-123')->active()->create([
                'last_activity_at' => now()->subHours(36),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Abandoned)
                ->and($cart->abandoned_at)->not->toBeNull();
        });
    });

    describe('job configuration', function () {
        it('uses default inactivity hours from config', function () {
            config(['cart.abandonment_hours' => 48]);

            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            // Cart at 30 hours (less than 48)
            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(30),
            ]);
            createCartItemWithoutObserver($cart, $product);

            $job = new MarkAbandonedCartsJob();
            $job->handle();

            $cart->refresh();
            expect($cart->status)->toBe(CartStatus::Active);
        });
    });
});
