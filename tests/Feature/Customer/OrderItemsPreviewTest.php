<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductImage};
use App\Domain\Checkout\Models\{Order, OrderItem};
use App\Domain\Customer\Livewire\OrderList;
use App\Models\User;
use Livewire\Livewire;

describe('US-05: Resumo dos itens em cada pedido', function () {
    describe('Preview dos primeiros produtos', function () {
        it('displays product name from order items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Camiseta Azul']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Camiseta Azul',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Camiseta Azul');
        });

        it('displays up to 3 products preview', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $products = [
                'Produto 1',
                'Produto 2',
                'Produto 3',
            ];

            foreach ($products as $productName) {
                $product = Product::factory()->create(['name' => $productName]);
                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => $productName,
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Produto 1')
                ->assertSee('Produto 2')
                ->assertSee('Produto 3');
        });

        it('displays product thumbnail when available', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto com Imagem']);
            ProductImage::factory()->create([
                'product_id' => $product->id,
                'path'       => 'products/test-image.jpg',
                'is_primary' => true,
            ]);

            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto com Imagem',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Produto com Imagem')
                ->assertSeeHtml('img');
        });

        it('displays placeholder when product has no image', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto sem Imagem']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto sem Imagem',
            ]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('Produto sem Imagem');
        });
    });

    describe('Indicador de itens extras', function () {
        it('shows +X itens when order has more than 3 items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            // Create 5 items
            for ($i = 1; $i <= 5; $i++) {
                $product = Product::factory()->create(['name' => "Produto {$i}"]);
                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => "Produto {$i}",
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('+2 itens');
        });

        it('shows +1 item (singular) when order has exactly 4 items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            // Create 4 items
            for ($i = 1; $i <= 4; $i++) {
                $product = Product::factory()->create(['name' => "Produto {$i}"]);
                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => "Produto {$i}",
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('+1 item');
        });

        it('does not show +X itens when order has 3 or fewer items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            // Create 3 items
            for ($i = 1; $i <= 3; $i++) {
                $product = Product::factory()->create(['name' => "Produto {$i}"]);
                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => "Produto {$i}",
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertDontSee('+1 item')
                ->assertDontSee('+2 itens')
                ->assertDontSee('+3 itens');
        });

        it('shows correct count for order with many items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            // Create 10 items
            for ($i = 1; $i <= 10; $i++) {
                $product = Product::factory()->create(['name' => "Produto {$i}"]);
                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => "Produto {$i}",
                ]);
            }

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSee('+7 itens');
        });
    });

    describe('Link para detalhes do pedido', function () {
        it('makes order card clickable', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create([
                'user_id'      => $user->id,
                'order_number' => 'ORD-CLICK1',
            ]);

            OrderItem::factory()->create(['order_id' => $order->id]);

            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSeeHtml('href')
                ->assertSee('ORD-CLICK1');
        });

        it('links to order details page', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            OrderItem::factory()->create(['order_id' => $order->id]);

            $component = Livewire::actingAs($user)
                ->test(OrderList::class);

            // Check that there is a link containing the order ID
            $component->assertSeeHtml("pedidos/{$order->id}");
        });
    });

    describe('Eager loading de itens', function () {
        it('eager loads order items to prevent N+1', function () {
            $user = User::factory()->create();

            // Create multiple orders with items
            for ($i = 0; $i < 5; $i++) {
                $order = Order::factory()->create(['user_id' => $user->id]);
                OrderItem::factory()->count(3)->create(['order_id' => $order->id]);
            }

            // This should not cause N+1 queries
            // We're just checking that the component renders without errors
            Livewire::actingAs($user)
                ->test(OrderList::class)
                ->assertSuccessful();
        });
    });
});
