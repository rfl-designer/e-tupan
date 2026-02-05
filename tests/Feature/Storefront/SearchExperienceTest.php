<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\Product;
use App\Livewire\Storefront\{ProductList, SearchBox};
use Livewire\Livewire;

use function Pest\Laravel\get;

describe('US-07: Experiencia de busca rapida e fluida', function () {
    describe('Autocomplete usa Livewire sem reload', function () {
        it('uses wire:model.live for real-time updates', function () {
            Livewire::test(SearchBox::class)
                ->assertSeeHtml('wire:model.live.debounce.300ms="query"');
        });

        it('updates suggestions without page reload via Livewire', function () {
            Product::factory()->create([
                'name'   => 'Camiseta Fluida',
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::test(SearchBox::class)
                ->assertSet('suggestions', [])
                ->set('query', 'Fluida')
                ->assertCount('suggestions', 1);

            // Verificar que ainda estamos no mesmo componente (sem reload)
            expect($component->instance())->toBeInstanceOf(SearchBox::class);
        });

        it('uses wire:navigate for SPA-like navigation to product', function () {
            Product::factory()->create([
                'name'   => 'Produto Navigate Test',
                'status' => ProductStatus::Active,
            ]);

            // wire:navigate só aparece quando há sugestões
            Livewire::test(SearchBox::class)
                ->set('query', 'Navigate')
                ->assertSeeHtml('wire:navigate');
        });
    });

    describe('Indicador de loading durante busca', function () {
        it('has loading indicator in search box view', function () {
            Livewire::test(SearchBox::class)
                ->assertSeeHtml('wire:loading');
        });

        it('has loading target for query updates', function () {
            Livewire::test(SearchBox::class)
                ->assertSeeHtml('wire:target="query"');
        });
    });

    describe('Pagina de resultados carrega rapidamente', function () {
        it('uses pagination to limit results per page', function () {
            Product::factory()->count(15)->sequence(
                fn ($sequence) => ['name' => 'Produto Rapido ' . ($sequence->index + 1)],
            )->create([
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::withQueryParams(['q' => 'Rapido'])
                ->test(ProductList::class);

            $products = $component->viewData('products');
            expect($products)->toHaveCount(12);
            expect($products->total())->toBe(15);
        });

        it('has loading overlay on product list', function () {
            Livewire::test(ProductList::class)
                ->assertSeeHtml('wire:loading');
        });

        it('applies loading opacity to grid during updates', function () {
            // A grid só é renderizada quando há produtos
            Product::factory()->create([
                'name'   => 'Produto Loading',
                'status' => ProductStatus::Active,
            ]);

            $component = Livewire::test(ProductList::class);

            // Verifica que existe wire:loading.class com opacity-50 na grid
            expect($component->html())->toContain('wire:loading.class')
                ->and($component->html())->toContain('opacity-50');
        });
    });

    describe('Historico do navegador permite voltar a buscas anteriores', function () {
        it('uses URL attribute for search term synchronization', function () {
            $component = Livewire::withQueryParams(['q' => 'historico-teste'])
                ->test(ProductList::class);

            expect($component->get('q'))->toBe('historico-teste');
        });

        it('maintains search query in URL for browser history', function () {
            Product::factory()->create([
                'name'   => 'Produto Historico',
                'status' => ProductStatus::Active,
            ]);

            $response = get(route('search', ['q' => 'Historico']));

            $response->assertOk();
            /** @phpstan-ignore method.notFound */
            $response->assertSeeLivewire(ProductList::class);
        });

        it('maintains filters in URL for browser history', function () {
            $component = Livewire::withQueryParams([
                'q'       => 'teste',
                'ordenar' => 'preco-asc',
            ])->test(ProductList::class);

            expect($component->get('q'))->toBe('teste');
            expect($component->get('ordenar'))->toBe('preco-asc');
        });

        it('uses wire:navigate for search results link', function () {
            Product::factory()->create([
                'name'   => 'Produto Navigate',
                'status' => ProductStatus::Active,
            ]);

            Livewire::test(SearchBox::class)
                ->set('query', 'Navigate')
                ->assertSeeHtml('wire:navigate');
        });
    });

    describe('Busca funciona bem em conexoes lentas (loading states)', function () {
        it('shows loading spinner during search operations', function () {
            Livewire::test(ProductList::class)
                ->assertSeeHtml('animate-spin');
        });

        it('shows loading text indicator', function () {
            Livewire::test(ProductList::class)
                ->assertSee('Carregando...');
        });

        it('search box has debounce to prevent excessive requests', function () {
            Livewire::test(SearchBox::class)
                ->assertSeeHtml('debounce.300ms');
        });

        it('autocomplete dropdown shows loading state while fetching', function () {
            Livewire::test(SearchBox::class)
                ->assertSeeHtml('wire:loading')
                ->assertSeeHtml('wire:target="query"');
        });
    });
});
