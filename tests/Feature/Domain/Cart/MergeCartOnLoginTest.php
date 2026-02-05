<?php

declare(strict_types = 1);

use App\Domain\Cart\Listeners\MergeCartOnLogin;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Illuminate\Auth\Events\Login;

describe('MergeCartOnLogin Listener', function () {
    it('merges session cart when customer logs in', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->active()->simple()->create([
            'stock_quantity' => 10,
        ]);

        // Set up session
        session()->start();
        $sessionId = session()->getId();

        // Create session cart
        $cartService = new CartService();
        $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
        $cartService->addItem($sessionCart, $product, 3);

        // Trigger login event
        $event    = new Login('web', $user, false);
        $listener = new MergeCartOnLogin(new \App\Domain\Cart\Services\CartMergeService());
        $listener->handle($event);

        // Check that cart is now user's
        $userCart = $cartService->getForUser($user->id);

        expect($userCart)->not->toBeNull()
            ->and($userCart->itemCount())->toBe(3)
            ->and($userCart->session_id)->toBeNull();
    });

    it('does not merge for admin guard', function () {
        $admin   = \App\Domain\Admin\Models\Admin::factory()->create();
        $product = Product::factory()->active()->simple()->create([
            'stock_quantity' => 10,
        ]);

        // Set up session
        session()->start();
        $sessionId = session()->getId();

        // Create session cart
        $cartService = new CartService();
        $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
        $cartService->addItem($sessionCart, $product, 2);

        // Trigger login event for admin guard
        $event    = new Login('admin', $admin, false);
        $listener = new MergeCartOnLogin(new \App\Domain\Cart\Services\CartMergeService());
        $listener->handle($event);

        // Session cart should still exist
        $sessionCartAfter = $cartService->getForSession($sessionId);

        expect($sessionCartAfter)->not->toBeNull()
            ->and($sessionCartAfter->itemCount())->toBe(2);
    });

    it('combines quantities when both carts have same product', function () {
        $user    = User::factory()->create();
        $product = Product::factory()->active()->simple()->create([
            'stock_quantity' => 20,
        ]);

        // Set up session
        session()->start();
        $sessionId = session()->getId();

        $cartService = new CartService();

        // Create user cart
        $userCart = $cartService->getOrCreate(userId: $user->id);
        $cartService->addItem($userCart, $product, 2);

        // Create session cart with same product
        $sessionCart = $cartService->getOrCreate(sessionId: $sessionId);
        $cartService->addItem($sessionCart, $product, 3);

        // Trigger login event
        $event    = new Login('web', $user, false);
        $listener = new MergeCartOnLogin(new \App\Domain\Cart\Services\CartMergeService());
        $listener->handle($event);

        // Check combined quantity
        $userCartAfter = $cartService->getForUser($user->id);

        expect($userCartAfter)->not->toBeNull()
            ->and($userCartAfter->itemCount())->toBe(5);
    });
});
