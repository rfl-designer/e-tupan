<?php

declare(strict_types = 1);

use App\Domain\Cart\Livewire\CartPage;
use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Models\User;
use Livewire\Livewire;

describe('CartPage Component', function () {
    describe('rendering', function () {
        it('renders successfully', function () {
            Livewire::test(CartPage::class)
                ->assertStatus(200);
        });

        it('shows empty cart state when no items', function () {
            Livewire::test(CartPage::class)
                ->assertSee('Seu carrinho esta vazio')
                ->assertSee('Continuar comprando');
        });
    });

    describe('cart items display', function () {
        it('shows cart items with product details', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Camiseta Premium',
                'price'          => 9990,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Camiseta Premium')
                ->assertSee('99,90') // Unit price
                ->assertSee('199,80'); // Subtotal (2 x 99.90)
        });

        it('shows variant information for variable products', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->variable()->create([
                'name' => 'Camisa Polo',
            ]);
            $variant = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'price'          => 15000,
                'stock_quantity' => 5,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1, $variant);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Camisa Polo')
                ->assertSee('150,00');
        });

        it('shows sale price indicator for products on sale', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto Promocional',
                'price'          => 10000,
                'sale_price'     => 7500,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Produto Promocional')
                ->assertSee('75,00'); // Sale price
        });
    });

    describe('cart summary', function () {
        it('shows subtotal', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 3);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Subtotal')
                ->assertSee('150,00'); // 3 x R$ 50,00
        });

        it('shows total', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 10000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Total')
                ->assertSee('200,00');
        });

        it('shows item count', function () {
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
            $cartService->addItem($cart, $product1, 2);
            $cartService->addItem($cart, $product2, 1);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('3 itens');
        });
    });

    describe('navigation', function () {
        it('has continue shopping link', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Continuar comprando');
        });

        it('has checkout button when cart has items', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSee('Finalizar Compra');
        });
    });

    describe('guest cart', function () {
        it('shows cart for guest users via session', function () {
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto para Visitante',
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            // Create cart with session
            $cartService = new CartService();
            Livewire::test(CartPage::class); // Initialize session

            $sessionId = session()->getId();
            $cart      = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($cart, $product, 1);

            Livewire::test(CartPage::class)
                ->assertSee('Produto para Visitante')
                ->assertSee('50,00');
        });
    });

    describe('real-time updates', function () {
        it('refreshes when cart-updated event is dispatched', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 3000,
                'stock_quantity' => 20,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            $component = Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSet('itemCount', 1);

            // Add more items
            $cartService->addItem($cart, $product, 2);

            // Refresh via event
            $component->dispatch('cart-updated')
                ->assertSet('itemCount', 3);
        });
    });

    describe('cart validation on mount', function () {
        it('shows validation alerts when product becomes unavailable', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto que sera removido',
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            // Delete the product
            $product->delete();

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSet('validationAlerts', fn ($alerts) => count($alerts) > 0)
                ->assertSee('foi removido do carrinho');
        });

        it('shows validation alert when stock is insufficient', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto com estoque baixo',
                'price'          => 5000,
                'stock_quantity' => 3,
                'manage_stock'   => true,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 3);

            // Reduce stock
            $product->update(['stock_quantity' => 1]);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSet('validationAlerts', fn ($alerts) => count($alerts) > 0)
                ->assertSee('quantidade')
                ->assertSee('ajustada');
        });

        it('shows validation alert when price changes', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'name'           => 'Produto com preco alterado',
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 1);

            // Change price
            $product->update(['price' => 6000]);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSet('validationAlerts', fn ($alerts) => count($alerts) > 0)
                ->assertSee('atualizado');
        });

        it('does not show alerts for valid cart', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(userId: $user->id);
            $cartService->addItem($cart, $product, 2);

            Livewire::actingAs($user)
                ->test(CartPage::class)
                ->assertSet('validationAlerts', []);
        });
    });
});
