<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-4xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ route('admin.products.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                            <flux:icon name="arrow-left" class="size-5" />
                        </a>
                        <div>
                            <flux:heading size="xl">{{ __('Editar Produto') }}</flux:heading>
                            <flux:subheading>{{ $product->name }}</flux:subheading>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @switch($product->status->value)
                            @case('active')
                                <flux:badge color="green">{{ __('Ativo') }}</flux:badge>
                                @break
                            @case('inactive')
                                <flux:badge color="red">{{ __('Inativo') }}</flux:badge>
                                @break
                            @case('draft')
                                <flux:badge color="zinc">{{ __('Rascunho') }}</flux:badge>
                                @break
                        @endswitch
                    </div>
                </div>

                {{-- Success Message --}}
                @if (session('success'))
                    <flux:callout variant="success" class="mb-6">
                        {{ session('success') }}
                    </flux:callout>
                @endif

                {{-- Error Messages --}}
                @if ($errors->any())
                    <flux:callout variant="danger" class="mb-6">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </flux:callout>
                @endif

                {{-- Form --}}
                <form method="POST" action="{{ route('admin.products.update', $product) }}" enctype="multipart/form-data">
                    @csrf
                    @method('PUT')

                    <div x-data="{ activeTab: 'general', productType: '{{ old('type', $product->type->value) }}' }" class="space-y-6">
                        {{-- Tabs Navigation --}}
                        <div class="flex items-center gap-1 border-b border-neutral-800">
                            <button
                                type="button"
                                @click="activeTab = 'general'"
                                :class="activeTab === 'general' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Geral') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'pricing'"
                                :class="activeTab === 'pricing' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Preços') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'stock'"
                                :class="activeTab === 'stock' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Estoque') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'attributes'"
                                x-show="productType === 'variable'"
                                :class="activeTab === 'attributes' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Variantes') }}
                                @if ($product->variants->count() > 0)
                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-neutral-700">{{ $product->variants->count() }}</span>
                                @endif
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'images'"
                                :class="activeTab === 'images' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Imagens') }}
                                @if ($product->images->count() > 0)
                                    <span class="ml-1 px-1.5 py-0.5 text-xs rounded-full bg-neutral-700">{{ $product->images->count() }}</span>
                                @endif
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'shipping'"
                                :class="activeTab === 'shipping' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('Entrega') }}
                            </button>
                            <button
                                type="button"
                                @click="activeTab = 'seo'"
                                :class="activeTab === 'seo' ? 'border-blue-500 text-white' : 'border-transparent text-neutral-400 hover:text-white'"
                                class="px-4 py-3 text-sm font-medium border-b-2 transition-colors -mb-px"
                            >
                                {{ __('SEO') }}
                            </button>
                        </div>

                        {{-- Tab: General --}}
                        <div x-show="activeTab === 'general'" class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- Name --}}
                                <flux:input
                                    name="name"
                                    :label="__('Nome do Produto')"
                                    :value="old('name', $product->name)"
                                    type="text"
                                    required
                                    placeholder="Ex: Camiseta Básica Algodão"
                                />

                                {{-- Slug --}}
                                <flux:input
                                    name="slug"
                                    :label="__('Slug')"
                                    :value="old('slug', $product->slug)"
                                    type="text"
                                    placeholder="deixe vazio para gerar automaticamente"
                                />

                                {{-- Type --}}
                                <flux:field>
                                    <flux:label>{{ __('Tipo de Produto') }}</flux:label>
                                    <flux:select name="type" x-model="productType" required>
                                        @foreach (\App\Domain\Catalog\Enums\ProductType::options() as $value => $label)
                                            <option value="{{ $value }}" @selected(old('type', $product->type->value) === $value)>{{ $label }}</option>
                                        @endforeach
                                    </flux:select>
                                    <flux:description>{{ __('Produtos variáveis permitem criar variantes com diferentes cores, tamanhos, etc.') }}</flux:description>
                                </flux:field>

                                {{-- Status --}}
                                <flux:select name="status" :label="__('Status')" required>
                                    @foreach (\App\Domain\Catalog\Enums\ProductStatus::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', $product->status->value) === $value)>{{ $label }}</option>
                                    @endforeach
                                </flux:select>

                                {{-- Short Description --}}
                                <flux:textarea
                                    name="short_description"
                                    :label="__('Descrição Curta')"
                                    :value="old('short_description', $product->short_description)"
                                    placeholder="Breve descrição do produto (exibida na listagem)"
                                    rows="2"
                                    maxlength="500"
                                />

                                {{-- Description --}}
                                <flux:textarea
                                    name="description"
                                    :label="__('Descrição Completa')"
                                    :value="old('description', $product->description)"
                                    placeholder="Descrição detalhada do produto"
                                    rows="6"
                                />

                                {{-- Categories --}}
                                @php
                                    $selectedCategories = old('categories', $product->categories->pluck('id')->toArray());
                                @endphp
                                <flux:field>
                                    <flux:label>{{ __('Categorias') }}</flux:label>
                                    <div class="grid grid-cols-2 gap-2 mt-2 max-h-48 overflow-y-auto p-3 bg-neutral-800/50 rounded-lg">
                                        @foreach ($categories as $category)
                                            <label class="flex items-center gap-2 text-sm text-neutral-300 hover:text-white cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="categories[]"
                                                    value="{{ $category->id }}"
                                                    @checked(in_array($category->id, $selectedCategories))
                                                    class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                                                >
                                                {{ $category->name }}
                                            </label>
                                            @foreach ($category->children as $child)
                                                <label class="flex items-center gap-2 text-sm text-neutral-300 hover:text-white cursor-pointer pl-4">
                                                    <input
                                                        type="checkbox"
                                                        name="categories[]"
                                                        value="{{ $child->id }}"
                                                        @checked(in_array($child->id, $selectedCategories))
                                                        class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                                                    >
                                                    {{ $child->name }}
                                                </label>
                                                @foreach ($child->children as $grandchild)
                                                    <label class="flex items-center gap-2 text-sm text-neutral-300 hover:text-white cursor-pointer pl-8">
                                                        <input
                                                            type="checkbox"
                                                            name="categories[]"
                                                            value="{{ $grandchild->id }}"
                                                            @checked(in_array($grandchild->id, $selectedCategories))
                                                            class="rounded border-neutral-600 bg-neutral-700 text-blue-500 focus:ring-blue-500"
                                                        >
                                                        {{ $grandchild->name }}
                                                    </label>
                                                @endforeach
                                            @endforeach
                                        @endforeach
                                    </div>
                                </flux:field>

                                {{-- Tags --}}
                                @php
                                    $selectedTags = old('tags', $product->tags->pluck('id')->toArray());
                                @endphp
                                <flux:field>
                                    <flux:label>{{ __('Tags') }}</flux:label>
                                    <div class="flex flex-wrap gap-2 mt-2 p-3 bg-neutral-800/50 rounded-lg">
                                        @forelse ($tags as $tag)
                                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors
                                                {{ in_array($tag->id, $selectedTags) ? 'bg-blue-600 text-white' : 'bg-neutral-700 text-neutral-300 hover:bg-neutral-600' }}">
                                                <input
                                                    type="checkbox"
                                                    name="tags[]"
                                                    value="{{ $tag->id }}"
                                                    @checked(in_array($tag->id, $selectedTags))
                                                    class="hidden"
                                                >
                                                {{ $tag->name }}
                                            </label>
                                        @empty
                                            <span class="text-sm text-neutral-500">{{ __('Nenhuma tag cadastrada') }}</span>
                                        @endforelse
                                    </div>
                                </flux:field>
                            </div>
                        </div>

                        {{-- Tab: Pricing --}}
                        <div x-show="activeTab === 'pricing'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- Price --}}
                                <flux:input
                                    name="price"
                                    :label="__('Preço (R$)')"
                                    :value="old('price', number_format($product->price_in_reais, 2, '.', ''))"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    required
                                    placeholder="0,00"
                                />

                                {{-- Cost --}}
                                <flux:input
                                    name="cost"
                                    :label="__('Custo (R$)')"
                                    :value="old('cost', $product->cost_in_reais ? number_format($product->cost_in_reais, 2, '.', '') : '')"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    placeholder="Custo interno do produto"
                                />

                                {{-- Sale Section --}}
                                <div class="border-t border-neutral-800 pt-6">
                                    <flux:heading size="sm" class="mb-4">{{ __('Preço Promocional') }}</flux:heading>

                                    <div class="flex flex-col gap-4">
                                        <flux:input
                                            name="sale_price"
                                            :label="__('Preço Promocional (R$)')"
                                            :value="old('sale_price', $product->sale_price_in_reais ? number_format($product->sale_price_in_reais, 2, '.', '') : '')"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Deixe vazio se não houver promoção"
                                        />

                                        <div class="grid grid-cols-2 gap-4">
                                            <flux:input
                                                name="sale_start_at"
                                                :label="__('Início da Promoção')"
                                                :value="old('sale_start_at', $product->sale_start_at?->format('Y-m-d\TH:i'))"
                                                type="datetime-local"
                                            />
                                            <flux:input
                                                name="sale_end_at"
                                                :label="__('Fim da Promoção')"
                                                :value="old('sale_end_at', $product->sale_end_at?->format('Y-m-d\TH:i'))"
                                                type="datetime-local"
                                            />
                                        </div>
                                    </div>
                                </div>

                                @if ($product->isOnSale())
                                    <flux:callout variant="success">
                                        <div class="flex items-center gap-2">
                                            <flux:icon name="tag" class="size-5" />
                                            <span>{{ __('Este produto está em promoção!') }} {{ $product->getDiscountPercentage() }}% de desconto.</span>
                                        </div>
                                    </flux:callout>
                                @endif
                            </div>
                        </div>

                        {{-- Tab: Stock --}}
                        <div x-show="activeTab === 'stock'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- SKU --}}
                                <flux:input
                                    name="sku"
                                    :label="__('SKU')"
                                    :value="old('sku', $product->sku)"
                                    type="text"
                                    placeholder="Código único do produto"
                                />

                                {{-- Stock Quantity --}}
                                <flux:input
                                    name="stock_quantity"
                                    :label="__('Quantidade em Estoque')"
                                    :value="old('stock_quantity', $product->stock_quantity)"
                                    type="number"
                                    min="0"
                                    required
                                />

                                {{-- Manage Stock --}}
                                <flux:field>
                                    <flux:switch
                                        name="manage_stock"
                                        value="1"
                                        :checked="old('manage_stock', $product->manage_stock)"
                                        label="{{ __('Gerenciar estoque') }}"
                                    />
                                    <flux:description>{{ __('Habilita o controle de quantidade em estoque') }}</flux:description>
                                </flux:field>

                                {{-- Allow Backorders --}}
                                <flux:field>
                                    <flux:switch
                                        name="allow_backorders"
                                        value="1"
                                        :checked="old('allow_backorders', $product->allow_backorders)"
                                        label="{{ __('Permitir pedidos em falta') }}"
                                    />
                                    <flux:description>{{ __('Permite vender mesmo com estoque zerado') }}</flux:description>
                                </flux:field>

                                {{-- Stock Status --}}
                                @if ($product->manage_stock)
                                    <div class="border-t border-neutral-800 pt-6">
                                        @if (!$product->isInStock())
                                            <flux:callout variant="danger">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon name="exclamation-triangle" class="size-5" />
                                                    <span>{{ __('Produto sem estoque!') }}</span>
                                                </div>
                                            </flux:callout>
                                        @elseif ($product->stock_quantity <= 5)
                                            <flux:callout variant="warning">
                                                <div class="flex items-center gap-2">
                                                    <flux:icon name="exclamation-triangle" class="size-5" />
                                                    <span>{{ __('Estoque baixo! Apenas :count unidades.', ['count' => $product->stock_quantity]) }}</span>
                                                </div>
                                            </flux:callout>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Tab: Attributes/Variants (only for variable products) --}}
                        <div x-show="activeTab === 'attributes'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            @if ($product->isVariable())
                                <livewire:product-variants :product="$product" />
                            @else
                                <flux:callout variant="info">
                                    {{ __('Salve o produto como "Variável" para gerenciar atributos e variantes.') }}
                                </flux:callout>
                            @endif
                        </div>

                        {{-- Tab: Images --}}
                        <div x-show="activeTab === 'images'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <livewire:product-images :product="$product" />
                        </div>

                        {{-- Tab: Shipping --}}
                        <div x-show="activeTab === 'shipping'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- Weight --}}
                                <flux:input
                                    name="weight"
                                    :label="__('Peso (kg)')"
                                    :value="old('weight', $product->weight)"
                                    type="number"
                                    step="0.001"
                                    min="0"
                                    placeholder="Ex: 0.500"
                                />

                                {{-- Dimensions --}}
                                <div class="grid grid-cols-3 gap-4">
                                    <flux:input
                                        name="length"
                                        :label="__('Comprimento (cm)')"
                                        :value="old('length', $product->length)"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Ex: 20"
                                    />
                                    <flux:input
                                        name="width"
                                        :label="__('Largura (cm)')"
                                        :value="old('width', $product->width)"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Ex: 15"
                                    />
                                    <flux:input
                                        name="height"
                                        :label="__('Altura (cm)')"
                                        :value="old('height', $product->height)"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Ex: 5"
                                    />
                                </div>

                                <flux:callout variant="info">
                                    {{ __('As dimensões e peso são usados para calcular o frete. Deixe em branco se o produto não precisa de entrega física.') }}
                                </flux:callout>
                            </div>
                        </div>

                        {{-- Tab: SEO --}}
                        <div x-show="activeTab === 'seo'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                <flux:input
                                    name="meta_title"
                                    :label="__('Título SEO')"
                                    :value="old('meta_title', $product->meta_title)"
                                    type="text"
                                    placeholder="Título para mecanismos de busca (máx. 60 caracteres)"
                                    maxlength="255"
                                />

                                <flux:textarea
                                    name="meta_description"
                                    :label="__('Descrição SEO')"
                                    :value="old('meta_description', $product->meta_description)"
                                    placeholder="Descrição para mecanismos de busca (máx. 160 caracteres)"
                                    rows="3"
                                    maxlength="500"
                                />

                                {{-- SEO Preview --}}
                                <div class="border-t border-neutral-800 pt-6">
                                    <flux:heading size="sm" class="mb-4">{{ __('Prévia nos Resultados de Busca') }}</flux:heading>
                                    <div class="p-4 bg-white rounded-lg">
                                        <div class="text-blue-600 text-lg hover:underline cursor-pointer">{{ $product->meta_title ?: $product->name }}</div>
                                        <div class="text-green-700 text-sm">{{ url('/produtos/' . $product->slug) }}</div>
                                        <div class="text-gray-600 text-sm mt-1">{{ $product->meta_description ?: $product->short_description ?: 'Descrição do produto...' }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-between pt-4">
                            <div class="flex items-center gap-2">
                                <form method="POST" action="{{ route('admin.products.destroy', $product) }}" onsubmit="return confirm('{{ __('Tem certeza que deseja mover este produto para a lixeira?') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="ghost" type="submit" class="text-red-400 hover:text-red-300">
                                        <flux:icon name="trash" class="size-4 mr-1" />
                                        {{ __('Excluir') }}
                                    </flux:button>
                                </form>
                            </div>
                            <div class="flex items-center gap-3">
                                <a href="{{ route('admin.products.index') }}">
                                    <flux:button variant="ghost">
                                        {{ __('Cancelar') }}
                                    </flux:button>
                                </a>
                                <flux:button variant="primary" type="submit">
                                    {{ __('Salvar Alterações') }}
                                </flux:button>
                            </div>
                        </div>
                    </div>
                </form>

                {{-- Product Info --}}
                <div class="mt-8 text-sm text-neutral-500">
                    <p>{{ __('Criado em') }}: {{ $product->created_at->format('d/m/Y H:i') }}</p>
                    <p>{{ __('Atualizado em') }}: {{ $product->updated_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
        @fluxScripts

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </body>
</html>
