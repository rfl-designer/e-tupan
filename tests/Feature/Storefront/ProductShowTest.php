<?php

declare(strict_types = 1);

use App\Domain\Catalog\Enums\ProductStatus;
use App\Domain\Catalog\Models\{Category, Product};
use App\Livewire\Storefront\ProductShow;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('US-01: Detalhes basicos do produto', function () {
    test('pagina /produtos/{slug} exibe o produto correspondente', function () {
        $product = Product::factory()->create([
            'name'   => 'Camiseta Azul',
            'slug'   => 'camiseta-azul',
            'status' => ProductStatus::Active,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSeeLivewire(ProductShow::class);
        $response->assertSee('Camiseta Azul');
    });

    test('produtos inativos retornam 404', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Inativo',
            'slug'   => 'produto-inativo',
            'status' => ProductStatus::Inactive,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertNotFound();
    });

    test('produtos rascunho retornam 404', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Rascunho',
            'slug'   => 'produto-rascunho',
            'status' => ProductStatus::Draft,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertNotFound();
    });

    test('slug inexistente retorna 404', function () {
        $response = $this->get(route('products.show', 'produto-que-nao-existe'));

        $response->assertNotFound();
    });

    test('nome do produto e exibido como titulo principal', function () {
        $product = Product::factory()->create([
            'name'   => 'Notebook Dell Inspiron',
            'slug'   => 'notebook-dell-inspiron',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Notebook Dell Inspiron');
        // Verifica que está em um h1
        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('<h1');
        expect($html)->toContain('Notebook Dell Inspiron');
    });

    test('descricao curta e exibida abaixo do titulo', function () {
        $product = Product::factory()->create([
            'name'              => 'Smartphone Samsung',
            'slug'              => 'smartphone-samsung',
            'short_description' => 'O melhor smartphone do mercado com camera de 108MP.',
            'status'            => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('O melhor smartphone do mercado com camera de 108MP.');
    });

    test('descricao completa e exibida em uma secao separada', function () {
        $product = Product::factory()->create([
            'name'        => 'Cadeira Gamer',
            'slug'        => 'cadeira-gamer',
            'description' => 'Cadeira ergonomica com apoio lombar ajustavel, bracos 4D e encosto reclinavel ate 180 graus.',
            'status'      => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Cadeira ergonomica com apoio lombar ajustavel');
        $response->assertSee('Descricao');
    });

    test('SKU do produto e exibido', function () {
        $product = Product::factory()->create([
            'name'   => 'Monitor LG',
            'slug'   => 'monitor-lg',
            'sku'    => 'MON-LG-27-4K',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('MON-LG-27-4K');
    });

    test('categorias do produto sao exibidas como breadcrumbs', function () {
        $category = Category::factory()->create([
            'name'      => 'Eletronicos',
            'slug'      => 'eletronicos',
            'is_active' => true,
        ]);

        $subcategory = Category::factory()->create([
            'name'      => 'Smartphones',
            'slug'      => 'smartphones',
            'parent_id' => $category->id,
            'is_active' => true,
        ]);

        $product = Product::factory()->create([
            'name'   => 'iPhone 15',
            'slug'   => 'iphone-15',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($subcategory);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Eletronicos');
        $response->assertSee('Smartphones');
    });

    test('produto sem categoria nao exibe breadcrumbs de categoria', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Sem Categoria',
            'slug'   => 'produto-sem-categoria',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Produto Sem Categoria');
        // Deve ter pelo menos breadcrumb de Produtos
        $response->assertSee('Produtos');
    });

    test('pagina tem meta tags para SEO com meta_title personalizado', function () {
        $product = Product::factory()->create([
            'name'             => 'Teclado Mecanico',
            'slug'             => 'teclado-mecanico',
            'meta_title'       => 'Teclado Mecanico RGB - Compre Agora',
            'meta_description' => 'O melhor teclado mecanico com switches Cherry MX.',
            'status'           => ProductStatus::Active,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Teclado Mecanico RGB - Compre Agora', false);
        $response->assertSee('O melhor teclado mecanico com switches Cherry MX', false);
    });

    test('pagina usa nome do produto como title quando meta_title nao definido', function () {
        $product = Product::factory()->create([
            'name'       => 'Mouse Wireless',
            'slug'       => 'mouse-wireless',
            'meta_title' => null,
            'status'     => ProductStatus::Active,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Mouse Wireless', false);
    });

    test('pagina usa short_description como meta_description quando meta_description nao definido', function () {
        $product = Product::factory()->create([
            'name'              => 'Webcam HD',
            'slug'              => 'webcam-hd',
            'short_description' => 'Webcam Full HD 1080p com microfone integrado.',
            'meta_description'  => null,
            'status'            => ProductStatus::Active,
        ]);

        $response = $this->get(route('products.show', $product->slug));

        $response->assertOk();
        $response->assertSee('Webcam Full HD 1080p com microfone integrado', false);
    });

    test('produto com descricao vazia nao quebra a pagina', function () {
        $product = Product::factory()->create([
            'name'              => 'Produto Simples',
            'slug'              => 'produto-simples',
            'short_description' => null,
            'description'       => null,
            'status'            => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertOk();
        $response->assertSee('Produto Simples');
    });

    test('produto com SKU vazio nao exibe secao de SKU', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Sem SKU',
            'slug'   => 'produto-sem-sku',
            'sku'    => null,
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertOk();
        $response->assertDontSee('SKU:');
    });
});

describe('US-02: Galeria de imagens do produto', function () {
    test('imagem principal e exibida em destaque', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto com Imagem',
            'slug'   => 'produto-com-imagem',
            'status' => ProductStatus::Active,
        ]);

        $image = \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/test-image.jpg',
            'is_primary' => true,
            'position'   => 1,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // O caminho da imagem aparece no JSON (Js::from escapa as barras)
        expect($html)->toContain('test-image.jpg');
    });

    test('thumbnails das demais imagens sao exibidos', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto com Varias Imagens',
            'slug'   => 'produto-varias-imagens',
            'status' => ProductStatus::Active,
        ]);

        // Criar imagem principal
        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem-1.jpg',
            'is_primary' => true,
            'position'   => 1,
        ]);

        // Criar imagens secundarias
        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem-2.jpg',
            'is_primary' => false,
            'position'   => 2,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem-3.jpg',
            'is_primary' => false,
            'position'   => 3,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Os caminhos das imagens aparecem no JSON de dados do Alpine.js
        expect($html)->toContain('imagem-1.jpg');
        expect($html)->toContain('imagem-2.jpg');
        expect($html)->toContain('imagem-3.jpg');
    });

    test('galeria usa Alpine.js para troca de imagens', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Alpine',
            'slug'   => 'produto-alpine',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem.jpg',
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que usa Alpine.js x-data
        expect($html)->toContain('x-data');
    });

    test('produto sem imagens exibe placeholder', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Sem Imagem',
            'slug'   => 'produto-sem-imagem',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que o placeholder com SVG e exibido (Flux icon renderiza como SVG)
        expect($html)->toContain('data-flux-icon');
    });

    test('galeria tem botoes de navegacao por setas', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Navegacao',
            'slug'   => 'produto-navegacao',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica botoes de navegacao (prev() e next() no Alpine.js)
        expect($html)->toContain('prev()');
        expect($html)->toContain('next()');
    });

    test('galeria tem modal para ampliar imagem', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Lightbox',
            'slug'   => 'produto-lightbox',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem.jpg',
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que existe a variavel lightboxOpen para controlar o modal
        expect($html)->toContain('lightboxOpen');
    });

    test('galeria e responsiva com classes tailwind', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Responsivo',
            'slug'   => 'produto-responsivo',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica classes responsivas
        expect($html)->toMatch('/grid-cols|flex|aspect-/');
    });

    test('imagens tem atributo alt para acessibilidade', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Acessivel',
            'slug'   => 'produto-acessivel',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/imagem.jpg',
            'alt_text'   => 'Descricao da imagem',
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('alt=');
    });

    test('imagens tem lazy loading', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Lazy',
            'slug'   => 'produto-lazy',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('loading="lazy"');
    });

    test('thumbnails tem indicador visual do item ativo', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Indicador',
            'slug'   => 'produto-indicador',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que tem binding para indicar item ativo
        expect($html)->toContain('currentImage');
    });
});

describe('US-03: Preco e promocoes do produto', function () {
    test('preco atual e exibido em destaque', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto com Preco',
            'slug'   => 'produto-com-preco',
            'price'  => 19999, // R$ 199.99
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('R$');
        $response->assertSee('199.99');
    });

    test('preco em promocao exibe preco original riscado', function () {
        $product = Product::factory()->create([
            'name'       => 'Produto em Promocao',
            'slug'       => 'produto-promocao',
            'price'      => 29999, // R$ 299.99
            'sale_price' => 19999, // R$ 199.99
            'status'     => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica preco original
        expect($html)->toContain('299.99');
        // Verifica preco promocional
        expect($html)->toContain('199.99');
        // Verifica que tem classe line-through para preco riscado
        expect($html)->toContain('line-through');
    });

    test('badge de desconto percentual e exibido', function () {
        $product = Product::factory()->create([
            'name'       => 'Produto Desconto',
            'slug'       => 'produto-desconto',
            'price'      => 10000, // R$ 100,00
            'sale_price' => 7000, // R$ 70,00 = 30% desconto
            'status'     => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('-30%');
    });

    test('preco sem promocao nao exibe badge de desconto', function () {
        $product = Product::factory()->create([
            'name'       => 'Produto Normal',
            'slug'       => 'produto-normal',
            'price'      => 15000, // R$ 150,00
            'sale_price' => null,
            'status'     => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->not->toContain('-%');
    });

    test('parcelamento e exibido para produtos acima do minimo', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Parcelamento',
            'slug'   => 'produto-parcelamento',
            'price'  => 60000, // R$ 600,00
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('12x');
    });

    test('parcelamento nao e exibido para produtos abaixo do minimo', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto Barato',
            'slug'   => 'produto-barato',
            'price'  => 5000, // R$ 50,00
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->not->toContain('12x');
    });

    test('preco e formatado em Real brasileiro', function () {
        $product = Product::factory()->create([
            'name'   => 'Produto BRL',
            'slug'   => 'produto-brl',
            'price'  => 12345, // R$ 123.45
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('R$');
        $response->assertSee('123.45');
    });

    test('parcelamento com juros exibe valor correto', function () {
        config(['payment.installments.interest_free' => 3]);
        config(['payment.installments.interest_rate' => 1.99]);
        config(['payment.installments.max_installments' => 12]);
        config(['payment.installments.min_value' => 500]);

        $product = Product::factory()->create([
            'name'   => 'Produto Juros',
            'slug'   => 'produto-juros',
            'price'  => 120000, // R$ 1.200,00
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        // Deve exibir opcoes de parcelamento
        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('12x');
        // Verifica que exibe informacao sobre juros
        expect($html)->toContain('com juros');
    });

    test('parcelamento sem juros e destacado quando disponivel', function () {
        config(['payment.installments.interest_free' => 3]);
        config(['payment.installments.min_value' => 500]);

        $product = Product::factory()->create([
            'name'   => 'Produto Sem Juros',
            'slug'   => 'produto-sem-juros',
            'price'  => 30000, // R$ 300,00
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        // Deve mostrar opcao sem juros (ate 3x)
        $response->assertSee('sem juros');
    });

    test('promocao com data de inicio futura nao ativa desconto', function () {
        $product = Product::factory()->create([
            'name'          => 'Promocao Futura',
            'slug'          => 'promocao-futura',
            'price'         => 20000,
            'sale_price'    => 15000,
            'sale_start_at' => now()->addDays(5),
            'status'        => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Nao deve mostrar preco riscado
        expect($html)->not->toContain('line-through');
        // Deve mostrar preco normal
        expect($html)->toContain('200.00');
    });

    test('promocao com data de fim passada nao ativa desconto', function () {
        $product = Product::factory()->create([
            'name'        => 'Promocao Expirada',
            'slug'        => 'promocao-expirada',
            'price'       => 20000,
            'sale_price'  => 15000,
            'sale_end_at' => now()->subDays(1),
            'status'      => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Nao deve mostrar preco riscado
        expect($html)->not->toContain('line-through');
    });
});

describe('US-04: Selecao de variantes do produto', function () {
    test('produtos variaveis exibem seletores de atributos', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Camiseta Variavel',
            'slug'   => 'camiseta-variavel',
            'status' => ProductStatus::Active,
        ]);

        $colorAttr  = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $colorValue = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->colorValue('Azul', '#0000FF')
            ->create(['attribute_id' => $colorAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant->attributeValues()->attach($colorValue->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Cor');
        $response->assertSee('Azul');
    });

    test('atributos de cor sao exibidos como swatches coloridos', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Cores',
            'slug'   => 'produto-cores',
            'status' => ProductStatus::Active,
        ]);

        $colorAttr = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $vermelho  = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->colorValue('Vermelho', '#FF0000')
            ->create(['attribute_id' => $colorAttr->id, 'position' => 1]);
        $verde = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->colorValue('Verde', '#00FF00')
            ->create(['attribute_id' => $colorAttr->id, 'position' => 2]);

        $variant1 = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant1->attributeValues()->attach($vermelho->id);

        $variant2 = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 5,
        ]);
        $variant2->attributeValues()->attach($verde->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que os swatches com cores hex sao exibidos
        expect($html)->toContain('#FF0000');
        expect($html)->toContain('#00FF00');
    });

    test('atributos de tamanho sao exibidos como botoes', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Tamanhos',
            'slug'   => 'produto-tamanhos',
            'status' => ProductStatus::Active,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id, 'position' => 1]);
        $sizeM = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('M')
            ->create(['attribute_id' => $sizeAttr->id, 'position' => 2]);
        $sizeG = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('G')
            ->create(['attribute_id' => $sizeAttr->id, 'position' => 3]);

        $variant1 = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant1->attributeValues()->attach($sizeP->id);

        $variant2 = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 5,
        ]);
        $variant2->attributeValues()->attach($sizeM->id);

        $variant3 = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 3,
        ]);
        $variant3->attributeValues()->attach($sizeG->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Tamanho');
        $response->assertSee('P');
        $response->assertSee('M');
        $response->assertSee('G');
    });

    test('ao selecionar variante o preco e atualizado', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Preco Variante',
            'slug'   => 'produto-preco-variante',
            'price'  => 10000, // R$ 100,00
            'status' => ProductStatus::Active,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()
            ->withPrice(15000) // R$ 150,00
            ->create([
                'product_id'     => $product->id,
                'stock_quantity' => 10,
            ]);
        $variant->attributeValues()->attach($sizeP->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSee('150.00');
    });

    test('ao selecionar variante o estoque e atualizado', function () {
        $product = Product::factory()->variable()->create([
            'name'           => 'Produto Estoque Variante',
            'slug'           => 'produto-estoque-variante',
            'stock_quantity' => 50,
            'status'         => ProductStatus::Active,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeM    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('M')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()
            ->outOfStock()
            ->create([
                'product_id' => $product->id,
            ]);
        $variant->attributeValues()->attach($sizeM->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSee('Esgotado');
    });

    test('variantes sem estoque aparecem desabilitadas', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Variante Esgotada',
            'slug'   => 'produto-variante-esgotada',
            'status' => ProductStatus::Active,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id, 'position' => 1]);
        $sizeM = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('M')
            ->create(['attribute_id' => $sizeAttr->id, 'position' => 2]);

        // Variante com estoque
        $variantP = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variantP->attributeValues()->attach($sizeP->id);

        // Variante sem estoque
        $variantM = \App\Domain\Catalog\Models\ProductVariant::factory()
            ->outOfStock()
            ->create([
                'product_id' => $product->id,
            ]);
        $variantM->attributeValues()->attach($sizeM->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que existe indicacao de variante sem estoque
        expect($html)->toMatch('/disabled|opacity|line-through|esgotado/i');
    });

    test('usuario deve selecionar variante antes de adicionar ao carrinho', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Selecao Obrigatoria',
            'slug'   => 'produto-selecao-obrigatoria',
            'status' => ProductStatus::Active,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant->attributeValues()->attach($sizeP->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        // Sem variante selecionada, botao deve indicar necessidade de selecao
        $html = $response->html(stripInitialData: false);
        expect($html)->toMatch('/Selecione|selecionar|escolha/i');
    });

    test('produtos simples nao exibem seletores de variantes', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Simples',
            'slug'   => 'produto-simples-sem-variante',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Nao deve exibir seletores de variantes
        expect($html)->not->toContain('Selecione');
        expect($html)->not->toContain('variant-selector');
    });

    test('selecionar variante atualiza imagem se houver imagem especifica', function () {
        $product = Product::factory()->variable()->create([
            'name'   => 'Produto Imagem Variante',
            'slug'   => 'produto-imagem-variante',
            'status' => ProductStatus::Active,
        ]);

        // Imagem do produto principal
        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'path'       => 'products/principal.jpg',
            'is_primary' => true,
        ]);

        $colorAttr = \App\Domain\Catalog\Models\Attribute::factory()->color()->create();
        $azul      = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->colorValue('Azul', '#0000FF')
            ->create(['attribute_id' => $colorAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant->attributeValues()->attach($azul->id);

        // Imagem especifica da variante
        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'path'       => 'products/variante-azul.jpg',
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('variante-azul.jpg');
    });
});

describe('US-05: Disponibilidade do produto', function () {
    test('produtos em estoque exibem Em estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Disponivel',
            'slug'           => 'produto-disponivel',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 50,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Em estoque');
    });

    test('produtos sem estoque exibem Esgotado', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Esgotado',
            'slug'           => 'produto-esgotado',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 0,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Esgotado');
    });

    test('produtos com estoque baixo exibem Ultimas unidades', function () {
        $product = Product::factory()->simple()->create([
            'name'                => 'Produto Ultimas Unidades',
            'slug'                => 'produto-ultimas-unidades',
            'status'              => ProductStatus::Active,
            'stock_quantity'      => 3,
            'manage_stock'        => true,
            'low_stock_threshold' => 5,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Ultimas unidades');
    });

    test('estoque baixo usa threshold padrao quando nao definido', function () {
        config(['inventory.default_low_stock_threshold' => 5]);

        $product = Product::factory()->simple()->create([
            'name'                => 'Produto Threshold Padrao',
            'slug'                => 'produto-threshold-padrao',
            'status'              => ProductStatus::Active,
            'stock_quantity'      => 4,
            'manage_stock'        => true,
            'low_stock_threshold' => null,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Ultimas unidades');
    });

    test('status de estoque atualiza ao selecionar variante com estoque', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variante Estoque',
            'slug'         => 'produto-variante-estoque',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 20,
        ]);
        $variant->attributeValues()->attach($sizeP->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSee('Em estoque');
    });

    test('status de estoque atualiza ao selecionar variante esgotada', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variante Esgotada',
            'slug'         => 'produto-variante-esgotada',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeM    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('M')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()
            ->outOfStock()
            ->create([
                'product_id' => $product->id,
            ]);
        $variant->attributeValues()->attach($sizeM->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSee('Esgotado');
    });

    test('status de estoque atualiza ao selecionar variante com estoque baixo', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variante Baixo Estoque',
            'slug'         => 'produto-variante-baixo-estoque',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeG    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('G')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'          => $product->id,
            'stock_quantity'      => 2,
            'low_stock_threshold' => 5,
        ]);
        $variant->attributeValues()->attach($sizeG->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSee('Ultimas unidades');
    });

    test('botao de comprar e desabilitado para produtos sem estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Botao Desabilitado',
            'slug'           => 'produto-botao-desabilitado',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 0,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica que o botao esta desabilitado
        expect($html)->toMatch('/disabled.*Adicionar ao Carrinho|Adicionar ao Carrinho.*disabled/s');
    });

    test('botao de comprar e habilitado para produtos em estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Botao Habilitado',
            'slug'           => 'produto-botao-habilitado',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Adicionar ao Carrinho');
        // Verifica que o produto esta em estoque
        expect($response->get('isCurrentlyInStock'))->toBeTrue();
    });

    test('produtos sem gerenciamento de estoque sempre exibem Em estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Sem Gerenciamento',
            'slug'           => 'produto-sem-gerenciamento',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 0,
            'manage_stock'   => false,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Em estoque');
    });

    test('produtos com backorders permitidos sempre exibem Em estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'             => 'Produto Backorder',
            'slug'             => 'produto-backorder',
            'status'           => ProductStatus::Active,
            'stock_quantity'   => 0,
            'manage_stock'     => true,
            'allow_backorders' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Em estoque');
    });
});

describe('US-06: Adicionar ao carrinho', function () {
    test('botao adicionar ao carrinho e exibido em destaque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Botao Carrinho',
            'slug'           => 'produto-botao-carrinho',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSee('Adicionar ao Carrinho');
    });

    test('seletor de quantidade e exibido', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Quantidade',
            'slug'           => 'produto-quantidade',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('Quantidade');
    });

    test('quantidade inicial e 1', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Qtd Inicial',
            'slug'           => 'produto-qtd-inicial',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSet('quantity', 1);
    });

    test('pode incrementar quantidade', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Incrementar',
            'slug'           => 'produto-incrementar',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('incrementQuantity');

        $response->assertSet('quantity', 2);
    });

    test('pode decrementar quantidade', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Decrementar',
            'slug'           => 'produto-decrementar',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('quantity', 3)
            ->call('decrementQuantity');

        $response->assertSet('quantity', 2);
    });

    test('quantidade minima e 1', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Qtd Minima',
            'slug'           => 'produto-qtd-minima',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('quantity', 1)
            ->call('decrementQuantity');

        $response->assertSet('quantity', 1);
    });

    test('quantidade maxima e limitada pelo estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Qtd Maxima',
            'slug'           => 'produto-qtd-maxima',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 5,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $response->assertSet('maxQuantity', 5);
    });

    test('nao pode incrementar alem do estoque', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Limite Estoque',
            'slug'           => 'produto-limite-estoque',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 3,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('quantity', 3)
            ->call('incrementQuantity');

        $response->assertSet('quantity', 3);
    });

    test('adicionar ao carrinho cria item no carrinho', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Adicionar',
            'slug'           => 'produto-adicionar',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('addToCart');

        $response->assertSet('showCartModal', true);
        $response->assertDispatched('cart-updated');
    });

    test('modal de confirmacao e exibido ao adicionar', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Modal',
            'slug'           => 'produto-modal',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('addToCart');

        $response->assertSet('showCartModal', true);
        $response->assertSet('addedItemName', 'Produto Modal');
    });

    test('produto variavel requer selecao de variante', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variavel Requer',
            'slug'         => 'produto-variavel-requer',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
        ]);
        $variant->attributeValues()->attach($sizeP->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('addToCart');

        $response->assertSet('cartErrorMessage', 'Selecione uma variacao do produto.');
        $response->assertSet('showCartModal', false);
    });

    test('produto variavel pode ser adicionado apos selecionar variante', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variavel Adicionar',
            'slug'         => 'produto-variavel-adicionar',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeM    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('M')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
            'price'          => 7500,
        ]);
        $variant->attributeValues()->attach($sizeM->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id)
            ->call('addToCart');

        $response->assertSet('showCartModal', true);
        $response->assertDispatched('cart-updated');
    });

    test('mensagem de erro e exibida quando estoque insuficiente', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Estoque Insuficiente',
            'slug'           => 'produto-estoque-insuficiente',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 2,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('quantity', 5)
            ->call('addToCart');

        $response->assertSet('showCartModal', false);
        expect($response->get('cartErrorMessage'))->toContain('Estoque insuficiente');
    });

    test('produto sem estoque nao pode ser adicionado', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Sem Estoque Carrinho',
            'slug'           => 'produto-sem-estoque-carrinho',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 0,
            'manage_stock'   => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        // Botao deve estar desabilitado
        expect($response->get('canAddToCart'))->toBeFalse();
    });

    test('quantidade maxima atualiza ao selecionar variante', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Variante Max Qtd',
            'slug'         => 'produto-variante-max-qtd',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeG    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('G')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 7,
        ]);
        $variant->attributeValues()->attach($sizeG->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        $response->assertSet('maxQuantity', 7);
    });

    test('pode fechar modal de confirmacao', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Fechar Modal',
            'slug'           => 'produto-fechar-modal',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('addToCart')
            ->call('closeCartModal');

        $response->assertSet('showCartModal', false);
    });

    test('quantidade reseta apos adicionar ao carrinho', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Reset Qtd',
            'slug'           => 'produto-reset-qtd',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->set('quantity', 3)
            ->call('addToCart');

        $response->assertSet('quantity', 1);
    });
});

describe('US-07: Experiencia responsiva', function () {
    test('pagina usa layout responsivo com grid em desktop', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Responsivo',
            'slug'   => 'produto-responsivo',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica classes responsivas do grid
        expect($html)->toContain('lg:grid');
        expect($html)->toContain('lg:grid-cols-2');
    });

    test('imagens usam lazy loading', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Lazy',
            'slug'   => 'produto-lazy',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('loading="lazy"');
    });

    test('barra de acao fixa e exibida em mobile', function () {
        $product = Product::factory()->simple()->create([
            'name'           => 'Produto Mobile Bar',
            'slug'           => 'produto-mobile-bar',
            'status'         => ProductStatus::Active,
            'stock_quantity' => 10,
            'manage_stock'   => true,
            'price'          => 5000,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica presenca da barra fixa mobile
        expect($html)->toContain('fixed bottom-0');
        expect($html)->toContain('lg:hidden');
    });

    test('galeria ocupa largura total em mobile', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Galeria Mobile',
            'slug'   => 'produto-galeria-mobile',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->create([
            'product_id' => $product->id,
            'is_primary' => true,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Galeria com aspect-square ocupa 100% da largura disponivel
        expect($html)->toContain('aspect-square');
    });

    test('interacoes sao feitas via livewire sem reload', function () {
        $product = Product::factory()->variable()->create([
            'name'         => 'Produto Livewire',
            'slug'         => 'produto-livewire',
            'status'       => ProductStatus::Active,
            'manage_stock' => true,
        ]);

        $sizeAttr = \App\Domain\Catalog\Models\Attribute::factory()->size()->create();
        $sizeP    = \App\Domain\Catalog\Models\AttributeValue::factory()
            ->size('P')
            ->create(['attribute_id' => $sizeAttr->id]);

        $variant = \App\Domain\Catalog\Models\ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 10,
            'price'          => 5000,
        ]);
        $variant->attributeValues()->attach($sizeP->id);

        // Seleciona variante via Livewire (sem reload)
        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug])
            ->call('selectVariant', $variant->id);

        // Verifica que a variante foi selecionada
        $response->assertSet('selectedVariantId', $variant->id);
    });

    test('thumbnails sao responsivos com diferentes colunas', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Thumbnails',
            'slug'   => 'produto-thumbnails',
            'status' => ProductStatus::Active,
        ]);

        \App\Domain\Catalog\Models\ProductImage::factory()->count(3)->create([
            'product_id' => $product->id,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        // Verifica grid responsivo de thumbnails
        expect($html)->toContain('grid-cols-4');
        expect($html)->toContain('sm:grid-cols-5');
    });
});

describe('US-08: Produtos relacionados', function () {
    test('secao de produtos relacionados e exibida quando ha produtos na mesma categoria', function () {
        $category = \App\Domain\Catalog\Models\Category::factory()->create();

        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Principal',
            'slug'   => 'produto-principal',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category->id);

        // Cria produtos relacionados na mesma categoria
        $relatedProducts = Product::factory()->simple()->count(3)->create([
            'status' => ProductStatus::Active,
        ]);

        foreach ($relatedProducts as $related) {
            $related->categories()->attach($category->id);
        }

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('Produtos Relacionados');
    });

    test('secao de produtos relacionados e ocultada quando nao ha produtos', function () {
        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Sem Relacionados',
            'slug'   => 'produto-sem-relacionados',
            'status' => ProductStatus::Active,
        ]);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->not->toContain('Produtos Relacionados');
    });

    test('produtos relacionados sao da mesma categoria', function () {
        $category      = \App\Domain\Catalog\Models\Category::factory()->create(['name' => 'Categoria Teste']);
        $otherCategory = \App\Domain\Catalog\Models\Category::factory()->create(['name' => 'Outra Categoria']);

        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Principal Categoria',
            'slug'   => 'produto-principal-categoria',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category->id);

        // Produto na mesma categoria
        $sameCategory = Product::factory()->simple()->create([
            'name'   => 'Produto Mesma Categoria',
            'slug'   => 'produto-mesma-categoria',
            'status' => ProductStatus::Active,
        ]);
        $sameCategory->categories()->attach($category->id);

        // Produto em outra categoria
        $differentCategory = Product::factory()->simple()->create([
            'name'   => 'Produto Outra Categoria',
            'slug'   => 'produto-outra-categoria',
            'status' => ProductStatus::Active,
        ]);
        $differentCategory->categories()->attach($otherCategory->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('Produto Mesma Categoria');
        expect($html)->not->toContain('Produto Outra Categoria');
    });

    test('exibe no maximo 4 produtos relacionados', function () {
        $category = \App\Domain\Catalog\Models\Category::factory()->create();

        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Principal Max',
            'slug'   => 'produto-principal-max',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category->id);

        // Cria 8 produtos relacionados
        for ($i = 1; $i <= 8; $i++) {
            $related = Product::factory()->simple()->create([
                'name'   => "Relacionado $i",
                'slug'   => "relacionado-$i",
                'status' => ProductStatus::Active,
            ]);
            $related->categories()->attach($category->id);
        }

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        // Verifica que relatedProducts tem no maximo 4 itens
        expect($response->get('relatedProducts'))->toHaveCount(4);
    });

    test('produto atual nao aparece nos relacionados', function () {
        $category = \App\Domain\Catalog\Models\Category::factory()->create();

        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Nao Duplica',
            'slug'   => 'produto-nao-duplica',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $relatedProducts = $response->get('relatedProducts');
        $relatedIds      = $relatedProducts->pluck('id')->toArray();

        expect($relatedIds)->not->toContain($product->id);
    });

    test('apenas produtos ativos aparecem nos relacionados', function () {
        $category = \App\Domain\Catalog\Models\Category::factory()->create();

        $product = Product::factory()->simple()->create([
            'name'   => 'Produto Ativos',
            'slug'   => 'produto-ativos',
            'status' => ProductStatus::Active,
        ]);
        $product->categories()->attach($category->id);

        // Produto ativo
        $active = Product::factory()->simple()->create([
            'name'   => 'Produto Ativo Relacionado',
            'slug'   => 'produto-ativo-relacionado',
            'status' => ProductStatus::Active,
        ]);
        $active->categories()->attach($category->id);

        // Produto inativo
        $inactive = Product::factory()->simple()->create([
            'name'   => 'Produto Inativo Relacionado',
            'slug'   => 'produto-inativo-relacionado',
            'status' => ProductStatus::Draft,
        ]);
        $inactive->categories()->attach($category->id);

        $response = Livewire::test(ProductShow::class, ['slug' => $product->slug]);

        $html = $response->html(stripInitialData: false);
        expect($html)->toContain('Produto Ativo Relacionado');
        expect($html)->not->toContain('Produto Inativo Relacionado');
    });
});
