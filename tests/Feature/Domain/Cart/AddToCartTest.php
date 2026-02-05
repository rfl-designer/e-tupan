<?php

declare(strict_types = 1);

use App\Domain\Cart\Livewire\AddToCart;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Models\User;
use Livewire\Livewire;

describe('AddToCart Component', function () {
    describe('mounting', function () {
        it('mounts with product id', function () {
            $product = Product::factory()->active()->simple()->create();

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('productId', $product->id)
                ->assertSet('quantity', 1)
                ->assertSet('requiresVariant', false);
        });

        it('detects variable product requires variant', function () {
            $product = Product::factory()->active()->variable()->create();
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'is_active'  => true,
            ]);
            ProductVariant::factory()->create([
                'product_id' => $product->id,
                'is_active'  => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('requiresVariant', true)
                ->assertCount('variants', 2);
        });

        it('auto-selects variant when only one exists', function () {
            $product = Product::factory()->active()->variable()->create();
            $variant = ProductVariant::factory()->create([
                'product_id' => $product->id,
                'is_active'  => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('variantId', $variant->id);
        });
    });

    describe('quantity controls', function () {
        it('increments quantity', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('quantity', 1)
                ->call('increment')
                ->assertSet('quantity', 2)
                ->call('increment')
                ->assertSet('quantity', 3);
        });

        it('decrements quantity', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('quantity', 5)
                ->call('decrement')
                ->assertSet('quantity', 4);
        });

        it('does not decrement below 1', function () {
            $product = Product::factory()->active()->create(['stock_quantity' => 10]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('quantity', 1)
                ->call('decrement')
                ->assertSet('quantity', 1);
        });

        it('does not increment above max quantity', function () {
            $product = Product::factory()->active()->create([
                'stock_quantity' => 3,
                'manage_stock'   => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->assertSet('maxQuantity', 3)
                ->set('quantity', 3)
                ->call('increment')
                ->assertSet('quantity', 3);
        });
    });

    describe('adding to cart', function () {
        it('adds simple product to cart for guest', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('quantity', 2)
                ->call('add')
                ->assertSet('showModal', true)
                ->assertSet('errorMessage', null)
                ->assertDispatched('cart-updated');

            expect(CartItem::where('product_id', $product->id)->first())
                ->not->toBeNull()
                ->quantity->toBe(2)
                ->unit_price->toBe(5000);
        });

        it('adds simple product to cart for authenticated user', function () {
            $user    = User::factory()->create();
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            Livewire::actingAs($user)
                ->test(AddToCart::class, ['productId' => $product->id])
                ->set('quantity', 3)
                ->call('add')
                ->assertSet('showModal', true);

            $cart = Cart::forUser($user->id)->first();
            expect($cart)->not->toBeNull()
                ->and($cart->items)->toHaveCount(1)
                ->and($cart->items->first()->quantity)->toBe(3);
        });

        it('adds variant to cart', function () {
            $product = Product::factory()->active()->variable()->create();
            $variant = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'price'          => 6000,
                'stock_quantity' => 5,
                'is_active'      => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('variantId', $variant->id)
                ->set('quantity', 1)
                ->call('add')
                ->assertSet('showModal', true);

            expect(CartItem::where('variant_id', $variant->id)->first())
                ->not->toBeNull()
                ->unit_price->toBe(6000);
        });

        it('shows error when variant not selected for variable product', function () {
            $product = Product::factory()->active()->variable()->create();
            ProductVariant::factory()->count(2)->create([
                'product_id' => $product->id,
                'is_active'  => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('variantId', null)
                ->call('add')
                ->assertSet('showModal', false)
                ->assertSet('errorMessage', 'Selecione uma variacao do produto.');
        });

        it('shows error when insufficient stock', function () {
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 2,
                'manage_stock'   => true,
            ]);

            $component = Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('quantity', 5)
                ->call('add')
                ->assertSet('showModal', false);

            expect($component->get('errorMessage'))->toContain('Estoque insuficiente');
        });

        it('shows error for inactive product', function () {
            $product = Product::factory()->inactive()->simple()->create();

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->call('add')
                ->assertSet('showModal', false)
                ->assertSet('errorMessage', 'Este produto nao esta disponivel para compra.');
        });

        it('resets quantity after successful add', function () {
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('quantity', 5)
                ->call('add')
                ->assertSet('quantity', 1);
        });
    });

    describe('modal controls', function () {
        it('closes modal', function () {
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->call('add')
                ->assertSet('showModal', true)
                ->call('closeModal')
                ->assertSet('showModal', false)
                ->assertSet('addedItemName', null)
                ->assertSet('addedItemPrice', null);
        });

        it('redirects to cart', function () {
            $product = Product::factory()->active()->simple()->create([
                'stock_quantity' => 10,
                'manage_stock'   => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->call('add')
                ->call('goToCart')
                ->assertRedirect(route('cart.index'));
        });
    });

    describe('variant selection', function () {
        it('updates max quantity when variant is selected', function () {
            $product = Product::factory()->active()->variable()->create([
                'manage_stock' => true,
            ]);
            $variant1 = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'stock_quantity' => 5,
                'is_active'      => true,
            ]);
            $variant2 = ProductVariant::factory()->create([
                'product_id'     => $product->id,
                'stock_quantity' => 15,
                'is_active'      => true,
            ]);

            Livewire::test(AddToCart::class, ['productId' => $product->id])
                ->set('variantId', $variant1->id)
                ->assertSet('maxQuantity', 5)
                ->set('variantId', $variant2->id)
                ->assertSet('maxQuantity', 15);
        });
    });
});
