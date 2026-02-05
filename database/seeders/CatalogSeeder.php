<?php declare(strict_types = 1);

namespace Database\Seeders;

use App\Domain\Catalog\Enums\{AttributeType, ProductStatus, ProductType};
use App\Domain\Catalog\Models\{Attribute, AttributeValue, Category, Product, ProductImage, ProductVariant, Tag};
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = $this->createCategories();
        $attributes = $this->createAttributes();
        $tags       = $this->createTags();

        $this->createSimpleProducts($categories, $attributes, $tags);
        $this->createVariableProducts($categories, $attributes, $tags);
    }

    /**
     * Create categories with hierarchy.
     *
     * @return array<string, Category>
     */
    private function createCategories(): array
    {
        $categories = [];

        // Root categories
        $rootCategories = [
            [
                'name'        => 'Eletrônicos',
                'description' => 'Produtos eletrônicos e tecnologia',
                'position'    => 1,
                'children'    => [
                    ['name' => 'Smartphones', 'description' => 'Celulares e smartphones', 'position' => 1],
                    ['name' => 'Notebooks', 'description' => 'Notebooks e laptops', 'position' => 2],
                    ['name' => 'Acessórios de Informática', 'description' => 'Periféricos e acessórios', 'position' => 3],
                ],
            ],
            [
                'name'        => 'Moda',
                'description' => 'Roupas, calçados e acessórios',
                'position'    => 2,
                'children'    => [
                    ['name' => 'Camisetas', 'description' => 'Camisetas masculinas e femininas', 'position' => 1],
                    ['name' => 'Calças', 'description' => 'Calças jeans, sociais e casuais', 'position' => 2],
                    ['name' => 'Calçados', 'description' => 'Tênis, sapatos e sandálias', 'position' => 3],
                ],
            ],
            [
                'name'        => 'Casa e Decoração',
                'description' => 'Móveis, decoração e utilidades domésticas',
                'position'    => 3,
                'children'    => [
                    ['name' => 'Móveis', 'description' => 'Sofás, mesas e cadeiras', 'position' => 1],
                    ['name' => 'Decoração', 'description' => 'Quadros, vasos e objetos decorativos', 'position' => 2],
                ],
            ],
            [
                'name'        => 'Esportes',
                'description' => 'Artigos esportivos e fitness',
                'position'    => 4,
                'children'    => [],
            ],
        ];

        foreach ($rootCategories as $rootData) {
            $children = $rootData['children'];
            unset($rootData['children']);

            $root = Category::create([
                ...$rootData,
                'slug'      => Str::slug($rootData['name']),
                'is_active' => true,
            ]);

            $categories[$root->slug] = $root;

            foreach ($children as $childData) {
                $child = Category::create([
                    ...$childData,
                    'slug'      => Str::slug($childData['name']),
                    'parent_id' => $root->id,
                    'is_active' => true,
                ]);

                $categories[$child->slug] = $child;
            }
        }

        return $categories;
    }

    /**
     * Create attributes with values.
     *
     * @return array<string, Attribute>
     */
    private function createAttributes(): array
    {
        $attributesData = [
            [
                'name'     => 'Cor',
                'slug'     => 'cor',
                'type'     => AttributeType::Color,
                'position' => 1,
                'values'   => [
                    ['value' => 'Preto', 'color_hex' => '#000000', 'position' => 1],
                    ['value' => 'Branco', 'color_hex' => '#FFFFFF', 'position' => 2],
                    ['value' => 'Azul', 'color_hex' => '#0066CC', 'position' => 3],
                    ['value' => 'Vermelho', 'color_hex' => '#CC0000', 'position' => 4],
                    ['value' => 'Verde', 'color_hex' => '#00CC66', 'position' => 5],
                    ['value' => 'Cinza', 'color_hex' => '#808080', 'position' => 6],
                    ['value' => 'Rosa', 'color_hex' => '#FF69B4', 'position' => 7],
                    ['value' => 'Amarelo', 'color_hex' => '#FFD700', 'position' => 8],
                ],
            ],
            [
                'name'     => 'Tamanho',
                'slug'     => 'tamanho',
                'type'     => AttributeType::Select,
                'position' => 2,
                'values'   => [
                    ['value' => 'PP', 'position' => 1],
                    ['value' => 'P', 'position' => 2],
                    ['value' => 'M', 'position' => 3],
                    ['value' => 'G', 'position' => 4],
                    ['value' => 'GG', 'position' => 5],
                    ['value' => 'XGG', 'position' => 6],
                ],
            ],
            [
                'name'     => 'Material',
                'slug'     => 'material',
                'type'     => AttributeType::Select,
                'position' => 3,
                'values'   => [
                    ['value' => 'Algodão', 'position' => 1],
                    ['value' => 'Poliéster', 'position' => 2],
                    ['value' => 'Couro', 'position' => 3],
                    ['value' => 'Sintético', 'position' => 4],
                    ['value' => 'Linho', 'position' => 5],
                ],
            ],
            [
                'name'     => 'Capacidade',
                'slug'     => 'capacidade',
                'type'     => AttributeType::Select,
                'position' => 4,
                'values'   => [
                    ['value' => '64GB', 'position' => 1],
                    ['value' => '128GB', 'position' => 2],
                    ['value' => '256GB', 'position' => 3],
                    ['value' => '512GB', 'position' => 4],
                    ['value' => '1TB', 'position' => 5],
                ],
            ],
            [
                'name'     => 'Voltagem',
                'slug'     => 'voltagem',
                'type'     => AttributeType::Select,
                'position' => 5,
                'values'   => [
                    ['value' => '110V', 'position' => 1],
                    ['value' => '220V', 'position' => 2],
                    ['value' => 'Bivolt', 'position' => 3],
                ],
            ],
        ];

        $attributes = [];

        foreach ($attributesData as $attrData) {
            $values = $attrData['values'];
            unset($attrData['values']);

            $attribute                    = Attribute::create($attrData);
            $attributes[$attribute->slug] = $attribute;

            foreach ($values as $valueData) {
                AttributeValue::create([
                    'attribute_id' => $attribute->id,
                    ...$valueData,
                ]);
            }
        }

        return $attributes;
    }

    /**
     * Create tags.
     *
     * @return array<string, Tag>
     */
    private function createTags(): array
    {
        $tagsData = [
            'Lançamento',
            'Promoção',
            'Mais Vendido',
            'Exclusivo',
            'Frete Grátis',
            'Sustentável',
            'Premium',
            'Oferta Relâmpago',
            'Novidade',
            'Destaque',
        ];

        $tags = [];

        foreach ($tagsData as $name) {
            $tag = Tag::create([
                'name' => $name,
                'slug' => Str::slug($name),
            ]);

            $tags[$tag->slug] = $tag;
        }

        return $tags;
    }

    /**
     * Create simple products.
     *
     * @param  array<string, Category>  $categories
     * @param  array<string, Attribute>  $attributes
     * @param  array<string, Tag>  $tags
     */
    private function createSimpleProducts(array $categories, array $attributes, array $tags): void
    {
        $simpleProducts = [
            // Eletrônicos
            [
                'name'              => 'Fone de Ouvido Bluetooth Premium',
                'short_description' => 'Fone sem fio com cancelamento de ruído ativo',
                'description'       => 'Experimente o som de alta qualidade com nosso fone de ouvido Bluetooth Premium. Com cancelamento de ruído ativo, bateria de longa duração e design ergonômico para máximo conforto.',
                'price'             => 29900,
                'sale_price'        => 24900,
                'sku'               => 'FON-BT-001',
                'stock_quantity'    => 50,
                'categories'        => ['eletronicos', 'acessorios-de-informatica'],
                'tags'              => ['lancamento', 'mais-vendido'],
            ],
            [
                'name'              => 'Mouse Gamer RGB',
                'short_description' => 'Mouse gamer com iluminação RGB e 7 botões programáveis',
                'description'       => 'Mouse gamer de alta precisão com sensor óptico de 16000 DPI, iluminação RGB personalizável e 7 botões programáveis. Ideal para jogos competitivos.',
                'price'             => 15900,
                'sku'               => 'MOU-GAM-001',
                'stock_quantity'    => 80,
                'categories'        => ['eletronicos', 'acessorios-de-informatica'],
                'tags'              => ['mais-vendido'],
            ],
            [
                'name'              => 'Teclado Mecânico Compacto',
                'short_description' => 'Teclado mecânico 60% com switches blue',
                'description'       => 'Teclado mecânico compacto com layout 60%, switches blue para feedback tátil e sonoro, iluminação RGB por tecla e construção em alumínio.',
                'price'             => 34900,
                'sale_price'        => 29900,
                'sku'               => 'TEC-MEC-001',
                'stock_quantity'    => 35,
                'categories'        => ['eletronicos', 'acessorios-de-informatica'],
                'tags'              => ['promocao', 'destaque'],
            ],
            [
                'name'              => 'Webcam Full HD',
                'short_description' => 'Webcam 1080p com microfone integrado',
                'description'       => 'Webcam Full HD 1080p com foco automático, microfone com redução de ruído e clipe universal para monitores e notebooks.',
                'price'             => 19900,
                'sku'               => 'WEB-FHD-001',
                'stock_quantity'    => 45,
                'categories'        => ['eletronicos', 'acessorios-de-informatica'],
                'tags'              => ['frete-gratis'],
            ],
            [
                'name'              => 'Carregador Portátil 20000mAh',
                'short_description' => 'Power bank de alta capacidade com carregamento rápido',
                'description'       => 'Carregador portátil com capacidade de 20000mAh, suporte a carregamento rápido PD 65W, 2 portas USB-C e 1 porta USB-A.',
                'price'             => 17900,
                'sku'               => 'PWB-20K-001',
                'stock_quantity'    => 60,
                'categories'        => ['eletronicos'],
                'tags'              => ['mais-vendido', 'frete-gratis'],
            ],
            // Casa e Decoração
            [
                'name'              => 'Luminária de Mesa LED',
                'short_description' => 'Luminária articulada com controle de intensidade',
                'description'       => 'Luminária de mesa LED com braço articulado, 5 níveis de intensidade, temperatura de cor ajustável e porta USB para carregar dispositivos.',
                'price'             => 12900,
                'sku'               => 'LUM-LED-001',
                'stock_quantity'    => 40,
                'categories'        => ['casa-e-decoracao', 'decoracao'],
                'tags'              => ['novidade'],
            ],
            [
                'name'              => 'Vaso Decorativo Cerâmica',
                'short_description' => 'Vaso artesanal em cerâmica com acabamento fosco',
                'description'       => 'Vaso decorativo feito à mão em cerâmica de alta qualidade. Acabamento fosco em tons neutros, perfeito para plantas ou como peça decorativa.',
                'price'             => 8900,
                'sku'               => 'VAS-CER-001',
                'stock_quantity'    => 25,
                'categories'        => ['casa-e-decoracao', 'decoracao'],
                'tags'              => ['sustentavel', 'exclusivo'],
            ],
            [
                'name'              => 'Quadro Abstrato 60x80cm',
                'short_description' => 'Quadro decorativo com arte abstrata moderna',
                'description'       => 'Quadro decorativo com impressão em canvas de alta qualidade. Arte abstrata moderna em tons de azul e dourado. Moldura em madeira natural.',
                'price'             => 15900,
                'sale_price'        => 12900,
                'sku'               => 'QUA-ABS-001',
                'stock_quantity'    => 15,
                'categories'        => ['casa-e-decoracao', 'decoracao'],
                'tags'              => ['promocao'],
            ],
            // Esportes
            [
                'name'              => 'Tapete de Yoga Premium',
                'short_description' => 'Tapete antiderrapante de 6mm de espessura',
                'description'       => 'Tapete de yoga premium com 6mm de espessura, superfície antiderrapante, material ecológico e alça para transporte. Dimensões: 183x61cm.',
                'price'             => 9900,
                'sku'               => 'TAP-YOG-001',
                'stock_quantity'    => 70,
                'categories'        => ['esportes'],
                'tags'              => ['sustentavel', 'mais-vendido'],
            ],
            [
                'name'              => 'Corda de Pular Profissional',
                'short_description' => 'Corda de pular com rolamentos de alta velocidade',
                'description'       => 'Corda de pular profissional com cabo de aço revestido, rolamentos de alta velocidade e manoplas ergonômicas. Comprimento ajustável.',
                'price'             => 4900,
                'sku'               => 'COR-PUL-001',
                'stock_quantity'    => 100,
                'categories'        => ['esportes'],
                'tags'              => ['oferta-relampago'],
            ],
            [
                'name'              => 'Kit Halteres Emborrachados',
                'short_description' => 'Par de halteres de 5kg cada com revestimento emborrachado',
                'description'       => 'Kit com par de halteres de 5kg cada. Revestimento emborrachado para proteção do piso e melhor aderência. Ideal para treinos em casa.',
                'price'             => 14900,
                'sku'               => 'HAL-5KG-001',
                'stock_quantity'    => 55,
                'categories'        => ['esportes'],
                'tags'              => ['frete-gratis'],
            ],
            [
                'name'              => 'Garrafa Térmica 750ml',
                'short_description' => 'Garrafa térmica em aço inox com isolamento a vácuo',
                'description'       => 'Garrafa térmica de 750ml em aço inoxidável com isolamento a vácuo. Mantém bebidas geladas por 24h ou quentes por 12h. Livre de BPA.',
                'price'             => 7900,
                'sku'               => 'GAR-TER-001',
                'stock_quantity'    => 90,
                'categories'        => ['esportes'],
                'tags'              => ['sustentavel', 'destaque'],
            ],
        ];

        foreach ($simpleProducts as $productData) {
            $categoryKeys = $productData['categories'];
            $tagKeys      = $productData['tags'];
            unset($productData['categories'], $productData['tags']);

            $product = Product::create([
                ...$productData,
                'slug'             => Str::slug($productData['name']),
                'type'             => ProductType::Simple,
                'status'           => ProductStatus::Active,
                'manage_stock'     => true,
                'allow_backorders' => false,
            ]);

            // Attach categories
            $categoryIds = collect($categoryKeys)
                ->map(fn ($key) => $categories[$key]->id ?? null)
                ->filter()
                ->toArray();
            $product->categories()->attach($categoryIds);

            // Attach tags
            $tagIds = collect($tagKeys)
                ->map(fn ($key) => $tags[$key]->id ?? null)
                ->filter()
                ->toArray();
            $product->tags()->attach($tagIds);

            // Create product images
            ProductImage::factory()
                ->primary()
                ->create([
                    'product_id' => $product->id,
                    'alt_text'   => $product->name,
                    'position'   => 1,
                ]);

            ProductImage::factory()
                ->count(rand(1, 3))
                ->create([
                    'product_id' => $product->id,
                ]);
        }

        // Create additional simple products using factory
        Product::factory()
            ->count(18)
            ->simple()
            ->active()
            ->create()
            ->each(function (Product $product) use ($categories, $tags) {
                // Attach random categories
                $randomCategories = collect($categories)->random(rand(1, 2))->pluck('id');
                $product->categories()->attach($randomCategories);

                // Attach random tags
                $randomTags = collect($tags)->random(rand(1, 3))->pluck('id');
                $product->tags()->attach($randomTags);

                // Create images
                ProductImage::factory()
                    ->primary()
                    ->create([
                        'product_id' => $product->id,
                        'alt_text'   => $product->name,
                        'position'   => 1,
                    ]);

                ProductImage::factory()
                    ->count(rand(1, 2))
                    ->create(['product_id' => $product->id]);
            });
    }

    /**
     * Create variable products with variants.
     *
     * @param  array<string, Category>  $categories
     * @param  array<string, Attribute>  $attributes
     * @param  array<string, Tag>  $tags
     */
    private function createVariableProducts(array $categories, array $attributes, array $tags): void
    {
        $colorAttribute    = $attributes['cor'];
        $sizeAttribute     = $attributes['tamanho'];
        $capacityAttribute = $attributes['capacidade'];

        $colorValues    = $colorAttribute->values()->get();
        $sizeValues     = $sizeAttribute->values()->get();
        $capacityValues = $capacityAttribute->values()->get();

        $variableProducts = [
            // Camisetas
            [
                'name'              => 'Camiseta Básica Algodão',
                'short_description' => 'Camiseta 100% algodão com corte regular',
                'description'       => 'Camiseta básica confeccionada em 100% algodão penteado. Corte regular, gola careca reforçada e acabamento premium. Disponível em várias cores e tamanhos.',
                'price'             => 5900,
                'sku'               => 'CAM-BAS-001',
                'categories'        => ['moda', 'camisetas'],
                'tags'              => ['mais-vendido', 'sustentavel'],
                'attributes'        => ['cor', 'tamanho'],
                'variants'          => $this->generateClothingVariants($colorValues->take(5), $sizeValues),
            ],
            [
                'name'              => 'Camiseta Estampada Premium',
                'short_description' => 'Camiseta com estampa exclusiva em algodão orgânico',
                'description'       => 'Camiseta premium com estampa exclusiva, confeccionada em algodão orgânico certificado. Tingimento natural e acabamento sustentável.',
                'price'             => 8900,
                'sale_price'        => 6900,
                'sku'               => 'CAM-EST-001',
                'categories'        => ['moda', 'camisetas'],
                'tags'              => ['lancamento', 'sustentavel', 'exclusivo'],
                'attributes'        => ['cor', 'tamanho'],
                'variants'          => $this->generateClothingVariants($colorValues->take(4), $sizeValues->take(4)),
            ],
            [
                'name'              => 'Polo Masculina Classic',
                'short_description' => 'Camisa polo em piquet de algodão',
                'description'       => 'Camisa polo clássica em piquet de algodão. Gola e punhos em ribana, botões de madrepérola e logo bordado discreto.',
                'price'             => 12900,
                'sku'               => 'POL-MAS-001',
                'categories'        => ['moda', 'camisetas'],
                'tags'              => ['premium', 'destaque'],
                'attributes'        => ['cor', 'tamanho'],
                'variants'          => $this->generateClothingVariants($colorValues->take(6), $sizeValues),
            ],
            // Calças
            [
                'name'              => 'Calça Jeans Slim',
                'short_description' => 'Calça jeans com corte slim e elastano',
                'description'       => 'Calça jeans com corte slim moderno. Composição com elastano para maior conforto e mobilidade. Lavagem média e detalhes em metal.',
                'price'             => 15900,
                'sale_price'        => 12900,
                'sku'               => 'CAL-JEA-001',
                'categories'        => ['moda', 'calcas'],
                'tags'              => ['promocao', 'mais-vendido'],
                'attributes'        => ['cor', 'tamanho'],
                'variants'          => $this->generateClothingVariants(
                    $colorValues->whereIn('value', ['Preto', 'Azul', 'Cinza']),
                    $sizeValues->take(5),
                ),
            ],
            [
                'name'              => 'Calça Moletom Confort',
                'short_description' => 'Calça de moletom com punho e bolsos',
                'description'       => 'Calça de moletom super confortável com punho nas barras, bolsos laterais e cordão de ajuste. Ideal para o dia a dia e atividades físicas leves.',
                'price'             => 9900,
                'sku'               => 'CAL-MOL-001',
                'categories'        => ['moda', 'calcas'],
                'tags'              => ['novidade', 'frete-gratis'],
                'attributes'        => ['cor', 'tamanho'],
                'variants'          => $this->generateClothingVariants(
                    $colorValues->whereIn('value', ['Preto', 'Cinza', 'Azul']),
                    $sizeValues,
                ),
            ],
            // Calçados
            [
                'name'              => 'Tênis Casual Urban',
                'short_description' => 'Tênis casual com solado em borracha',
                'description'       => 'Tênis casual com design moderno e versátil. Cabedal em material sintético premium, solado em borracha antiderrapante e palmilha anatômica.',
                'price'             => 19900,
                'sale_price'        => 15900,
                'sku'               => 'TEN-CAS-001',
                'categories'        => ['moda', 'calcados'],
                'tags'              => ['promocao', 'destaque'],
                'attributes'        => ['cor'],
                'variants'          => $this->generateShoeVariants($colorValues->take(4)),
            ],
            [
                'name'              => 'Tênis Esportivo Runner',
                'short_description' => 'Tênis para corrida com tecnologia de amortecimento',
                'description'       => 'Tênis esportivo desenvolvido para corrida. Tecnologia de amortecimento no calcanhar, cabedal em mesh respirável e solado com tração multidirecional.',
                'price'             => 29900,
                'sku'               => 'TEN-RUN-001',
                'categories'        => ['moda', 'calcados', 'esportes'],
                'tags'              => ['lancamento', 'premium'],
                'attributes'        => ['cor'],
                'variants'          => $this->generateShoeVariants($colorValues->take(5)),
            ],
            // Smartphones
            [
                'name'              => 'Smartphone Galaxy Pro',
                'short_description' => 'Smartphone com tela AMOLED e câmera de 108MP',
                'description'       => 'Smartphone de última geração com tela AMOLED de 6.7", câmera principal de 108MP, processador octa-core e bateria de 5000mAh com carregamento rápido.',
                'price'             => 399900,
                'sale_price'        => 349900,
                'sku'               => 'SMA-GAL-001',
                'categories'        => ['eletronicos', 'smartphones'],
                'tags'              => ['lancamento', 'destaque', 'frete-gratis'],
                'attributes'        => ['cor', 'capacidade'],
                'variants'          => $this->generateSmartphoneVariants(
                    $colorValues->whereIn('value', ['Preto', 'Branco', 'Azul']),
                    $capacityValues->whereIn('value', ['128GB', '256GB', '512GB']),
                ),
            ],
            [
                'name'              => 'Smartphone Lite Edition',
                'short_description' => 'Smartphone custo-benefício com ótimo desempenho',
                'description'       => 'Smartphone com excelente custo-benefício. Tela IPS de 6.5", câmera tripla de 48MP, 4GB de RAM e bateria de 5000mAh.',
                'price'             => 149900,
                'sku'               => 'SMA-LIT-001',
                'categories'        => ['eletronicos', 'smartphones'],
                'tags'              => ['mais-vendido', 'oferta-relampago'],
                'attributes'        => ['cor', 'capacidade'],
                'variants'          => $this->generateSmartphoneVariants(
                    $colorValues->whereIn('value', ['Preto', 'Azul', 'Verde']),
                    $capacityValues->whereIn('value', ['64GB', '128GB']),
                ),
            ],
            // Notebooks
            [
                'name'              => 'Notebook UltraBook Pro',
                'short_description' => 'Notebook ultrafino com processador de última geração',
                'description'       => 'Notebook ultrafino com tela Full HD de 14", processador Intel Core i7, 16GB de RAM, SSD NVMe e bateria com autonomia de até 12 horas.',
                'price'             => 599900,
                'sale_price'        => 549900,
                'sku'               => 'NOT-ULT-001',
                'categories'        => ['eletronicos', 'notebooks'],
                'tags'              => ['premium', 'frete-gratis', 'destaque'],
                'attributes'        => ['cor', 'capacidade'],
                'variants'          => $this->generateNotebookVariants(
                    $colorValues->whereIn('value', ['Cinza', 'Preto']),
                    $capacityValues->whereIn('value', ['256GB', '512GB', '1TB']),
                ),
            ],
        ];

        foreach ($variableProducts as $productData) {
            $categoryKeys  = $productData['categories'];
            $tagKeys       = $productData['tags'];
            $attributeKeys = $productData['attributes'];
            $variantsData  = $productData['variants'];
            unset($productData['categories'], $productData['tags'], $productData['attributes'], $productData['variants']);

            $product = Product::create([
                ...$productData,
                'slug'             => Str::slug($productData['name']),
                'type'             => ProductType::Variable,
                'status'           => ProductStatus::Active,
                'manage_stock'     => true,
                'allow_backorders' => false,
            ]);

            // Attach categories
            $categoryIds = collect($categoryKeys)
                ->map(fn ($key) => $categories[$key]->id ?? null)
                ->filter()
                ->toArray();
            $product->categories()->attach($categoryIds);

            // Attach tags
            $tagIds = collect($tagKeys)
                ->map(fn ($key) => $tags[$key]->id ?? null)
                ->filter()
                ->toArray();
            $product->tags()->attach($tagIds);

            // Attach attributes to product
            foreach ($attributeKeys as $attrKey) {
                $attribute = $attributes[$attrKey];

                foreach ($attribute->values as $value) {
                    $product->productAttributes()->attach($attribute->id, [
                        'attribute_value_id'  => $value->id,
                        'used_for_variations' => true,
                    ]);
                }
            }

            // Create variants
            $variantCounter = 1;

            foreach ($variantsData as $variantData) {
                $attributeValueIds = $variantData['attribute_value_ids'];
                unset($variantData['attribute_value_ids']);

                // Generate unique SKU using product SKU as prefix
                $variantData['sku'] = sprintf('%s-V%03d', $product->sku, $variantCounter++);

                $variant = ProductVariant::create([
                    'product_id' => $product->id,
                    ...$variantData,
                ]);

                $variant->attributeValues()->attach($attributeValueIds);
            }

            // Create product images
            ProductImage::factory()
                ->primary()
                ->create([
                    'product_id' => $product->id,
                    'alt_text'   => $product->name,
                    'position'   => 1,
                ]);

            ProductImage::factory()
                ->count(rand(2, 4))
                ->create([
                    'product_id' => $product->id,
                ]);
        }

        // Create additional variable products using factory
        Product::factory()
            ->count(10)
            ->variable()
            ->active()
            ->create()
            ->each(function (Product $product) use ($categories, $tags, $colorValues, $sizeValues) {
                // Attach random categories
                $randomCategories = collect($categories)->random(rand(1, 2))->pluck('id');
                $product->categories()->attach($randomCategories);

                // Attach random tags
                $randomTags = collect($tags)->random(rand(1, 3))->pluck('id');
                $product->tags()->attach($randomTags);

                // Create simple variants
                $selectedColors = $colorValues->random(rand(2, 4));
                $selectedSizes  = $sizeValues->random(rand(3, 5));

                foreach ($selectedColors as $color) {
                    foreach ($selectedSizes as $size) {
                        $variant = ProductVariant::factory()->create([
                            'product_id' => $product->id,
                        ]);

                        $variant->attributeValues()->attach([$color->id, $size->id]);
                    }
                }

                // Create images
                ProductImage::factory()
                    ->primary()
                    ->create([
                        'product_id' => $product->id,
                        'alt_text'   => $product->name,
                        'position'   => 1,
                    ]);

                ProductImage::factory()
                    ->count(rand(1, 3))
                    ->create(['product_id' => $product->id]);
            });
    }

    /**
     * Generate clothing variants (color + size combinations).
     *
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $colors
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $sizes
     * @return array<int, array<string, mixed>>
     */
    private function generateClothingVariants($colors, $sizes): array
    {
        $variants = [];

        foreach ($colors as $color) {
            foreach ($sizes as $size) {
                $variants[] = [
                    'stock_quantity'      => rand(5, 30),
                    'is_active'           => true,
                    'attribute_value_ids' => [$color->id, $size->id],
                ];
            }
        }

        return $variants;
    }

    /**
     * Generate shoe variants (color only, sizes would be numeric).
     *
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $colors
     * @return array<int, array<string, mixed>>
     */
    private function generateShoeVariants($colors): array
    {
        $variants = [];

        foreach ($colors as $color) {
            // Simulate shoe sizes 38-44
            for ($shoeSize = 38; $shoeSize <= 44; $shoeSize++) {
                $variants[] = [
                    'stock_quantity'      => rand(3, 15),
                    'is_active'           => true,
                    'attribute_value_ids' => [$color->id],
                ];
            }
        }

        return $variants;
    }

    /**
     * Generate smartphone variants (color + capacity combinations).
     *
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $colors
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $capacities
     * @return array<int, array<string, mixed>>
     */
    private function generateSmartphoneVariants($colors, $capacities): array
    {
        $variants = [];

        // Price adjustments based on capacity
        $priceAdjustments = [
            '64GB'  => 0,
            '128GB' => 10000,
            '256GB' => 25000,
            '512GB' => 50000,
            '1TB'   => 100000,
        ];

        foreach ($colors as $color) {
            foreach ($capacities as $capacity) {
                $priceAdjustment = $priceAdjustments[$capacity->value] ?? 0;

                $variants[] = [
                    'price'               => $priceAdjustment > 0 ? $priceAdjustment : null,
                    'stock_quantity'      => rand(5, 20),
                    'is_active'           => true,
                    'attribute_value_ids' => [$color->id, $capacity->id],
                ];
            }
        }

        return $variants;
    }

    /**
     * Generate notebook variants (color + storage combinations).
     *
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $colors
     * @param  \Illuminate\Support\Collection<int, AttributeValue>  $storages
     * @return array<int, array<string, mixed>>
     */
    private function generateNotebookVariants($colors, $storages): array
    {
        $variants = [];

        // Price adjustments based on storage
        $priceAdjustments = [
            '256GB' => 0,
            '512GB' => 30000,
            '1TB'   => 80000,
        ];

        foreach ($colors as $color) {
            foreach ($storages as $storage) {
                $priceAdjustment = $priceAdjustments[$storage->value] ?? 0;

                $variants[] = [
                    'price'               => $priceAdjustment > 0 ? $priceAdjustment : null,
                    'stock_quantity'      => rand(3, 10),
                    'is_active'           => true,
                    'attribute_value_ids' => [$color->id, $storage->id],
                ];
            }
        }

        return $variants;
    }
}
