<?php

declare(strict_types = 1);

use App\Domain\Cart\Livewire\MiniCart;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Models\User;
use Livewire\Livewire;

describe('MiniCart Component', function () {
    describe('rendering', function () {
        it('renders successfully', function () {
            Livewire::test(MiniCart::class)
                ->assertStatus(200);
        });

        it('shows zero count when cart is empty', function () {
            Livewire::test(MiniCart::class)
                ->assertSet('itemCount', 0)
                ->assertSee('0');
        });

        it('shows empty state message when cart is empty', function () {
            Livewire::test(MiniCart::class)
                ->assertSee('Seu carrinho esta vazio');
        });
    });

    describe('cart with items', function () {
        it('shows correct item count', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price' => 5000,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 3);

            Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSet('itemCount', 3);
        });

        it('shows last items added to cart', function () {
            $user     = User::factory()->create();
            $products = Product::factory()->active()->simple()->count(5)->create([
                'price'          => 2500,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);

            foreach ($products as $product) {
                $cartService->addItem($cart, $product, 1);
            }

            $component = Livewire::actingAs($user)
                ->test(MiniCart::class);

            // Should show all 5 items
            foreach ($products as $product) {
                $component->assertSee($product->name);
            }
        });

        it('limits displayed items to maxItems', function () {
            $user     = User::factory()->create();
            $products = Product::factory()->active()->simple()->count(6)->create([
                'price'          => 2500,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);

            foreach ($products as $product) {
                $cartService->addItem($cart, $product, 1);
            }

            $component = Livewire::actingAs($user)
                ->test(MiniCart::class);

            // Should show maxItems (5 by default) items
            expect($component->get('items'))->toHaveCount(5);
        });

        it('shows product name, quantity and subtotal', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Camiseta Teste',
                'price'          => 4990,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSee('Camiseta Teste')
                ->assertSee('Qtd: 2')
                ->assertSee('99,80'); // 2 x R$ 49,90 = subtotal
        });

        it('shows cart subtotal', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price' => 10000,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSee('200,00'); // 2 x R$ 100,00
        });
    });

    describe('session cart', function () {
        it('loads cart from session for guests', function () {
            $product = Product::factory()->active()->simple()->create([
                'price' => 3000,
            ]);

            $cartService = new CartService();

            // Create a component first to get the session ID
            $component = Livewire::test(MiniCart::class);

            // Get the session ID from the Livewire request
            $sessionId = session()->getId();

            // Create cart with that session ID
            $cart = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($cart, $product, 2);

            // Re-test with same session
            Livewire::test(MiniCart::class)
                ->assertSet('itemCount', 2)
                ->assertSee($product->name);
        });
    });

    describe('navigation', function () {
        it('has link to cart page when cart has items', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create();

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSee('Ver Carrinho');
        });

        it('has link to checkout when cart has items', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create();

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSee('Finalizar Compra');
        });

        it('shows continue shopping link when empty', function () {
            Livewire::test(MiniCart::class)
                ->assertSee('Continuar comprando');
        });
    });

    describe('real-time updates', function () {
        it('listens to cart-updated event', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price' => 2500,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);

            $component = Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSet('itemCount', 0);

            // Add item to cart
            $cartService->addItem($cart, $product, 1);

            // Dispatch event to refresh
            $component->dispatch('cart-updated')
                ->assertSet('itemCount', 1);
        });

        it('refreshes when cart-updated event is dispatched', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create();

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            $component = Livewire::actingAs($user)
                ->test(MiniCart::class)
                ->assertSet('itemCount', 2);

            // Simulate another item being added elsewhere
            $cartService->addItem($cart, $product, 3);

            // Should update when event is dispatched
            $component->dispatch('cart-updated')
                ->assertSet('itemCount', 5);
        });
    });

    describe('dropdown behavior', function () {
        it('has dropdown toggle functionality', function () {
            Livewire::test(MiniCart::class)
                ->assertSet('isOpen', false)
                ->call('toggle')
                ->assertSet('isOpen', true)
                ->call('toggle')
                ->assertSet('isOpen', false);
        });

        it('closes dropdown when close is called', function () {
            Livewire::test(MiniCart::class)
                ->set('isOpen', true)
                ->call('close')
                ->assertSet('isOpen', false);
        });
    });
});
