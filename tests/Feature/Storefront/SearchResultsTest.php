<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Livewire\Storefront\ProductList;
use Livewire\Livewire;

describe('US-03: Página de resultados de busca', function () {
    it('displays search results page at /busca route', function () {
        $this->get(route('search', ['q' => 'teste']))
            ->assertOk()
            ->assertSeeLivewire(ProductList::class);
    });

    it('displays the search term in the page title', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::withQueryParams(['q' => 'Camiseta'])
            ->test(ProductList::class)
            ->assertSee('Resultados para')
            ->assertSee('Camiseta');
    });

    it('displays the total number of results', function () {
        Product::factory()->count(5)->create([
            'name'   => 'Produto Teste',
            'status' => ProductStatus::Active,
        ]);

        Livewire::withQueryParams(['q' => 'Teste'])
            ->test(ProductList::class)
            ->assertSee('5 produtos');
    });

    it('displays products matching the search term', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Premium',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create([
            'name'   => 'Calça Jeans',
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Camiseta'])
            ->test(ProductList::class);

        $products = $component->viewData('products');
        expect($products)->toHaveCount(1);
        expect($products->first()->name)->toBe('Camiseta Premium');
    });

    it('shows products in grid using product card', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Teste',
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Teste'])
            ->test(ProductList::class);

        $products = $component->viewData('products');
        expect($products)->toHaveCount(1);
        // The product card is rendered using <x-storefront.product-card>
        $component->assertSee('Camiseta Teste');
    });

    it('paginates results with 12 products per page', function () {
        Product::factory()->count(15)->sequence(
            fn ($sequence) => ['name' => 'Produto Teste ' . ($sequence->index + 1)],
        )->create([
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Produto'])
            ->test(ProductList::class);

        $products = $component->viewData('products');
        expect($products)->toHaveCount(12);
        expect($products->total())->toBe(15);
    });

    it('maintains search term during pagination', function () {
        Product::factory()->count(15)->sequence(
            fn ($sequence) => ['name' => 'Produto Paginado ' . ($sequence->index + 1)],
        )->create([
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Paginado'])
            ->test(ProductList::class)
            ->call('nextPage');

        expect($component->get('q'))->toBe('Paginado');

        $products = $component->viewData('products');
        expect($products)->toHaveCount(3);
    });

    it('searches only in active products', function () {
        Product::factory()->create([
            'name'   => 'Produto Ativo',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create([
            'name'   => 'Produto Rascunho',
            'status' => ProductStatus::Draft,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Produto'])
            ->test(ProductList::class);

        $products = $component->viewData('products');
        expect($products)->toHaveCount(1);
        expect($products->first()->name)->toBe('Produto Ativo');
    });

    it('displays message when no products are found', function () {
        Livewire::withQueryParams(['q' => 'produto-inexistente'])
            ->test(ProductList::class)
            ->assertSee('Nenhum resultado encontrado');
    });

    it('shows search term in URL query parameter', function () {
        Livewire::withQueryParams(['q' => 'busca-teste'])
            ->test(ProductList::class)
            ->assertSet('q', 'busca-teste');
    });

    it('resets pagination when search term changes', function () {
        Product::factory()->count(15)->sequence(
            fn ($sequence) => ['name' => 'Item Reset ' . ($sequence->index + 1)],
        )->create([
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Item'])
            ->test(ProductList::class)
            ->call('nextPage')
            ->set('q', 'Reset');

        $products = $component->viewData('products');
        expect($products->currentPage())->toBe(1);
    });

    it('displays singular product count when only one result', function () {
        Product::factory()->create([
            'name'   => 'Produto Único',
            'status' => ProductStatus::Active,
        ]);

        Livewire::withQueryParams(['q' => 'Único'])
            ->test(ProductList::class)
            ->assertSee('1 produto');
    });

    it('clears search term correctly', function () {
        Product::factory()->create([
            'name'   => 'Produto Limpar',
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::withQueryParams(['q' => 'Limpar'])
            ->test(ProductList::class)
            ->call('clearSearch');

        expect($component->get('q'))->toBe('');
    });
});
