<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Livewire\Storefront\SearchBox;
use Livewire\Livewire;

describe('US-01: Campo de busca no header', function () {
    it('renders the search box component', function () {
        Livewire::test(SearchBox::class)
            ->assertStatus(200);
    });

    it('displays search input with correct placeholder', function () {
        Livewire::test(SearchBox::class)
            ->assertSeeHtml('placeholder="Buscar produtos..."');
    });

    it('has a search icon visible', function () {
        Livewire::test(SearchBox::class)
            ->assertSeeHtml('data-flux-icon');
    });

    it('allows typing in search query', function () {
        Livewire::test(SearchBox::class)
            ->set('query', 'camiseta')
            ->assertSet('query', 'camiseta');
    });

    it('redirects to search results page when form is submitted', function () {
        Livewire::test(SearchBox::class)
            ->set('query', 'camiseta')
            ->call('search')
            ->assertRedirect(route('search', ['q' => 'camiseta']));
    });

    it('does not redirect when query is empty', function () {
        Livewire::test(SearchBox::class)
            ->set('query', '')
            ->call('search')
            ->assertNoRedirect();
    });

    it('trims whitespace from query before searching', function () {
        Livewire::test(SearchBox::class)
            ->set('query', '  camiseta  ')
            ->call('search')
            ->assertRedirect(route('search', ['q' => 'camiseta']));
    });

    it('does not redirect when query is only whitespace', function () {
        Livewire::test(SearchBox::class)
            ->set('query', '   ')
            ->call('search')
            ->assertNoRedirect();
    });

    it('search box is visible on header in storefront pages', function () {
        $this->get(route('home'))
            ->assertOk()
            ->assertSeeLivewire(SearchBox::class);
    });
});

describe('US-01: Responsividade do campo de busca', function () {
    it('has mobile toggle button for expanding search', function () {
        Livewire::test(SearchBox::class)
            ->assertSeeHtml('lg:hidden');
    });

    it('has desktop search input always visible', function () {
        Livewire::test(SearchBox::class)
            ->assertSeeHtml('hidden lg:block');
    });

    it('can toggle mobile search visibility', function () {
        Livewire::test(SearchBox::class)
            ->assertSet('showMobileSearch', false)
            ->call('toggleMobileSearch')
            ->assertSet('showMobileSearch', true)
            ->call('toggleMobileSearch')
            ->assertSet('showMobileSearch', false);
    });
});

describe('US-02: Autocomplete de busca', function () {
    it('does not show suggestions when query has less than 2 characters', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'C')
            ->assertSet('suggestions', []);
    });

    it('shows suggestions after typing 2+ characters', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'Cam')
            ->assertCount('suggestions', 1);
    });

    it('limits suggestions to 5 products', function () {
        Product::factory()->count(10)->sequence(
            ['name' => 'Camiseta 01'],
            ['name' => 'Camiseta 02'],
            ['name' => 'Camiseta 03'],
            ['name' => 'Camiseta 04'],
            ['name' => 'Camiseta 05'],
            ['name' => 'Camiseta 06'],
            ['name' => 'Camiseta 07'],
            ['name' => 'Camiseta 08'],
            ['name' => 'Camiseta 09'],
            ['name' => 'Camiseta 10'],
        )->create([
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'Camiseta')
            ->assertCount('suggestions', 5);
    });

    it('only shows active products in suggestions', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Ativa',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create([
            'name'   => 'Camiseta Rascunho',
            'status' => ProductStatus::Draft,
        ]);

        $component = Livewire::test(SearchBox::class)
            ->set('query', 'Camiseta');

        $suggestions = $component->get('suggestions');
        expect($suggestions)->toHaveCount(1);
        expect($suggestions[0]['name'])->toBe('Camiseta Ativa');
    });

    it('includes product image, name and price in suggestions', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Premium',
            'price'  => 9990,
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::test(SearchBox::class)
            ->set('query', 'Premium');

        $suggestions = $component->get('suggestions');
        expect($suggestions[0])->toHaveKeys(['id', 'name', 'slug', 'price', 'formatted_price', 'image']);
    });

    it('clears suggestions when query is cleared', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'Camiseta')
            ->assertCount('suggestions', 1)
            ->set('query', '')
            ->assertSet('suggestions', []);
    });

    it('navigates to product page when selecting a suggestion', function () {
        $product = Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'slug'   => 'camiseta-azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->call('selectSuggestion', $product->slug)
            ->assertRedirect(route('products.show', $product->slug));
    });

    it('shows "ver todos os resultados" link when there are suggestions', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);

        Livewire::test(SearchBox::class)
            ->set('query', 'Camiseta')
            ->assertSee('Ver todos os resultados');
    });

    it('searches in product name', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Azul Marinho',
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::test(SearchBox::class)
            ->set('query', 'Azul');

        expect($component->get('suggestions'))->toHaveCount(1);
    });

    it('searches case-insensitive', function () {
        Product::factory()->create([
            'name'   => 'Camiseta Premium',
            'status' => ProductStatus::Active,
        ]);

        $component = Livewire::test(SearchBox::class)
            ->set('query', 'PREMIUM');

        expect($component->get('suggestions'))->toHaveCount(1);
    });
});
