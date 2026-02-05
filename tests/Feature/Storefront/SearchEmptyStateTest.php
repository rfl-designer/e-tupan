<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Livewire\Storefront\ProductList;
use Livewire\Livewire;

describe('US-05: Mensagem amigável quando não houver resultados', function () {
    describe('Mensagem amigável', function () {
        it('displays friendly message when no products are found for search', function () {
            Livewire::withQueryParams(['q' => 'produto-inexistente-xyz'])
                ->test(ProductList::class)
                ->assertSee('Nenhum resultado encontrado')
                ->assertSee('produto-inexistente-xyz');
        });

        it('displays message suggesting to check spelling or use different terms', function () {
            Livewire::withQueryParams(['q' => 'xyzabc123'])
                ->test(ProductList::class)
                ->assertSee('Verifique a ortografia')
                ->assertSee('termos diferentes');
        });
    });

    describe('Produtos sugeridos', function () {
        it('shows suggested products when search has no results', function () {
            Product::factory()->create([
                'name'   => 'Produto Sugerido',
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $component->assertSee('Talvez você goste')
                ->assertSee('Produto Sugerido');
        });

        it('shows up to 4 suggested products', function () {
            Product::factory()->count(6)->create([
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $suggestedProducts = $component->instance()->suggestedProducts;
            expect($suggestedProducts)->toHaveCount(4);
        });

        it('does not show suggested section when no products exist', function () {
            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $component->assertDontSee('Talvez você goste');
        });

        it('only shows active products in suggestions', function () {
            Product::factory()->create([
                'name'   => 'Produto Ativo',
                'status' => ProductStatus::Active,
            ]);
            Product::factory()->create([
                'name'   => 'Produto Inativo',
                'status' => ProductStatus::Inactive,
            ]);

            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $suggestedProducts = $component->instance()->suggestedProducts;
            expect($suggestedProducts)->toHaveCount(1);
            expect($suggestedProducts->first()->name)->toBe('Produto Ativo');
        });
    });

    describe('Categorias populares', function () {
        it('shows popular categories when search has no results', function () {
            Category::factory()->create([
                'name'      => 'Categoria Popular',
                'is_active' => true,
            ]);

            Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class)
                ->assertSee('Categorias populares')
                ->assertSee('Categoria Popular');
        });

        it('shows up to 6 categories', function () {
            Category::factory()->count(10)->create([
                'is_active' => true,
                'parent_id' => null,
            ]);

            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $popularCategories = $component->instance()->popularCategories;
            expect($popularCategories)->toHaveCount(6);
        });

        it('does not show category section when no categories exist', function () {
            $component = Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class);

            $component->assertDontSee('Categorias populares');
        });
    });

    describe('Campo de busca para nova pesquisa', function () {
        it('shows search input in empty state for easy new search', function () {
            Livewire::withQueryParams(['q' => 'busca-sem-resultado'])
                ->test(ProductList::class)
                ->assertSee('Tente uma nova busca');
        });
    });
});
