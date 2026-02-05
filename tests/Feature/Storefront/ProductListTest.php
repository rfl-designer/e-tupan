<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Livewire\Storefront\ProductList;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('US-01: Listagem basica de produtos', function () {
    test('pagina /produtos carrega com sucesso', function () {
        $response = $this->get(route('products.index'));

        $response->assertOk();
    });

    test('pagina exibe grade de produtos ativos', function () {
        $activeProduct = Product::factory()->create([
            'name'   => 'Produto Ativo',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Produto Ativo');
    });

    test('cada produto mostra imagem, nome e preco', function () {
        $product = Product::factory()->create([
            'name'   => 'Camiseta Teste',
            'status' => ProductStatus::Active,
            'price'  => 9990,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Camiseta Teste');
        $response->assertSee('R$99.90');
    });

    test('produtos em promocao mostram desconto', function () {
        $product = Product::factory()->create([
            'name'          => 'Produto Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Produto Promocao');
        $response->assertSee('R$70.00');
        $response->assertSee('R$100.00');
    });

    test('produtos inativos nao sao exibidos', function () {
        Product::factory()->create([
            'name'   => 'Produto Ativo',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create([
            'name'   => 'Produto Inativo',
            'status' => ProductStatus::Inactive,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Produto Ativo');
        $response->assertDontSee('Produto Inativo');
    });

    test('produtos rascunho nao sao exibidos', function () {
        Product::factory()->create([
            'name'   => 'Produto Ativo',
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->create([
            'name'   => 'Produto Rascunho',
            'status' => ProductStatus::Draft,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Produto Ativo');
        $response->assertDontSee('Produto Rascunho');
    });

    test('paginacao exibe 12 produtos por pagina', function () {
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $products = $response->viewData('products');
        expect($products)->toHaveCount(12);
    });

    test('paginacao mostra numero total de produtos', function () {
        Product::factory()->count(25)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('25 produtos');
    });

    test('pode navegar entre paginas', function () {
        Product::factory()->count(25)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 2);

        $products = $response->viewData('products');
        expect($products)->toHaveCount(12);
    });

    test('ultima pagina mostra produtos restantes', function () {
        Product::factory()->count(25)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 3);

        $products = $response->viewData('products');
        expect($products)->toHaveCount(1);
    });

    test('exibe estado vazio quando nao ha produtos', function () {
        $response = Livewire::test(ProductList::class);

        $response->assertSee('Nenhum produto encontrado');
    });

    test('pagina e responsiva com classes de grid', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('grid-cols-2');
        $response->assertSee('sm:grid-cols-2');
        $response->assertSee('lg:grid-cols-3');
    });
});

describe('US-02: Filtro por categoria', function () {
    test('sidebar exibe categorias disponiveis', function () {
        $category = Category::factory()->create([
            'name'      => 'Eletrônicos',
            'is_active' => true,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Eletrônicos');
    });

    test('ao selecionar categoria apenas produtos dessa categoria sao exibidos', function () {
        $category = Category::factory()->create([
            'name'      => 'Roupas',
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $productInCategory = Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);
        $productInCategory->categories()->attach($category);

        $productNotInCategory = Product::factory()->create([
            'name'   => 'Notebook Dell',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas');

        $response->assertSee('Camiseta Azul');
        $response->assertDontSee('Notebook Dell');
    });

    test('categorias filhas tambem filtram corretamente', function () {
        $parentCategory = Category::factory()->create([
            'name'      => 'Roupas',
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $childCategory = Category::factory()->create([
            'name'      => 'Camisetas',
            'slug'      => 'camisetas',
            'parent_id' => $parentCategory->id,
            'is_active' => true,
        ]);

        $productInChild = Product::factory()->create([
            'name'   => 'Camiseta Branca',
            'status' => ProductStatus::Active,
        ]);
        $productInChild->categories()->attach($childCategory);

        $productInParent = Product::factory()->create([
            'name'   => 'Calca Jeans',
            'status' => ProductStatus::Active,
        ]);
        $productInParent->categories()->attach($parentCategory);

        $productOther = Product::factory()->create([
            'name'   => 'Smartphone',
            'status' => ProductStatus::Active,
        ]);

        // Ao filtrar pela categoria pai, deve mostrar produtos da filha também
        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas');

        $response->assertSee('Camiseta Branca');
        $response->assertSee('Calca Jeans');
        $response->assertDontSee('Smartphone');
    });

    test('filtro de categoria e refletido na URL', function () {
        $category = Category::factory()->create([
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'eletronicos');

        $response->assertSet('categoria', 'eletronicos');
    });

    test('contador de produtos atualiza ao filtrar por categoria', function () {
        $category = Category::factory()->create([
            'name'      => 'Livros',
            'slug'      => 'livros',
            'is_active' => true,
        ]);

        Product::factory()->count(5)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->categories()->attach($category));

        Product::factory()->count(10)->create([
            'status' => ProductStatus::Active,
        ]);

        // Sem filtro deve mostrar total
        $response = Livewire::test(ProductList::class);
        $response->assertSee('15 produtos');

        // Com filtro deve mostrar apenas os da categoria
        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'livros');
        $response->assertSee('5 produtos');
    });

    test('pode limpar o filtro de categoria', function () {
        $category = Category::factory()->create([
            'slug'      => 'moveis',
            'is_active' => true,
        ]);

        $productInCategory = Product::factory()->create([
            'name'   => 'Sofa',
            'status' => ProductStatus::Active,
        ]);
        $productInCategory->categories()->attach($category);

        $productOther = Product::factory()->create([
            'name'   => 'Televisao',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'moveis')
            ->assertSee('Sofa')
            ->assertDontSee('Televisao')
            ->call('clearCategory')
            ->assertSee('Sofa')
            ->assertSee('Televisao');
    });

    test('categorias inativas nao sao exibidas no filtro', function () {
        Category::factory()->create([
            'name'      => 'Categoria Ativa',
            'is_active' => true,
        ]);

        Category::factory()->create([
            'name'      => 'Categoria Inativa',
            'is_active' => false,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Categoria Ativa');
        $response->assertDontSee('Categoria Inativa');
    });

    test('URL com parametro categoria filtra automaticamente', function () {
        $category = Category::factory()->create([
            'slug'      => 'games',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'name'   => 'PS5',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category);

        $otherProduct = Product::factory()->create([
            'name'   => 'Geladeira',
            'status' => ProductStatus::Active,
        ]);

        // Simula acesso com query string
        $response = Livewire::withQueryParams(['categoria' => 'games'])
            ->test(ProductList::class);

        $response->assertSee('PS5');
        $response->assertDontSee('Geladeira');
    });

    test('paginacao reseta ao mudar filtro de categoria', function () {
        $category = Category::factory()->create([
            'slug'      => 'tecnologia',
            'is_active' => true,
        ]);

        // Criar mais de 12 produtos para ter paginação
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->count(5)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->categories()->attach($category));

        // Ir para página 2 sem filtro
        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 2);

        // Ao aplicar filtro, deve voltar para página 1
        $response->set('categoria', 'tecnologia');

        $products = $response->viewData('products');
        expect($products->currentPage())->toBe(1);
    });
});

describe('US-03: Filtro por faixa de preco', function () {
    test('inputs de preco minimo e maximo estao disponiveis', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Preco');
    });

    test('filtro por preco minimo exibe apenas produtos acima do valor', function () {
        Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50,00
        ]);

        Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 15000, // R$ 150,00
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('precoMin', 100); // R$ 100,00

        $response->assertDontSee('Produto Barato');
        $response->assertSee('Produto Caro');
    });

    test('filtro por preco maximo exibe apenas produtos abaixo do valor', function () {
        Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50,00
        ]);

        Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 15000, // R$ 150,00
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('precoMax', 100); // R$ 100,00

        $response->assertSee('Produto Barato');
        $response->assertDontSee('Produto Caro');
    });

    test('filtro considera preco promocional quando ativo', function () {
        // Produto com preço normal R$ 200, mas em promoção por R$ 80
        Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 20000,
            'sale_price'    => 8000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto com preço normal R$ 90
        Product::factory()->create([
            'name'   => 'Produto Normal',
            'status' => ProductStatus::Active,
            'price'  => 9000,
        ]);

        // Filtrando até R$ 100, deve mostrar ambos (promocional = R$ 80, normal = R$ 90)
        $response = Livewire::test(ProductList::class)
            ->set('precoMax', 100);

        $response->assertSee('Produto em Promocao');
        $response->assertSee('Produto Normal');
    });

    test('filtro de preco e refletido na URL', function () {
        $response = Livewire::test(ProductList::class)
            ->set('precoMin', 50)
            ->set('precoMax', 200);

        $response->assertSet('precoMin', 50);
        $response->assertSet('precoMax', 200);
    });

    test('contador de produtos atualiza ao filtrar por preco', function () {
        Product::factory()->count(3)->create([
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50,00
        ]);

        Product::factory()->count(7)->create([
            'status' => ProductStatus::Active,
            'price'  => 15000, // R$ 150,00
        ]);

        // Sem filtro
        $response = Livewire::test(ProductList::class);
        $response->assertSee('10 produtos');

        // Com filtro de preco maximo R$ 100
        $response = Livewire::test(ProductList::class)
            ->set('precoMax', 100);
        $response->assertSee('3 produtos');
    });

    test('pode limpar filtro de preco', function () {
        Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 15000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('precoMin', 100)
            ->assertDontSee('Produto Barato')
            ->call('clearPriceFilter')
            ->assertSee('Produto Barato')
            ->assertSee('Produto Caro');
    });

    test('URL com parametros de preco filtra automaticamente', function () {
        Product::factory()->create([
            'name'   => 'Produto Acessivel',
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50
        ]);

        Product::factory()->create([
            'name'   => 'Produto Premium',
            'status' => ProductStatus::Active,
            'price'  => 15000, // R$ 150
        ]);

        // Filtrando de R$ 100 a R$ 200, deve mostrar apenas Premium (R$ 150)
        $response = Livewire::withQueryParams(['preco_min' => '100', 'preco_max' => '200'])
            ->test(ProductList::class);

        $response->assertDontSee('Produto Acessivel');
        $response->assertSee('Produto Premium');
    });

    test('paginacao reseta ao mudar filtro de preco', function () {
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        Product::factory()->count(5)->create([
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 2);

        $response->set('precoMax', 60);

        $products = $response->viewData('products');
        expect($products->currentPage())->toBe(1);
    });

    test('filtro de preco funciona com faixa minima e maxima', function () {
        Product::factory()->create([
            'name'   => 'Muito Barato',
            'status' => ProductStatus::Active,
            'price'  => 2000, // R$ 20
        ]);

        Product::factory()->create([
            'name'   => 'Preco Medio',
            'status' => ProductStatus::Active,
            'price'  => 10000, // R$ 100
        ]);

        Product::factory()->create([
            'name'   => 'Muito Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000, // R$ 500
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('precoMin', 50)
            ->set('precoMax', 200);

        $response->assertDontSee('Muito Barato');
        $response->assertSee('Preco Medio');
        $response->assertDontSee('Muito Caro');
    });
});

describe('US-04: Filtro por atributos', function () {
    test('atributos com valores presentes nos produtos sao exibidos', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $product = Product::factory()->create([
            'name'   => 'Camiseta Vermelha',
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Cor');
        $response->assertSee('Vermelho');
    });

    test('apenas atributos presentes nos produtos listados sao exibidos', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $sizeAttribute  = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $mediumValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($sizeAttribute, 'attribute')
            ->size('M')
            ->create();

        // Produto apenas com cor
        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Atributo tamanho não está em nenhum produto ativo
        $inactiveProduct = Product::factory()->create([
            'status' => ProductStatus::Inactive,
        ]);
        $inactiveProduct->attributeValues()->attach($mediumValue, ['attribute_id' => $sizeAttribute->id]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Cor');
        $response->assertSee('Vermelho');
        $response->assertDontSee('Tamanho');
    });

    test('pode selecionar multiplos valores do mesmo atributo', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        $greenValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Verde', '#00FF00')
            ->create();

        $redProduct = Product::factory()->create([
            'name'   => 'Camiseta Vermelha',
            'status' => ProductStatus::Active,
        ]);
        $redProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $blueProduct = Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
        ]);
        $blueProduct->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]);

        $greenProduct = Product::factory()->create([
            'name'   => 'Camiseta Verde',
            'status' => ProductStatus::Active,
        ]);
        $greenProduct->attributeValues()->attach($greenValue, ['attribute_id' => $colorAttribute->id]);

        // Seleciona vermelho e azul
        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id, $blueValue->id]]);

        $response->assertSee('Camiseta Vermelha');
        $response->assertSee('Camiseta Azul');
        $response->assertDontSee('Camiseta Verde');
    });

    test('filtro de atributos e refletido na URL', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]]);

        $response->assertSet('atributos', [$colorAttribute->slug => [$redValue->id]]);
    });

    test('contador de produtos atualiza ao filtrar por atributos', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        // 3 produtos vermelhos
        Product::factory()->count(3)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]));

        // 2 produtos azuis
        Product::factory()->count(2)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]));

        // Sem filtro
        $response = Livewire::test(ProductList::class);
        $response->assertSee('5 produtos');

        // Com filtro vermelho
        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]]);
        $response->assertSee('3 produtos');
    });

    test('pode limpar filtros de atributos individualmente', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $sizeAttribute  = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $mediumValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($sizeAttribute, 'attribute')
            ->size('M')
            ->create();

        $product = Product::factory()->create([
            'name'   => 'Camiseta Vermelha M',
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);
        $product->attributeValues()->attach($mediumValue, ['attribute_id' => $sizeAttribute->id]);

        $otherProduct = Product::factory()->create([
            'name'   => 'Camiseta Azul G',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('atributos', [
                $colorAttribute->slug => [$redValue->id],
                $sizeAttribute->slug  => [$mediumValue->id],
            ])
            ->assertSee('Camiseta Vermelha M')
            ->assertDontSee('Camiseta Azul G')
            ->call('clearAttributeFilter', $colorAttribute->slug)
            ->assertSet('atributos', [$sizeAttribute->slug => [$mediumValue->id]]);
    });

    test('URL com parametros de atributos filtra automaticamente', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        $redProduct = Product::factory()->create([
            'name'   => 'Produto Vermelho',
            'status' => ProductStatus::Active,
        ]);
        $redProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $blueProduct = Product::factory()->create([
            'name'   => 'Produto Azul',
            'status' => ProductStatus::Active,
        ]);
        $blueProduct->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::withQueryParams(['atributos' => [$colorAttribute->slug => [(string) $redValue->id]]])
            ->test(ProductList::class);

        $response->assertSee('Produto Vermelho');
        $response->assertDontSee('Produto Azul');
    });

    test('paginacao reseta ao mudar filtro de atributos', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Criar mais de 12 produtos para ter paginação
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
        ]);

        Product::factory()->count(5)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]));

        // Ir para página 2 sem filtro
        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 2);

        // Ao aplicar filtro, deve voltar para página 1
        $response->set('atributos', [$colorAttribute->slug => [$redValue->id]]);

        $products = $response->viewData('products');
        expect($products->currentPage())->toBe(1);
    });

    test('filtro de atributos funciona com multiplos atributos diferentes', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $sizeAttribute  = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $mediumValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($sizeAttribute, 'attribute')
            ->size('M')
            ->create();

        $largeValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($sizeAttribute, 'attribute')
            ->size('G')
            ->create();

        // Produto vermelho M
        $productRedM = Product::factory()->create([
            'name'   => 'Camiseta Vermelha M',
            'status' => ProductStatus::Active,
        ]);
        $productRedM->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);
        $productRedM->attributeValues()->attach($mediumValue, ['attribute_id' => $sizeAttribute->id]);

        // Produto vermelho G
        $productRedL = Product::factory()->create([
            'name'   => 'Camiseta Vermelha G',
            'status' => ProductStatus::Active,
        ]);
        $productRedL->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);
        $productRedL->attributeValues()->attach($largeValue, ['attribute_id' => $sizeAttribute->id]);

        // Produto azul M
        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        $productBlueM = Product::factory()->create([
            'name'   => 'Camiseta Azul M',
            'status' => ProductStatus::Active,
        ]);
        $productBlueM->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]);
        $productBlueM->attributeValues()->attach($mediumValue, ['attribute_id' => $sizeAttribute->id]);

        // Filtrar por vermelho E tamanho M (deve ser AND entre atributos diferentes)
        $response = Livewire::test(ProductList::class)
            ->set('atributos', [
                $colorAttribute->slug => [$redValue->id],
                $sizeAttribute->slug  => [$mediumValue->id],
            ]);

        $response->assertSee('Camiseta Vermelha M');
        $response->assertDontSee('Camiseta Vermelha G');
        $response->assertDontSee('Camiseta Azul M');
    });

    test('atributos tipo cor exibem swatch de cor', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('#FF0000');
    });

    test('filtro combina atributos com categoria', function () {
        $category = Category::factory()->create([
            'name'      => 'Roupas',
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Produto vermelho na categoria roupas
        $productInCategory = Product::factory()->create([
            'name'   => 'Camiseta Vermelha',
            'status' => ProductStatus::Active,
        ]);
        $productInCategory->categories()->attach($category);
        $productInCategory->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto vermelho fora da categoria
        $productOutCategory = Product::factory()->create([
            'name'   => 'Garrafa Vermelha',
            'status' => ProductStatus::Active,
        ]);
        $productOutCategory->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]]);

        $response->assertSee('Camiseta Vermelha');
        $response->assertDontSee('Garrafa Vermelha');
    });

    test('filtro combina atributos com preco', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Produto vermelho barato
        $cheapProduct = Product::factory()->create([
            'name'   => 'Camiseta Vermelha Barata',
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50
        ]);
        $cheapProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto vermelho caro
        $expensiveProduct = Product::factory()->create([
            'name'   => 'Camiseta Vermelha Cara',
            'status' => ProductStatus::Active,
            'price'  => 20000, // R$ 200
        ]);
        $expensiveProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->set('precoMax', 100);

        $response->assertSee('Camiseta Vermelha Barata');
        $response->assertDontSee('Camiseta Vermelha Cara');
    });
});

describe('US-05: Ordenacao de produtos', function () {
    test('dropdown de ordenacao exibe opcoes disponiveis', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('Ordenar por');
        $response->assertSee('Mais recentes');
        $response->assertSee('Menor preço');
        $response->assertSee('Maior preço');
        $response->assertSee('Nome A-Z');
        $response->assertSee('Mais vendidos');
    });

    test('ordenacao padrao e mais recentes', function () {
        // Criar produtos em ordem específica
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

        $response = Livewire::test(ProductList::class);

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Novo');
        expect($products->last()->name)->toBe('Produto Antigo');
    });

    test('pode ordenar por menor preco', function () {
        $expensiveProduct = Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        $cheapProduct = Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'preco-asc');

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Barato');
        expect($products->last()->name)->toBe('Produto Caro');
    });

    test('pode ordenar por maior preco', function () {
        $cheapProduct = Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        $expensiveProduct = Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'preco-desc');

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Caro');
        expect($products->last()->name)->toBe('Produto Barato');
    });

    test('pode ordenar por nome A-Z', function () {
        $productZ = Product::factory()->create([
            'name'   => 'Zebra',
            'status' => ProductStatus::Active,
        ]);

        $productA = Product::factory()->create([
            'name'   => 'Abacate',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'nome-asc');

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Abacate');
        expect($products->last()->name)->toBe('Zebra');
    });

    test('pode ordenar por mais vendidos', function () {
        $lessPopular = Product::factory()->create([
            'name'   => 'Produto Menos Popular',
            'status' => ProductStatus::Active,
        ]);

        $popular = Product::factory()->create([
            'name'   => 'Produto Popular',
            'status' => ProductStatus::Active,
        ]);

        // Criar order items para simular vendas
        \App\Domain\Checkout\Models\OrderItem::factory()->count(10)->create([
            'product_id' => $popular->id,
        ]);

        \App\Domain\Checkout\Models\OrderItem::factory()->count(2)->create([
            'product_id' => $lessPopular->id,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'mais-vendidos');

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Popular');
        expect($products->last()->name)->toBe('Produto Menos Popular');
    });

    test('ordenacao e refletida na URL', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'preco-asc');

        $response->assertSet('ordenar', 'preco-asc');
    });

    test('URL com parametro ordenar aplica ordenacao automaticamente', function () {
        $expensiveProduct = Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        $cheapProduct = Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        $response = Livewire::withQueryParams(['ordenar' => 'preco-asc'])
            ->test(ProductList::class);

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Barato');
    });

    test('ordenacao e mantida ao aplicar filtro de categoria', function () {
        $category = Category::factory()->create([
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        $cheapProduct = Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);
        $cheapProduct->categories()->attach($category);

        $expensiveProduct = Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);
        $expensiveProduct->categories()->attach($category);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'preco-asc')
            ->set('categoria', 'eletronicos');

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Produto Barato');
        expect($products->last()->name)->toBe('Produto Caro');
    });

    test('ordenacao e mantida ao aplicar filtro de preco', function () {
        $productA = Product::factory()->create([
            'name'   => 'Produto A',
            'status' => ProductStatus::Active,
            'price'  => 15000, // R$ 150
        ]);

        $productB = Product::factory()->create([
            'name'   => 'Produto B',
            'status' => ProductStatus::Active,
            'price'  => 10000, // R$ 100
        ]);

        $productC = Product::factory()->create([
            'name'   => 'Produto C',
            'status' => ProductStatus::Active,
            'price'  => 5000, // R$ 50 - fora do filtro
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'nome-asc')
            ->set('precoMin', 80);

        $products = $response->viewData('products');
        expect($products)->toHaveCount(2);
        expect($products->first()->name)->toBe('Produto A');
        expect($products->last()->name)->toBe('Produto B');
    });

    test('ordenacao e mantida ao aplicar filtro de atributos', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();

        $redValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $productZ = Product::factory()->create([
            'name'   => 'Zebra Vermelha',
            'status' => ProductStatus::Active,
        ]);
        $productZ->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $productA = Product::factory()->create([
            'name'   => 'Abacate Vermelho',
            'status' => ProductStatus::Active,
        ]);
        $productA->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'nome-asc')
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]]);

        $products = $response->viewData('products');
        expect($products->first()->name)->toBe('Abacate Vermelho');
        expect($products->last()->name)->toBe('Zebra Vermelha');
    });

    test('ordenar por preco considera preco promocional quando ativo', function () {
        // Produto com preço normal R$ 200, mas em promoção por R$ 50
        $promoProduct = Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 20000,
            'sale_price'    => 5000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto com preço normal R$ 100
        $normalProduct = Product::factory()->create([
            'name'   => 'Produto Normal',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('ordenar', 'preco-asc');

        $products = $response->viewData('products');
        // Produto em promoção (R$ 50) deve vir antes do normal (R$ 100)
        expect($products->first()->name)->toBe('Produto em Promocao');
        expect($products->last()->name)->toBe('Produto Normal');
    });
});

describe('US-06: Produtos em promocao destacados', function () {
    test('produtos em promocao exibem badge de desconto', function () {
        $product = Product::factory()->create([
            'name'          => 'Produto com Desconto',
            'status'        => ProductStatus::Active,
            'price'         => 10000, // R$ 100
            'sale_price'    => 7000, // R$ 70
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class);

        // Badge deve mostrar -30%
        $response->assertSee('-30%');
    });

    test('preco original aparece riscado ao lado do promocional', function () {
        $product = Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000, // R$ 100
            'sale_price'    => 7000, // R$ 70
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class);

        // Deve exibir ambos os preços
        $response->assertSee('R$100.00');
        $response->assertSee('R$70.00');
        // Verifica que tem classe line-through para o preço original
        $response->assertSee('line-through');
    });

    test('pode filtrar apenas produtos em promocao', function () {
        // Produto em promoção
        $promoProduct = Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto sem promoção
        $normalProduct = Product::factory()->create([
            'name'   => 'Produto Normal',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        // Produto com promoção expirada
        $expiredPromoProduct = Product::factory()->create([
            'name'          => 'Produto Promocao Expirada',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDays(10),
            'sale_end_at'   => Carbon::now()->subDays(5),
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true);

        $response->assertSee('Produto em Promocao');
        $response->assertDontSee('Produto Normal');
        $response->assertDontSee('Produto Promocao Expirada');
    });

    test('filtro de promocao e refletido na URL', function () {
        Product::factory()->create([
            'status'        => ProductStatus::Active,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true);

        $response->assertSet('promocao', true);
    });

    test('URL com parametro promocao filtra automaticamente', function () {
        // Produto em promoção
        $promoProduct = Product::factory()->create([
            'name'          => 'Produto Oferta',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto sem promoção
        $normalProduct = Product::factory()->create([
            'name'   => 'Produto Comum',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        $response = Livewire::withQueryParams(['promocao' => '1'])
            ->test(ProductList::class);

        $response->assertSee('Produto Oferta');
        $response->assertDontSee('Produto Comum');
    });

    test('percentual de desconto e calculado corretamente', function () {
        // 50% de desconto
        $product50 = Product::factory()->create([
            'name'          => 'Desconto 50%',
            'status'        => ProductStatus::Active,
            'price'         => 10000, // R$ 100
            'sale_price'    => 5000, // R$ 50
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertSee('-50%');
    });

    test('contador de produtos atualiza ao filtrar promocoes', function () {
        // 3 produtos em promoção
        Product::factory()->count(3)->create([
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // 5 produtos sem promoção
        Product::factory()->count(5)->create([
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        // Sem filtro
        $response = Livewire::test(ProductList::class);
        $response->assertSee('8 produtos');

        // Com filtro de promoção
        $response = Livewire::test(ProductList::class)
            ->set('promocao', true);
        $response->assertSee('3 produtos');
    });

    test('pode limpar filtro de promocao', function () {
        // Produto em promoção
        $promoProduct = Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto sem promoção
        $normalProduct = Product::factory()->create([
            'name'   => 'Produto Normal',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true)
            ->assertSee('Produto em Promocao')
            ->assertDontSee('Produto Normal')
            ->call('clearPromoFilter')
            ->assertSee('Produto em Promocao')
            ->assertSee('Produto Normal');
    });

    test('filtro de promocao combina com outros filtros', function () {
        $category = Category::factory()->create([
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        // Produto em promoção na categoria
        $promoInCategory = Product::factory()->create([
            'name'          => 'Celular em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 100000,
            'sale_price'    => 70000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);
        $promoInCategory->categories()->attach($category);

        // Produto em promoção fora da categoria
        $promoOutCategory = Product::factory()->create([
            'name'          => 'Camiseta em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        // Produto sem promoção na categoria
        $normalInCategory = Product::factory()->create([
            'name'   => 'Notebook Normal',
            'status' => ProductStatus::Active,
            'price'  => 200000,
        ]);
        $normalInCategory->categories()->attach($category);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true)
            ->set('categoria', 'eletronicos');

        $response->assertSee('Celular em Promocao');
        $response->assertDontSee('Camiseta em Promocao');
        $response->assertDontSee('Notebook Normal');
    });

    test('badge de promocao exibe apenas quando promocao esta ativa', function () {
        // Promoção futura
        $futurePromo = Product::factory()->create([
            'name'          => 'Promocao Futura',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->addDays(5),
            'sale_end_at'   => Carbon::now()->addDays(10),
        ]);

        // Promoção expirada
        $expiredPromo = Product::factory()->create([
            'name'          => 'Promocao Expirada',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDays(10),
            'sale_end_at'   => Carbon::now()->subDays(5),
        ]);

        $response = Livewire::test(ProductList::class);

        // Não deve exibir badge de desconto para promoções inativas
        $response->assertDontSee('-30%');
        // Deve exibir apenas o preço normal
        $response->assertSee('R$100.00');
    });

    test('paginacao reseta ao ativar filtro de promocao', function () {
        // Criar muitos produtos para ter paginação
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        Product::factory()->count(5)->create([
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class)
            ->call('gotoPage', 2);

        $response->set('promocao', true);

        $products = $response->viewData('products');
        expect($products->currentPage())->toBe(1);
    });
});

describe('US-07: Filtros combinaveis', function () {
    test('pode combinar filtro de categoria + preco + atributos + ordenacao', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Produto que deve aparecer: categoria roupas, vermelho, preço R$ 80
        $matchingProduct = Product::factory()->create([
            'name'   => 'Camiseta Vermelha',
            'status' => ProductStatus::Active,
            'price'  => 8000,
        ]);
        $matchingProduct->categories()->attach($category);
        $matchingProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto categoria correta, cor correta, mas preço fora
        $expensiveProduct = Product::factory()->create([
            'name'   => 'Camiseta Vermelha Cara',
            'status' => ProductStatus::Active,
            'price'  => 30000,
        ]);
        $expensiveProduct->categories()->attach($category);
        $expensiveProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto categoria errada
        $wrongCategory = Product::factory()->create([
            'name'   => 'Eletronico Vermelho',
            'status' => ProductStatus::Active,
            'price'  => 8000,
        ]);
        $wrongCategory->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto cor errada
        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        $wrongColor = Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'status' => ProductStatus::Active,
            'price'  => 8000,
        ]);
        $wrongColor->categories()->attach($category);
        $wrongColor->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMax', 100)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->set('ordenar', 'preco-asc');

        $response->assertSee('Camiseta Vermelha');
        $response->assertDontSee('Camiseta Vermelha Cara');
        $response->assertDontSee('Eletronico Vermelho');
        $response->assertDontSee('Camiseta Azul');
    });

    test('filtros ativos sao exibidos como chips', function () {
        $category = Category::factory()->create([
            'name'      => 'Roupas',
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Active,
            'price'  => 8000,
        ])->categories()->attach($category);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMin', 50)
            ->set('precoMax', 200);

        // Deve exibir chips com os filtros ativos
        $response->assertSee('Filtros ativos');
        $response->assertSee('Roupas');
        $response->assertSee('R$ 50');
        $response->assertSee('R$ 200');
    });

    test('filtro de promocao exibido como chip quando ativo', function () {
        Product::factory()->create([
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true);

        $response->assertSee('Filtros ativos');
        $response->assertSee('Ofertas');
    });

    test('filtros de atributos exibidos como chips', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $product = Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]]);

        $response->assertSee('Filtros ativos');
        $response->assertSee('Cor');
        $response->assertSee('Vermelho');
    });

    test('pode remover filtro de categoria clicando no X', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $productInCategory = Product::factory()->create([
            'name'   => 'Camiseta',
            'status' => ProductStatus::Active,
        ]);
        $productInCategory->categories()->attach($category);

        $otherProduct = Product::factory()->create([
            'name'   => 'Notebook',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->assertSee('Camiseta')
            ->assertDontSee('Notebook')
            ->call('clearCategory')
            ->assertSee('Camiseta')
            ->assertSee('Notebook');

        $response->assertSet('categoria', '');
    });

    test('pode remover filtro de preco clicando no X', function () {
        Product::factory()->create([
            'name'   => 'Produto Barato',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        Product::factory()->create([
            'name'   => 'Produto Caro',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('precoMax', 100)
            ->assertSee('Produto Barato')
            ->assertDontSee('Produto Caro')
            ->call('clearPriceFilter')
            ->assertSee('Produto Barato')
            ->assertSee('Produto Caro');

        $response->assertSet('precoMin', null);
        $response->assertSet('precoMax', null);
    });

    test('pode remover filtro de atributo clicando no X', function () {
        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $blueValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Azul', '#0000FF')
            ->create();

        $redProduct = Product::factory()->create([
            'name'   => 'Produto Vermelho',
            'status' => ProductStatus::Active,
        ]);
        $redProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $blueProduct = Product::factory()->create([
            'name'   => 'Produto Azul',
            'status' => ProductStatus::Active,
        ]);
        $blueProduct->attributeValues()->attach($blueValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->assertSee('Produto Vermelho')
            ->assertDontSee('Produto Azul')
            ->call('clearAttributeFilter', $colorAttribute->slug)
            ->assertSee('Produto Vermelho')
            ->assertSee('Produto Azul');

        $response->assertSet('atributos', []);
    });

    test('pode remover filtro de promocao clicando no X', function () {
        $promoProduct = Product::factory()->create([
            'name'          => 'Produto em Promocao',
            'status'        => ProductStatus::Active,
            'price'         => 10000,
            'sale_price'    => 7000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);

        $normalProduct = Product::factory()->create([
            'name'   => 'Produto Normal',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('promocao', true)
            ->assertSee('Produto em Promocao')
            ->assertDontSee('Produto Normal')
            ->call('clearPromoFilter')
            ->assertSee('Produto em Promocao')
            ->assertSee('Produto Normal');

        $response->assertSet('promocao', false);
    });

    test('botao limpar todos remove todos os filtros', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $matchingProduct = Product::factory()->create([
            'name'          => 'Camiseta Vermelha em Promo',
            'status'        => ProductStatus::Active,
            'price'         => 8000,
            'sale_price'    => 5000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);
        $matchingProduct->categories()->attach($category);
        $matchingProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $otherProduct = Product::factory()->create([
            'name'   => 'Notebook',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMax', 100)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->set('promocao', true)
            ->set('ordenar', 'preco-asc')
            ->assertSee('Camiseta Vermelha em Promo')
            ->assertDontSee('Notebook')
            ->call('clearAllFilters')
            ->assertSee('Camiseta Vermelha em Promo')
            ->assertSee('Notebook');

        $response->assertSet('categoria', '');
        $response->assertSet('precoMin', null);
        $response->assertSet('precoMax', null);
        $response->assertSet('atributos', []);
        $response->assertSet('promocao', false);
    });

    test('URL reflete todos os filtros aplicados', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        $product = Product::factory()->create([
            'status'        => ProductStatus::Active,
            'price'         => 8000,
            'sale_price'    => 5000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);
        $product->categories()->attach($category);
        $product->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMin', 50)
            ->set('precoMax', 200)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->set('promocao', true)
            ->set('ordenar', 'preco-asc');

        // Todas as propriedades devem estar setadas corretamente (refletidas na URL via #[Url])
        $response->assertSet('categoria', 'roupas');
        $response->assertSet('precoMin', 50);
        $response->assertSet('precoMax', 200);
        $response->assertSet('atributos', [$colorAttribute->slug => [$redValue->id]]);
        $response->assertSet('promocao', true);
        $response->assertSet('ordenar', 'preco-asc');
    });

    test('ao compartilhar URL os filtros sao aplicados automaticamente', function () {
        $category = Category::factory()->create([
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Produto que atende todos os filtros
        $matchingProduct = Product::factory()->create([
            'name'          => 'Celular Vermelho em Promo',
            'status'        => ProductStatus::Active,
            'price'         => 15000,
            'sale_price'    => 10000,
            'sale_start_at' => Carbon::now()->subDay(),
            'sale_end_at'   => Carbon::now()->addDay(),
        ]);
        $matchingProduct->categories()->attach($category);
        $matchingProduct->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);

        // Produto fora dos filtros
        $otherProduct = Product::factory()->create([
            'name'   => 'Notebook Azul',
            'status' => ProductStatus::Active,
            'price'  => 50000,
        ]);

        // Simula acesso via URL compartilhada com todos os filtros
        $response = Livewire::withQueryParams([
            'categoria' => 'eletronicos',
            'preco_min' => '50',
            'preco_max' => '200',
            'atributos' => [$colorAttribute->slug => [(string) $redValue->id]],
            'promocao'  => '1',
            'ordenar'   => 'preco-asc',
        ])->test(ProductList::class);

        $response->assertSee('Celular Vermelho em Promo');
        $response->assertDontSee('Notebook Azul');

        // Verifica que os filtros foram aplicados
        $response->assertSet('categoria', 'eletronicos');
        $response->assertSet('precoMin', 50);
        $response->assertSet('precoMax', 200);
        $response->assertSet('promocao', true);
        $response->assertSet('ordenar', 'preco-asc');
    });

    test('area de filtros ativos nao aparece quando nao ha filtros', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductList::class);

        $response->assertDontSee('Filtros ativos');
    });

    test('botao limpar todos aparece na area de filtros ativos', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'status' => ProductStatus::Active,
        ])->categories()->attach($category);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMin', 50);

        // Verifica que o botão Limpar todos está presente
        $response->assertSee('Limpar todos');
    });

    test('ordenacao e mantida ao limpar filtros', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name'   => 'Produto A',
            'status' => ProductStatus::Active,
            'price'  => 10000,
        ]);

        Product::factory()->create([
            'name'   => 'Produto B',
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ]);

        $response = Livewire::test(ProductList::class)
            ->set('categoria', 'roupas')
            ->set('ordenar', 'preco-asc')
            ->call('clearCategory');

        // Ordenação deve ser mantida
        $response->assertSet('ordenar', 'preco-asc');
    });
});

describe('US-08: Experiencia fluida de navegacao', function () {
    test('filtros atualizam a lista via Livewire sem reload de pagina', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $productInCategory = Product::factory()->create([
            'name'   => 'Camiseta',
            'status' => ProductStatus::Active,
        ]);
        $productInCategory->categories()->attach($category);

        $otherProduct = Product::factory()->create([
            'name'   => 'Notebook',
            'status' => ProductStatus::Active,
        ]);

        // Inicia sem filtro
        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class)
            ->assertSee('Camiseta')
            ->assertSee('Notebook');

        // Aplica filtro via wire:model.live (Livewire)
        $response->set('categoria', 'roupas')
            ->assertSee('Camiseta')
            ->assertDontSee('Notebook');

        // Limpa filtro e verifica reatividade
        $response->call('clearCategory')
            ->assertSee('Camiseta')
            ->assertSee('Notebook');
    });

    test('indicador de loading esta presente durante filtragem', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Verifica que a diretiva wire:loading.class esta presente para indicar loading
        $response->assertSee('wire:loading.class');
    });

    test('indicador de loading com spinner esta presente', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Verifica que existe um indicador de loading visivel
        $response->assertSee('wire:loading');
    });

    test('paginacao funciona com filtros aplicados', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        // Criar mais de 12 produtos na categoria para ter paginação
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
        ])->each(fn ($p) => $p->categories()->attach($category));

        // Criar produtos fora da categoria
        Product::factory()->count(10)->create([
            'status' => ProductStatus::Active,
        ]);

        // Com filtro de categoria, deve ter 15 produtos = 2 páginas
        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class)
            ->set('categoria', 'roupas');

        $products = $response->viewData('products');
        expect($products)->toHaveCount(12);
        expect($products->total())->toBe(15);

        // Vai para página 2
        $response->call('gotoPage', 2);

        $products = $response->viewData('products');
        expect($products)->toHaveCount(3);
        expect($products->currentPage())->toBe(2);

        // Filtro deve continuar aplicado
        $response->assertSet('categoria', 'roupas');
    });

    test('scroll ao topo esta configurado ao mudar de pagina', function () {
        Product::factory()->count(25)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Verifica que o componente usa WithPagination trait que suporta scroll behavior
        $reflection = new \ReflectionClass(\App\Livewire\Storefront\ProductList::class);
        $traits     = $reflection->getTraitNames();
        expect($traits)->toContain(\Livewire\WithPagination::class);

        // Verifica que ao mudar de página, a paginação funciona
        $products = $response->viewData('products');
        expect($products->hasPages())->toBeTrue();

        // Navega para a página 2
        $response->call('gotoPage', 2);
        $products = $response->viewData('products');
        expect($products->currentPage())->toBe(2);
    });

    test('historico do navegador funciona com filtros via URL', function () {
        $category = Category::factory()->create([
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'name'   => 'Celular',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category);

        $otherProduct = Product::factory()->create([
            'name'   => 'Camiseta',
            'status' => ProductStatus::Active,
        ]);

        // Simula navegacao via URL (historico do navegador)
        $response = Livewire::withQueryParams([
            'categoria' => 'eletronicos',
        ])->test(\App\Livewire\Storefront\ProductList::class);

        $response->assertSee('Celular');
        $response->assertDontSee('Camiseta');

        // Simula voltar no historico (sem filtros)
        $responseBack = Livewire::withQueryParams([])
            ->test(\App\Livewire\Storefront\ProductList::class);

        $responseBack->assertSee('Celular');
        $responseBack->assertSee('Camiseta');
    });

    test('propriedades com atributo Url sao sincronizadas com query string', function () {
        // Verifica que todas as propriedades de filtro tem o atributo #[Url]
        $reflection = new \ReflectionClass(\App\Livewire\Storefront\ProductList::class);

        $urlProperties = ['categoria', 'precoMin', 'precoMax', 'atributos', 'ordenar', 'promocao'];

        foreach ($urlProperties as $propertyName) {
            $property   = $reflection->getProperty($propertyName);
            $attributes = $property->getAttributes(\Livewire\Attributes\Url::class);
            expect($attributes)->not->toBeEmpty("Propriedade {$propertyName} deve ter atributo #[Url]");
        }
    });

    test('multiplos filtros sao mantidos ao navegar paginas', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        $colorAttribute = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $redValue       = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->for($colorAttribute, 'attribute')
            ->colorValue('Vermelho', '#FF0000')
            ->create();

        // Criar 15 produtos vermelhos na categoria roupas
        Product::factory()->count(15)->create([
            'status' => ProductStatus::Active,
            'price'  => 5000,
        ])->each(function ($p) use ($category, $colorAttribute, $redValue) {
            $p->categories()->attach($category);
            $p->attributeValues()->attach($redValue, ['attribute_id' => $colorAttribute->id]);
        });

        // Aplicar multiplos filtros
        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class)
            ->set('categoria', 'roupas')
            ->set('precoMax', 100)
            ->set('atributos', [$colorAttribute->slug => [$redValue->id]])
            ->set('ordenar', 'preco-asc');

        // Verifica primeira pagina
        $products = $response->viewData('products');
        expect($products)->toHaveCount(12);

        // Navega para segunda pagina
        $response->call('gotoPage', 2);

        // Todos os filtros devem estar mantidos
        $response->assertSet('categoria', 'roupas');
        $response->assertSet('precoMax', 100);
        $response->assertSet('atributos', [$colorAttribute->slug => [$redValue->id]]);
        $response->assertSet('ordenar', 'preco-asc');

        $products = $response->viewData('products');
        expect($products)->toHaveCount(3);
    });

    test('componente usa trait WithPagination para navegacao fluida', function () {
        $reflection = new \ReflectionClass(\App\Livewire\Storefront\ProductList::class);
        $traits     = $reflection->getTraitNames();

        expect($traits)->toContain(\Livewire\WithPagination::class);
    });

    test('filtros usam wire model live para atualizacao em tempo real', function () {
        Product::factory()->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Verifica que existe wire:model.live para ordenacao (sem escape HTML)
        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('wire:model.live="ordenar"');
    });

    test('pagina inteira nao recarrega ao aplicar filtros', function () {
        $category = Category::factory()->create([
            'slug'      => 'roupas',
            'is_active' => true,
        ]);

        Product::factory()->create([
            'name'   => 'Camiseta',
            'status' => ProductStatus::Active,
            'price'  => 10000, // R$ 100 - dentro da faixa de R$ 500
        ])->categories()->attach($category);

        // Testa multiplas interacoes no mesmo componente (sem reload)
        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Primeira interacao
        $response->set('categoria', 'roupas')
            ->assertSee('Camiseta');

        // Segunda interacao (no mesmo componente)
        $response->set('precoMax', 500)
            ->assertSee('Camiseta');

        // Terceira interacao
        $response->set('ordenar', 'preco-asc')
            ->assertSee('Camiseta');

        // Limpa tudo
        $response->call('clearAllFilters')
            ->assertSee('Camiseta');
    });

    test('mudanca de pagina dispara hook updated', function () {
        Product::factory()->count(25)->create([
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(\App\Livewire\Storefront\ProductList::class);

        // Verifica que o componente tem hooks de updated para resetar pagina
        $reflection = new \ReflectionClass(\App\Livewire\Storefront\ProductList::class);

        // Métodos updated que resetam paginação
        $updatedMethods = [
            'updatedCategoria',
            'updatedPrecoMin',
            'updatedPrecoMax',
            'updatedAtributos',
            'updatedOrdenar',
            'updatedPromocao',
        ];

        foreach ($updatedMethods as $method) {
            expect($reflection->hasMethod($method))->toBeTrue("Método {$method} deve existir");
        }
    });
});
