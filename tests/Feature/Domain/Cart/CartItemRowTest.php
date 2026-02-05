<?php

declare(strict_types = 1);

use App\Domain\Cart\Livewire\CartItemRow;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Livewire\Livewire;

describe('CartItemRow Component', function () {
    describe('rendering', function () {
        it('renders successfully with item', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertStatus(200)
                ->assertSee($product->name);
        });

        it('shows quantity controls', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSet('quantity', 2)
                ->assertSeeHtml('wire:click="increment"')
                ->assertSeeHtml('wire:click="decrement"');
        });
    });

    describe('increment quantity', function () {
        it('increments quantity by one', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSet('quantity', 2)
                ->call('increment')
                ->assertSet('quantity', 3);

            // Verify in database
            expect($item->fresh()->quantity)->toBe(3);
        });

        it('does not increment beyond max stock', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 3,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 3);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSet('quantity', 3)
                ->call('increment')
                ->assertSet('quantity', 3); // Should not change

            expect($item->fresh()->quantity)->toBe(3);
        });

        it('dispatches cart-updated event after increment', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->call('increment')
                ->assertDispatched('cart-updated');
        });
    });

    describe('decrement quantity', function () {
        it('decrements quantity by one', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 3);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSet('quantity', 3)
                ->call('decrement')
                ->assertSet('quantity', 2);

            expect($item->fresh()->quantity)->toBe(2);
        });

        it('does not decrement below 1', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSet('quantity', 1)
                ->call('decrement')
                ->assertSet('quantity', 1); // Should not change

            expect($item->fresh()->quantity)->toBe(1);
        });

        it('dispatches cart-updated event after decrement', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->call('decrement')
                ->assertDispatched('cart-updated');
        });
    });

    describe('update quantity directly', function () {
        it('updates quantity from input', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->set('quantity', 5)
                ->call('updateQuantity')
                ->assertSet('quantity', 5);

            expect($item->fresh()->quantity)->toBe(5);
        });

        it('limits quantity to max stock when exceeding', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 5,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->set('quantity', 10)
                ->call('updateQuantity')
                ->assertSet('quantity', 5); // Limited to max stock

            expect($item->fresh()->quantity)->toBe(5);
        });

        it('sets minimum quantity to 1 when zero or negative', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->set('quantity', 0)
                ->call('updateQuantity')
                ->assertSet('quantity', 1);

            expect($item->fresh()->quantity)->toBe(1);
        });
    });

    describe('subtotal calculation', function () {
        it('updates subtotal when quantity changes', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->assertSee('100,00') // 2 x R$ 50,00
                ->call('increment')
                ->assertSee('150,00'); // 3 x R$ 50,00
        });
    });
});
