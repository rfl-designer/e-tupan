<?php

declare(strict_types = 1);

use App\Domain\Cart\Livewire\CartItemRow;
use App\Domain\Cart\Models\CartItem;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Models\StockReservation;
use App\Models\User;
use Livewire\Livewire;

describe('Remove Item', function () {
    describe('remove button', function () {
        it('has remove button in cart item row', function () {
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
                ->assertSeeHtml('wire:click="remove"');
        });
    });

    describe('removing item', function () {
        it('removes item from cart', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            expect(CartItem::count())->toBe(1);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->call('remove');

            expect(CartItem::count())->toBe(0);
        });

        it('releases stock reservation when item is removed', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);

            expect(StockReservation::count())->toBe(1);
            expect(StockReservation::first()->quantity)->toBe(2);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->call('remove');

            expect(StockReservation::count())->toBe(0);
        });

        it('dispatches cart-updated event after removal', function () {
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
                ->call('remove')
                ->assertDispatched('cart-updated');
        });

        it('dispatches item-removed event with item data', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto para Remover',
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);
            $itemId      = $item->id;

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $itemId])
                ->call('remove')
                ->assertDispatched('item-removed', itemId: $itemId);
        });

        it('updates cart totals after removal', function () {
            $user     = User::factory()->create();
            $product1 = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);
            $product2 = Product::factory()->active()->simple()->create([
                'price'          => 3000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item1       = $cartService->addItem($cart, $product1, 2);
            $cartService->addItem($cart, $product2, 1);

            // Initial total: 2x50 + 1x30 = R$ 130,00 = 13000 centavos
            expect($cart->fresh()->total)->toBe(13000);

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item1->id])
                ->call('remove');

            // After removal: 1x30 = R$ 30,00 = 3000 centavos
            expect($cart->fresh()->total)->toBe(3000);
        });
    });

    describe('undo functionality', function () {
        it('provides item data for undo in event', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto Teste',
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 2);
            $itemId      = $item->id;

            $component = Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $itemId])
                ->call('remove');

            // Event should contain data needed for undo
            $component->assertDispatched('item-removed', function ($name, $params) use ($itemId) {
                return $params['itemId'] === $itemId
                    && $params['productId'] !== null
                    && $params['quantity'] === 2;
            });
        });
    });

    describe('empty cart after removal', function () {
        it('cart becomes empty after removing last item', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $item        = $cartService->addItem($cart, $product, 1);

            expect($cart->fresh()->isEmpty())->toBeFalse();

            Livewire::actingAs($user)
                ->test(CartItemRow::class, ['itemId' => $item->id])
                ->call('remove');

            expect($cart->fresh()->isEmpty())->toBeTrue();
        });
    });
});
