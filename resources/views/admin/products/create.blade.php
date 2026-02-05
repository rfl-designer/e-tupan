<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white antialiased dark:bg-linear-to-b dark:from-neutral-950 dark:to-neutral-900">
        <div class="min-h-svh p-6 md:p-10">
            <div class="mx-auto max-w-4xl">
                {{-- Header --}}
                <div class="mb-8 flex items-center gap-4">
                    <a href="{{ route('admin.products.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                        <flux:icon name="arrow-left" class="size-5" />
                    </a>
                    <div>
                        <flux:heading size="xl">{{ __('Novo Produto') }}</flux:heading>
                        <flux:subheading>{{ __('Adicione um novo produto ao catálogo') }}</flux:subheading>
                    </div>
                </div>

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
                <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div x-data="{ activeTab: 'general' }" class="space-y-6">
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
                                    :value="old('name')"
                                    type="text"
                                    required
                                    autofocus
                                    placeholder="Ex: Camiseta Básica Algodão"
                                />

                                {{-- Slug --}}
                                <flux:input
                                    name="slug"
                                    :label="__('Slug')"
                                    :value="old('slug')"
                                    type="text"
                                    placeholder="deixe vazio para gerar automaticamente"
                                />

                                {{-- Type --}}
                                <flux:select name="type" :label="__('Tipo de Produto')" required>
                                    @foreach (\App\Domain\Catalog\Enums\ProductType::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('type', 'simple') === $value)>{{ $label }}</option>
                                    @endforeach
                                </flux:select>

                                {{-- Status --}}
                                <flux:select name="status" :label="__('Status')" required>
                                    @foreach (\App\Domain\Catalog\Enums\ProductStatus::options() as $value => $label)
                                        <option value="{{ $value }}" @selected(old('status', 'draft') === $value)>{{ $label }}</option>
                                    @endforeach
                                </flux:select>

                                {{-- Short Description --}}
                                <flux:textarea
                                    name="short_description"
                                    :label="__('Descrição Curta')"
                                    :value="old('short_description')"
                                    placeholder="Breve descrição do produto (exibida na listagem)"
                                    rows="2"
                                    maxlength="500"
                                />

                                {{-- Description --}}
                                <flux:textarea
                                    name="description"
                                    :label="__('Descrição Completa')"
                                    :value="old('description')"
                                    placeholder="Descrição detalhada do produto"
                                    rows="6"
                                />

                                {{-- Categories --}}
                                <flux:field>
                                    <flux:label>{{ __('Categorias') }}</flux:label>
                                    <div class="grid grid-cols-2 gap-2 mt-2 max-h-48 overflow-y-auto p-3 bg-neutral-800/50 rounded-lg">
                                        @foreach ($categories as $category)
                                            <label class="flex items-center gap-2 text-sm text-neutral-300 hover:text-white cursor-pointer">
                                                <input
                                                    type="checkbox"
                                                    name="categories[]"
                                                    value="{{ $category->id }}"
                                                    @checked(in_array($category->id, old('categories', [])))
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
                                                        @checked(in_array($child->id, old('categories', [])))
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
                                                            @checked(in_array($grandchild->id, old('categories', [])))
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
                                <flux:field>
                                    <flux:label>{{ __('Tags') }}</flux:label>
                                    <div class="flex flex-wrap gap-2 mt-2 p-3 bg-neutral-800/50 rounded-lg">
                                        @forelse ($tags as $tag)
                                            <label class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm cursor-pointer transition-colors
                                                {{ in_array($tag->id, old('tags', [])) ? 'bg-blue-600 text-white' : 'bg-neutral-700 text-neutral-300 hover:bg-neutral-600' }}">
                                                <input
                                                    type="checkbox"
                                                    name="tags[]"
                                                    value="{{ $tag->id }}"
                                                    @checked(in_array($tag->id, old('tags', [])))
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
                                    :value="old('price', '0.00')"
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
                                    :value="old('cost')"
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
                                            :value="old('sale_price')"
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Deixe vazio se não houver promoção"
                                        />

                                        <div class="grid grid-cols-2 gap-4">
                                            <flux:input
                                                name="sale_start_at"
                                                :label="__('Início da Promoção')"
                                                :value="old('sale_start_at')"
                                                type="datetime-local"
                                            />
                                            <flux:input
                                                name="sale_end_at"
                                                :label="__('Fim da Promoção')"
                                                :value="old('sale_end_at')"
                                                type="datetime-local"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Tab: Stock --}}
                        <div x-show="activeTab === 'stock'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- SKU --}}
                                <flux:input
                                    name="sku"
                                    :label="__('SKU')"
                                    :value="old('sku')"
                                    type="text"
                                    placeholder="Código único do produto"
                                />

                                {{-- Stock Quantity --}}
                                <flux:input
                                    name="stock_quantity"
                                    :label="__('Quantidade em Estoque')"
                                    :value="old('stock_quantity', 0)"
                                    type="number"
                                    min="0"
                                    required
                                />

                                {{-- Manage Stock --}}
                                <flux:field>
                                    <flux:switch
                                        name="manage_stock"
                                        value="1"
                                        :checked="old('manage_stock', true)"
                                        label="{{ __('Gerenciar estoque') }}"
                                    />
                                    <flux:description>{{ __('Habilita o controle de quantidade em estoque') }}</flux:description>
                                </flux:field>

                                {{-- Allow Backorders --}}
                                <flux:field>
                                    <flux:switch
                                        name="allow_backorders"
                                        value="1"
                                        :checked="old('allow_backorders', false)"
                                        label="{{ __('Permitir pedidos em falta') }}"
                                    />
                                    <flux:description>{{ __('Permite vender mesmo com estoque zerado') }}</flux:description>
                                </flux:field>
                            </div>
                        </div>

                        {{-- Tab: Shipping --}}
                        <div x-show="activeTab === 'shipping'" x-cloak class="rounded-lg border border-neutral-800 bg-neutral-900 p-6">
                            <div class="flex flex-col gap-6">
                                {{-- Weight --}}
                                <flux:input
                                    name="weight"
                                    :label="__('Peso (kg)')"
                                    :value="old('weight')"
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
                                        :value="old('length')"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Ex: 20"
                                    />
                                    <flux:input
                                        name="width"
                                        :label="__('Largura (cm)')"
                                        :value="old('width')"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Ex: 15"
                                    />
                                    <flux:input
                                        name="height"
                                        :label="__('Altura (cm)')"
                                        :value="old('height')"
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
                                    :value="old('meta_title')"
                                    type="text"
                                    placeholder="Título para mecanismos de busca (máx. 60 caracteres)"
                                    maxlength="255"
                                />

                                <flux:textarea
                                    name="meta_description"
                                    :label="__('Descrição SEO')"
                                    :value="old('meta_description')"
                                    placeholder="Descrição para mecanismos de busca (máx. 160 caracteres)"
                                    rows="3"
                                    maxlength="500"
                                />

                                {{-- SEO Preview --}}
                                <div class="border-t border-neutral-800 pt-6">
                                    <flux:heading size="sm" class="mb-4">{{ __('Prévia nos Resultados de Busca') }}</flux:heading>
                                    <div class="p-4 bg-white rounded-lg">
                                        <div class="text-blue-600 text-lg hover:underline cursor-pointer" x-text="$refs.metaTitle?.value || $refs.productName?.value || 'Nome do Produto'">Nome do Produto</div>
                                        <div class="text-green-700 text-sm">{{ url('/produtos') }}/nome-do-produto</div>
                                        <div class="text-gray-600 text-sm mt-1" x-text="$refs.metaDescription?.value || $refs.shortDescription?.value || 'Descrição do produto aparecerá aqui...'">Descrição do produto aparecerá aqui...</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex items-center justify-end gap-3 pt-4">
                            <a href="{{ route('admin.products.index') }}">
                                <flux:button variant="ghost">
                                    {{ __('Cancelar') }}
                                </flux:button>
                            </a>
                            <flux:button variant="primary" type="submit">
                                {{ __('Criar Produto') }}
                            </flux:button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        @fluxScripts

        <style>
            [x-cloak] { display: none !important; }
        </style>
    </body>
</html>
