<?php

declare(strict_types = 1);

use App\Domain\Admin\Services\SettingsService;
use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Domain\Marketing\Models\Banner;
use App\Livewire\Storefront\Homepage;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('homepage loads successfully', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
});

test('homepage displays store name from settings', function () {
    $settings = app(SettingsService::class);
    $settings->set('general.store_name', 'Loja Teste');

    $component = Livewire::test(Homepage::class);

    expect($component->viewData('storeName'))->toBe('Loja Teste');
});

test('homepage displays default app name when store name not set', function () {
    $component = Livewire::test(Homepage::class);

    expect($component->viewData('storeName'))->toBe(config('app.name'));
});

test('homepage displays categories section when categories exist', function () {
    Category::factory()->count(3)->create([
        'is_active' => true,
        'parent_id' => null,
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Categorias')
        ->assertSee('Navegue por nossas categorias');
});

test('homepage hides categories section when no categories', function () {
    Livewire::test(Homepage::class)
        ->assertDontSee('Navegue por nossas categorias');
});

test('homepage displays only active root categories', function () {
    $activeRoot = Category::factory()->create([
        'name'      => 'Categoria Ativa',
        'is_active' => true,
        'parent_id' => null,
    ]);

    $inactiveRoot = Category::factory()->create([
        'name'      => 'Categoria Inativa',
        'is_active' => false,
        'parent_id' => null,
    ]);

    $childCategory = Category::factory()->create([
        'name'      => 'Subcategoria',
        'is_active' => true,
        'parent_id' => $activeRoot->id,
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Categoria Ativa')
        ->assertDontSee('Categoria Inativa')
        ->assertDontSee('Subcategoria');
});

test('homepage displays featured products section', function () {
    Product::factory()->count(3)->create([
        'status' => ProductStatus::Active,
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Produtos em Destaque')
        ->assertSee('Selecao especial para voce');
});

test('homepage displays empty state when no products', function () {
    Livewire::test(Homepage::class)
        ->assertSee('Nenhum produto disponivel')
        ->assertSee('Em breve teremos novidades para voce');
});

test('homepage displays only active products', function () {
    Product::factory()->create([
        'name'   => 'Produto Ativo',
        'status' => ProductStatus::Active,
    ]);

    Product::factory()->create([
        'name'   => 'Produto Rascunho',
        'status' => ProductStatus::Draft,
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Produto Ativo')
        ->assertDontSee('Produto Rascunho');
});

test('homepage displays sale products section when products on sale exist', function () {
    Product::factory()->create([
        'status'        => ProductStatus::Active,
        'price'         => 10000,
        'sale_price'    => 8000,
        'sale_start_at' => Carbon::now()->subDay(),
        'sale_end_at'   => Carbon::now()->addDay(),
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Ofertas Especiais')
        ->assertSee('Aproveite os melhores descontos');
});

test('homepage hides sale section when no products on sale', function () {
    Product::factory()->create([
        'status'     => ProductStatus::Active,
        'price'      => 10000,
        'sale_price' => null,
    ]);

    Livewire::test(Homepage::class)
        ->assertDontSee('Ofertas Especiais');
});

test('homepage displays new products section', function () {
    Product::factory()->count(3)->create([
        'status' => ProductStatus::Active,
    ]);

    Livewire::test(Homepage::class)
        ->assertSee('Novidades')
        ->assertSee('Produtos recem adicionados');
});

test('homepage displays newsletter section', function () {
    Livewire::test(Homepage::class)
        ->assertSee('Fique por dentro das novidades')
        ->assertSee('Cadastre-se para receber ofertas exclusivas');
});

test('homepage displays features section', function () {
    Livewire::test(Homepage::class)
        ->assertSee('Frete Gratis')
        ->assertSee('Compra Segura')
        ->assertSee('Troca Facil')
        ->assertSee('Parcele em 12x');
});

test('homepage displays banner carousel when banners exist', function () {
    Banner::factory()->valid()->create(['alt_text' => 'Banner Home']);

    Livewire::test(Homepage::class)
        ->assertSee('Banner Home');
});

test('homepage limits featured products to 8', function () {
    Product::factory()->count(15)->create([
        'status' => ProductStatus::Active,
    ]);

    $component = Livewire::test(Homepage::class);

    $featuredProducts = $component->viewData('featuredProducts');
    expect($featuredProducts)->toHaveCount(8);
});

test('homepage limits categories to 6', function () {
    Category::factory()->count(10)->create([
        'is_active' => true,
        'parent_id' => null,
    ]);

    $component = Livewire::test(Homepage::class);

    $categories = $component->viewData('categories');
    expect($categories)->toHaveCount(6);
});

test('homepage limits sale products to 4', function () {
    Product::factory()->count(10)->create([
        'status'        => ProductStatus::Active,
        'price'         => 10000,
        'sale_price'    => 8000,
        'sale_start_at' => Carbon::now()->subDay(),
        'sale_end_at'   => Carbon::now()->addDay(),
    ]);

    $component = Livewire::test(Homepage::class);

    $saleProducts = $component->viewData('saleProducts');
    expect($saleProducts)->toHaveCount(4);
});

test('homepage orders categories by position', function () {
    $cat3 = Category::factory()->create([
        'name'      => 'Cat C',
        'position'  => 3,
        'is_active' => true,
        'parent_id' => null,
    ]);
    $cat1 = Category::factory()->create([
        'name'      => 'Cat A',
        'position'  => 1,
        'is_active' => true,
        'parent_id' => null,
    ]);
    $cat2 = Category::factory()->create([
        'name'      => 'Cat B',
        'position'  => 2,
        'is_active' => true,
        'parent_id' => null,
    ]);

    $component = Livewire::test(Homepage::class);

    $categories = $component->viewData('categories');
    expect($categories->pluck('name')->toArray())->toBe(['Cat A', 'Cat B', 'Cat C']);
});

test('homepage orders new products by latest', function () {
    $oldProduct = Product::factory()->create([
        'name'       => 'Produto Antigo',
        'status'     => ProductStatus::Active,
        'created_at' => Carbon::now()->subDays(5),
    ]);

    $newProduct = Product::factory()->create([
        'name'       => 'Produto Novo',
        'status'     => ProductStatus::Active,
        'created_at' => Carbon::now(),
    ]);

    $component = Livewire::test(Homepage::class);

    $newProducts = $component->viewData('newProducts');
    expect($newProducts->first()->name)->toBe('Produto Novo');
});
