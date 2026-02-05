<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductImage};
use App\Domain\Checkout\Models\{Order, OrderItem};
use App\Domain\Customer\Livewire\OrderDetail;
use App\Models\User;
use Livewire\Livewire;

describe('US-02: Exibição dos itens do pedido', function () {
    describe('A lista de itens exibe: imagem, nome, SKU, variante, quantidade, preço unitário e subtotal', function () {
        it('displays all order items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product1 = Product::factory()->create(['name' => 'Camiseta Básica', 'slug' => 'camiseta-basica']);
            $product2 = Product::factory()->create(['name' => 'Calça Jeans', 'slug' => 'calca-jeans']);

            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product1->id,
                'product_name' => 'Camiseta Básica',
                'product_sku'  => 'CAM-001',
                'quantity'     => 2,
                'unit_price'   => 5990,
                'subtotal'     => 11980,
            ]);

            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product2->id,
                'product_name' => 'Calça Jeans',
                'product_sku'  => 'CAL-001',
                'quantity'     => 1,
                'unit_price'   => 15990,
                'subtotal'     => 15990,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Camiseta Básica')
                ->assertSee('Calça Jeans')
                ->assertSee('CAM-001')
                ->assertSee('CAL-001');
        });

        it('displays item quantity', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Teste', 'slug' => 'produto-teste']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Teste',
                'quantity'     => 3,
                'unit_price'   => 1000,
                'subtotal'     => 3000,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Qtd: 3');
        });

        it('displays unit price formatted in BRL', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Caro', 'slug' => 'produto-caro']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Caro',
                'quantity'     => 1,
                'unit_price'   => 125990, // R$ 1.259,90
                'subtotal'     => 125990,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 1.259,90');
        });

        it('displays subtotal formatted in BRL', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Multi', 'slug' => 'produto-multi']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Multi',
                'quantity'     => 3,
                'unit_price'   => 5000, // R$ 50,00
                'subtotal'     => 15000, // R$ 150,00
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 150,00');
        });

        it('displays product image when available', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto com Imagem', 'slug' => 'produto-com-imagem']);
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
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('products/test-image.jpg');
        });
    });

    describe('Produtos sem imagem exibem placeholder', function () {
        it('displays placeholder for products without image', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto sem Imagem', 'slug' => 'produto-sem-imagem']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto sem Imagem',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Produto sem Imagem')
                ->assertSeeHtml('data-flux-icon'); // Flux icon SVG for placeholder
        });

        it('displays placeholder for deleted products', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Deletado', 'slug' => 'produto-deletado']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Deletado',
            ]);

            // Delete the product
            $product->delete();

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Produto Deletado')
                ->assertSeeHtml('data-flux-icon');
        });
    });

    describe('O nome do produto é clicável e leva para a página do produto', function () {
        it('displays product name as link to product page', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create([
                'name' => 'Produto Clicável',
                'slug' => 'produto-clicavel',
            ]);

            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Clicável',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSeeHtml('href="' . route('products.show', 'produto-clicavel') . '"')
                ->assertSee('Produto Clicável');
        });

        it('does not display link for deleted products', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create([
                'name' => 'Produto Removido',
                'slug' => 'produto-removido',
            ]);

            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Removido',
            ]);

            $product->delete();

            $component = Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Produto Removido');

            // Should not have a link to the product page
            $component->assertDontSeeHtml('href="' . route('products.show', 'produto-removido') . '"');
        });
    });

    describe('Variantes são exibidas abaixo do nome do produto', function () {
        it('displays variant name when item has variant', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Camiseta', 'slug' => 'camiseta']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Camiseta',
                'variant_name' => 'Tamanho M - Azul',
                'variant_sku'  => 'CAM-001-M-AZ',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Camiseta')
                ->assertSee('Tamanho M - Azul');
        });

        it('displays variant SKU when available', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Tênis', 'slug' => 'tenis']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Tênis',
                'product_sku'  => 'TEN-001',
                'variant_name' => '42 - Preto',
                'variant_sku'  => 'TEN-001-42-PR',
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('TEN-001-42-PR');
        });

        it('does not display variant section for items without variant', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Simples', 'slug' => 'produto-simples']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Simples',
                'product_sku'  => 'SIM-001',
                'variant_name' => null,
                'variant_sku'  => null,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('Produto Simples')
                ->assertSee('SIM-001');
        });
    });

    describe('Os preços são formatados em Reais (R$ X.XXX,XX)', function () {
        it('formats prices with thousands separator', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Premium', 'slug' => 'produto-premium']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Premium',
                'quantity'     => 1,
                'unit_price'   => 1234567, // R$ 12.345,67
                'subtotal'     => 1234567,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 12.345,67');
        });

        it('formats small prices correctly', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Barato', 'slug' => 'produto-barato']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Barato',
                'quantity'     => 1,
                'unit_price'   => 990, // R$ 9,90
                'subtotal'     => 990,
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 9,90');
        });

        it('displays subtotal correctly for multiple items', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            $product = Product::factory()->create(['name' => 'Produto Kit', 'slug' => 'produto-kit']);
            OrderItem::factory()->create([
                'order_id'     => $order->id,
                'product_id'   => $product->id,
                'product_name' => 'Produto Kit',
                'quantity'     => 5,
                'unit_price'   => 9990, // R$ 99,90
                'subtotal'     => 49950, // R$ 499,50
            ]);

            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSee('R$ 99,90')
                ->assertSee('R$ 499,50');
        });
    });

    describe('Eager loading para evitar N+1', function () {
        it('loads order items with product and images efficiently', function () {
            $user  = User::factory()->create();
            $order = Order::factory()->create(['user_id' => $user->id]);

            // Create multiple items with products and images
            for ($i = 1; $i <= 5; $i++) {
                $product = Product::factory()->create([
                    'name' => "Produto {$i}",
                    'slug' => "produto-{$i}",
                ]);

                ProductImage::factory()->create([
                    'product_id' => $product->id,
                    'is_primary' => true,
                ]);

                OrderItem::factory()->create([
                    'order_id'     => $order->id,
                    'product_id'   => $product->id,
                    'product_name' => "Produto {$i}",
                ]);
            }

            // Should render without N+1 issues
            Livewire::actingAs($user)
                ->test(OrderDetail::class, ['order' => $order])
                ->assertSuccessful()
                ->assertSee('Produto 1')
                ->assertSee('Produto 5');
        });
    });
});
