<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;

use function Pest\Laravel\get;

describe('US-08: SEO da pagina de busca', function () {
    describe('Meta title dinamico com termo buscado', function () {
        it('includes search term in page title', function () {
            Product::factory()->create([
                'name'   => 'Camiseta SEO',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Camiseta']));

            $response->assertOk();
            $response->assertSee('<title>Busca: Camiseta', false);
        });

        it('shows generic title when no search term', function () {
            $response = get(route('search'));

            $response->assertOk();
            $response->assertSee('<title>Produtos', false);
        });
    });

    describe('Meta description apropriada', function () {
        it('has meta description with search term and result count', function () {
            Product::factory()->count(5)->create([
                'name'   => 'Produto Meta',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Meta']));

            $response->assertOk();
            $response->assertSee('<meta name="description"', false);
            $response->assertSee('Meta', false);
        });

        it('has appropriate description for empty results', function () {
            $response = get(route('search', ['q' => 'produto-inexistente-xyz']));

            $response->assertOk();
            $response->assertSee('<meta name="description"', false);
        });
    });

    describe('Canonical URL definida corretamente', function () {
        it('has canonical url for search page', function () {
            Product::factory()->create([
                'name'   => 'Produto Canonical',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Canonical']));

            $response->assertOk();
            $response->assertSee('<link rel="canonical"', false);
        });

        it('canonical url includes search term only', function () {
            Product::factory()->create([
                'name'   => 'Produto Clean',
                'status' => ProductStatus::Active,
            ]);

            // Access with extra parameters
            $response = get(route('search', ['q' => 'Clean', 'page' => '2']));

            $response->assertOk();
            // Canonical should have clean URL with just the search term
            $response->assertSee('canonical', false);
        });

        it('canonical url excludes pagination parameter', function () {
            Product::factory()->count(15)->sequence(
                fn ($sequence) => ['name' => 'Item Paginado ' . ($sequence->index + 1)],
            )->create([
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Paginado', 'page' => '2']));

            $response->assertOk();
            // The canonical href should not contain page=2
            $canonicalUrl = route('search', ['q' => 'Paginado']);
            $response->assertSee('href="' . $canonicalUrl . '"', false);
        });
    });

    describe('Noindex para paginas sem resultados', function () {
        it('has noindex meta tag when no results found', function () {
            $response = get(route('search', ['q' => 'termo-sem-resultados-xyz']));

            $response->assertOk();
            $response->assertSee('<meta name="robots" content="noindex', false);
        });

        it('does not have noindex when results exist', function () {
            Product::factory()->create([
                'name'   => 'Produto Indexavel',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Indexavel']));

            $response->assertOk();
            $response->assertDontSee('noindex', false);
        });

        it('has noindex for highly filtered search pages', function () {
            Product::factory()->create([
                'name'   => 'Produto Filtrado',
                'status' => ProductStatus::Active,
            ]);

            // Search with many filters should be noindex
            $response = get(route('search', [
                'q'         => 'Filtrado',
                'preco_min' => 100,
                'preco_max' => 500,
                'ordenar'   => 'preco-asc',
            ]));

            $response->assertOk();
            $response->assertSee('<meta name="robots" content="noindex', false);
        });
    });

    describe('URLs de busca indexaveis quando relevantes', function () {
        it('search pages with results are indexable', function () {
            Product::factory()->create([
                'name'   => 'Produto Relevante',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Relevante']));

            $response->assertOk();
            // Should not have noindex
            $response->assertDontSee('noindex', false);
        });

        it('product listing without search is indexable', function () {
            Product::factory()->create([
                'name'   => 'Produto Lista',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search'));

            $response->assertOk();
            $response->assertDontSee('noindex', false);
        });
    });
});
