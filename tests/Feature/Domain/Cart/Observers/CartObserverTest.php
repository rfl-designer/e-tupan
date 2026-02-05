<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Domain\Marketing\Models\Coupon;
use App\Models\User;

describe('CartObserver', function () {
    describe('updating last_activity_at', function () {
        it('updates last_activity_at when cart item is added', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create([
                'last_activity_at' => now()->subHours(10),
            ]);
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            $oldActivityAt = $cart->last_activity_at;

            CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $cart->refresh();
            expect($cart->last_activity_at->gt($oldActivityAt))->toBeTrue();
        });

        it('updates last_activity_at when cart item quantity changes', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);
            $cart    = Cart::factory()->forUser($user)->create([
                'last_activity_at' => now()->subHours(10),
            ]);
            $item = CartItem::factory()->forCart($cart)->forProduct($product)->withQuantity(2)->create();

            $oldActivityAt = $cart->last_activity_at;

            // Update item quantity
            $item->update(['quantity' => 5]);

            $cart->refresh();
            expect($cart->last_activity_at->gt($oldActivityAt))->toBeTrue();
        });

        it('updates last_activity_at when cart item is deleted', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);
            $cart    = Cart::factory()->forUser($user)->create([
                'last_activity_at' => now()->subHours(10),
            ]);
            $item = CartItem::factory()->forCart($cart)->forProduct($product)->create();

            $oldActivityAt = $cart->last_activity_at;

            $item->delete();

            $cart->refresh();
            expect($cart->last_activity_at->gt($oldActivityAt))->toBeTrue();
        });

        it('updates last_activity_at when coupon is applied', function () {
            $user   = User::factory()->create();
            $coupon = Coupon::factory()->create();
            $cart   = Cart::factory()->forUser($user)->create([
                'last_activity_at' => now()->subHours(10),
            ]);

            $oldActivityAt = $cart->last_activity_at;

            $cart->update(['coupon_id' => $coupon->id]);

            $cart->refresh();
            expect($cart->last_activity_at->gt($oldActivityAt))->toBeTrue();
        });

        it('updates last_activity_at when shipping is calculated', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->create([
                'last_activity_at' => now()->subHours(10),
            ]);

            $oldActivityAt = $cart->last_activity_at;

            $cart->update([
                'shipping_zipcode' => '01310-100',
                'shipping_method'  => 'PAC',
                'shipping_cost'    => 2500,
            ]);

            $cart->refresh();
            expect($cart->last_activity_at->gt($oldActivityAt))->toBeTrue();
        });

        it('does not update last_activity_at on status change to abandoned', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(30),
            ]);

            $oldActivityAt = $cart->last_activity_at;

            $cart->update([
                'status'       => CartStatus::Abandoned,
                'abandoned_at' => now(),
            ]);

            $cart->refresh();
            expect($cart->last_activity_at->timestamp)->toBe($oldActivityAt->timestamp);
        });

        it('does not update last_activity_at on status change to converted', function () {
            $user = User::factory()->create();
            $cart = Cart::factory()->forUser($user)->active()->create([
                'last_activity_at' => now()->subHours(30),
            ]);

            $oldActivityAt = $cart->last_activity_at;

            $cart->update(['status' => CartStatus::Converted]);

            $cart->refresh();
            expect($cart->last_activity_at->timestamp)->toBe($oldActivityAt->timestamp);
        });
    });
});
