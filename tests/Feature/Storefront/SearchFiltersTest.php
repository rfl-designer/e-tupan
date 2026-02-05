<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Livewire\Storefront\ProductList;
use Livewire\Livewire;

describe('US-06: Filtrar e ordenar resultados da busca', function () {
    describe('Filtros disponíveis na busca', function () {
        it('shows filter sidebar when searching', function () {
            Product::factory()->create([
                'name'   => 'Produto Teste',
                'status' => ProductStatus::Active,
            ]);

            Livewire::withQueryParams(['q' => 'Teste'])
                ->test(ProductList::class)
                ->assertSee('Filtros')
                ->assertSee('Ordenar por');
        });

        it('shows category filter in search results', function () {
            $category = Category::factory()->create([
                'name'      => 'Roupas',
                'is_active' => true,
            ]);

            Livewire::withQueryParams(['q' => 'produto'])
                ->test(ProductList::class)
                ->assertSee('Roupas');
        });

        it('shows price filter in search results', function () {
            Livewire::withQueryParams(['q' => 'produto'])
                ->test(ProductList::class)
                ->assertSee('Preco');
        });
    });

    describe('Filtrar por categoria', function () {
        it('filters search results by category', function () {
            $category = Category::factory()->create([
                'name'      => 'Eletrônicos',
                'slug'      => 'eletronicos',
                'is_active' => true,
            ]);

            $productInCategory = Product::factory()->create([
                'name'   => 'Celular Samsung',
                'status' => ProductStatus::Active,
            ]);
            $productInCategory->categories()->attach($category);

            $productOutsideCategory = Product::factory()->create([
                'name'   => 'Celular Apple',
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'Celular', 'categoria' => 'eletronicos'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products)->toHaveCount(1);
            expect($products->first()->name)->toBe('Celular Samsung');
        });

        it('combines category filter with search term in URL', function () {
            $category = Category::factory()->create([
                'slug'      => 'eletronicos',
                'is_active' => true,
            ]);

            $component = Livewire::withQueryParams(['q' => 'celular', 'categoria' => 'eletronicos'])
                ->test(ProductList::class);

            expect($component->get('q'))->toBe('celular');
            expect($component->get('categoria'))->toBe('eletronicos');
        });
    });

    describe('Filtrar por faixa de preço', function () {
        it('filters search results by minimum price', function () {
            Product::factory()->create([
                'name'   => 'Produto Barato',
                'price'  => 5000, // R$ 50,00
                'status' => ProductStatus::Active,
            ]);

            Product::factory()->create([
                'name'   => 'Produto Caro',
                'price'  => 20000, // R$ 200,00
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'Produto', 'preco_min' => 100])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products)->toHaveCount(1);
            expect($products->first()->name)->toBe('Produto Caro');
        });

        it('filters search results by maximum price', function () {
            Product::factory()->create([
                'name'   => 'Produto Barato',
                'price'  => 5000, // R$ 50,00
                'status' => ProductStatus::Active,
            ]);

            Product::factory()->create([
                'name'   => 'Produto Caro',
                'price'  => 20000, // R$ 200,00
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'Produto', 'preco_max' => 100])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products)->toHaveCount(1);
            expect($products->first()->name)->toBe('Produto Barato');
        });

        it('filters search results by price range', function () {
            Product::factory()->create([
                'name'   => 'Produto Barato',
                'price'  => 3000, // R$ 30,00
                'status' => ProductStatus::Active,
            ]);

            Product::factory()->create([
                'name'   => 'Produto Médio',
                'price'  => 10000, // R$ 100,00
                'status' => ProductStatus::Active,
            ]);

            Product::factory()->create([
                'name'   => 'Produto Caro',
                'price'  => 50000, // R$ 500,00
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'Produto', 'preco_min' => 50, 'preco_max' => 200])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products)->toHaveCount(1);
            expect($products->first()->name)->toBe('Produto Médio');
        });
    });

    describe('Ordenar resultados', function () {
        beforeEach(function () {
            Product::factory()->create([
                'name'       => 'Produto A',
                'price'      => 10000,
                'status'     => ProductStatus::Active,
                'created_at' => now()->subDays(2),
            ]);

            Product::factory()->create([
                'name'       => 'Produto B',
                'price'      => 5000,
                'status'     => ProductStatus::Active,
                'created_at' => now()->subDay(),
            ]);

            Product::factory()->create([
                'name'       => 'Produto C',
                'price'      => 15000,
                'status'     => ProductStatus::Active,
                'created_at' => now(),
            ]);
        });

        it('orders search results by lowest price', function () {
            $component = Livewire::withQueryParams(['q' => 'Produto', 'ordenar' => 'preco-asc'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products->first()->name)->toBe('Produto B');
            expect($products->last()->name)->toBe('Produto C');
        });

        it('orders search results by highest price', function () {
            $component = Livewire::withQueryParams(['q' => 'Produto', 'ordenar' => 'preco-desc'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products->first()->name)->toBe('Produto C');
            expect($products->last()->name)->toBe('Produto B');
        });

        it('orders search results by name A-Z', function () {
            $component = Livewire::withQueryParams(['q' => 'Produto', 'ordenar' => 'nome-asc'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products->first()->name)->toBe('Produto A');
            expect($products->last()->name)->toBe('Produto C');
        });

        it('orders search results by most recent by default', function () {
            $component = Livewire::withQueryParams(['q' => 'Produto', 'ordenar' => 'recentes'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products->first()->name)->toBe('Produto C');
            expect($products->last()->name)->toBe('Produto A');
        });
    });

    describe('Filtros combinados na URL', function () {
        it('maintains all filters in URL', function () {
            $category = Category::factory()->create([
                'slug'      => 'roupas',
                'is_active' => true,
            ]);

            $component = Livewire::withQueryParams([
                'q'         => 'camiseta',
                'categoria' => 'roupas',
                'preco_min' => 50,
                'preco_max' => 200,
                'ordenar'   => 'preco-asc',
            ])->test(ProductList::class);

            expect($component->get('q'))->toBe('camiseta');
            expect($component->get('categoria'))->toBe('roupas');
            expect($component->get('precoMin'))->toBe(50);
            expect($component->get('precoMax'))->toBe(200);
            expect($component->get('ordenar'))->toBe('preco-asc');
        });
    });

    describe('Limpar filtros mantém o termo de busca', function () {
        it('clears category filter but keeps search term', function () {
            $component = Livewire::withQueryParams(['q' => 'camiseta', 'categoria' => 'roupas'])
                ->test(ProductList::class)
                ->call('clearCategory');

            expect($component->get('q'))->toBe('camiseta');
            expect($component->get('categoria'))->toBe('');
        });

        it('clears price filter but keeps search term', function () {
            $component = Livewire::withQueryParams(['q' => 'camiseta', 'preco_min' => 50, 'preco_max' => 200])
                ->test(ProductList::class)
                ->call('clearPriceFilter');

            expect($component->get('q'))->toBe('camiseta');
            expect($component->get('precoMin'))->toBeNull();
            expect($component->get('precoMax'))->toBeNull();
        });

        it('clears all filters but keeps search term', function () {
            $component = Livewire::withQueryParams([
                'q'         => 'camiseta',
                'categoria' => 'roupas',
                'preco_min' => 50,
                'preco_max' => 200,
                'promocao'  => true,
            ])->test(ProductList::class)
                ->call('clearAllFilters');

            expect($component->get('q'))->toBe('camiseta');
            expect($component->get('categoria'))->toBe('');
            expect($component->get('precoMin'))->toBeNull();
            expect($component->get('precoMax'))->toBeNull();
            expect($component->get('promocao'))->toBe(false);
        });

        it('shows clear all filters button when filters are active', function () {
            $category = Category::factory()->create([
                'slug'      => 'roupas',
                'is_active' => true,
            ]);

            Livewire::withQueryParams(['q' => 'camiseta', 'categoria' => 'roupas'])
                ->test(ProductList::class)
                ->assertSee('Limpar todos');
        });
    });
});
