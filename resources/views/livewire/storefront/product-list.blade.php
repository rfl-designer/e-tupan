<div>
    {{-- Page Header --}}
    <div class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
        <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
            <h1 class="text-2xl font-bold text-zinc-900 sm:text-3xl dark:text-white">
                @if($this->hasSearchQuery())
                    Resultados para "{{ $q }}"
                @else
                    Produtos
                @endif
            </h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                @if($this->totalProducts > 0)
                    {{ $this->totalProducts }} {{ $this->totalProducts === 1 ? 'produto' : 'produtos' }}
                @else
                    Nenhum produto disponivel
                @endif
            </p>
        </div>
    </div>

    {{-- Products Section --}}
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 sm:py-12 lg:px-8">
        <div class="lg:grid lg:grid-cols-4 lg:gap-8">
            {{-- Sidebar Filters --}}
            <aside class="mb-6 lg:col-span-1 lg:mb-0">
                {{-- Mobile Filter Toggle --}}
                <div class="lg:hidden">
                    @php
                        $activeAttributeCount = collect($atributos)->filter(fn($v) => is_array($v) && !empty($v))->count();
                        $activeFilterCount = ($categoria ? 1 : 0) + ($this->hasPriceFilter() ? 1 : 0) + ($promocao ? 1 : 0) + $activeAttributeCount;
                    @endphp
                    <flux:button x-data x-on:click="$dispatch('open-filters')" variant="ghost" class="w-full justify-between">
                        <span class="flex items-center gap-2">
                            <flux:icon name="adjustments-horizontal" class="size-5" />
                            Filtros
                        </span>
                        @if($activeFilterCount > 0)
                            <flux:badge size="sm">{{ $activeFilterCount }}</flux:badge>
                        @endif
                    </flux:button>
                </div>

                {{-- Desktop Filter Sidebar --}}
                <div class="hidden space-y-6 lg:block">
                    @include('livewire.storefront.partials.category-filter')

                    <flux:separator />

                    @include('livewire.storefront.partials.price-filter')

                    <flux:separator />

                    @include('livewire.storefront.partials.promo-filter')

                    @if($this->availableAttributes->isNotEmpty())
                        <flux:separator />

                        @include('livewire.storefront.partials.attribute-filter')
                    @endif
                </div>

                {{-- Mobile Filter Drawer --}}
                <div x-data="{ open: false }" x-on:open-filters.window="open = true" class="lg:hidden">
                    <template x-teleport="body">
                        <div x-show="open" x-cloak class="fixed inset-0 z-50 overflow-hidden">
                            <div x-show="open" x-transition:enter="ease-in-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in-out duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="absolute inset-0 bg-zinc-900/50" x-on:click="open = false"></div>

                            <div x-show="open" x-transition:enter="transform transition ease-in-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transform transition ease-in-out duration-300" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="absolute inset-y-0 left-0 flex max-w-full">
                                <div class="w-screen max-w-xs">
                                    <div class="flex h-full flex-col bg-white shadow-xl dark:bg-zinc-900">
                                        <div class="flex items-center justify-between border-b border-zinc-200 px-4 py-4 dark:border-zinc-700">
                                            <h2 class="text-lg font-medium text-zinc-900 dark:text-white">Filtros</h2>
                                            <flux:button x-on:click="open = false" variant="ghost" size="sm" icon="x-mark" />
                                        </div>
                                        <div class="flex-1 space-y-6 overflow-y-auto px-4 py-6">
                                            @include('livewire.storefront.partials.category-filter')

                                            <flux:separator />

                                            @include('livewire.storefront.partials.price-filter')

                                            <flux:separator />

                                            @include('livewire.storefront.partials.promo-filter')

                                            @if($this->availableAttributes->isNotEmpty())
                                                <flux:separator />

                                                @include('livewire.storefront.partials.attribute-filter')
                                            @endif
                                        </div>
                                        <div class="border-t border-zinc-200 px-4 py-4 dark:border-zinc-700">
                                            <flux:button x-on:click="open = false" variant="primary" class="w-full">
                                                Ver {{ $this->totalProducts }} {{ $this->totalProducts === 1 ? 'produto' : 'produtos' }}
                                            </flux:button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </aside>

            {{-- Products Content --}}
            <div class="lg:col-span-3" id="products-grid">
                {{-- Loading Overlay --}}
                <div wire:loading.flex class="fixed inset-0 z-40 items-center justify-center bg-white/60 dark:bg-zinc-900/60">
                    <div class="flex flex-col items-center gap-3">
                        <flux:icon name="arrow-path" class="size-8 animate-spin text-zinc-600 dark:text-zinc-400" />
                        <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Carregando...</span>
                    </div>
                </div>

                {{-- Sorting and Active Filters Bar --}}
                <div class="mb-4 flex flex-wrap items-center justify-between gap-4">
                    {{-- Sorting Dropdown --}}
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Ordenar por</span>
                        <flux:select wire:model.live="ordenar" size="sm" class="w-44">
                            <flux:select.option value="recentes">Mais recentes</flux:select.option>
                            <flux:select.option value="preco-asc">Menor preço</flux:select.option>
                            <flux:select.option value="preco-desc">Maior preço</flux:select.option>
                            <flux:select.option value="nome-asc">Nome A-Z</flux:select.option>
                            <flux:select.option value="mais-vendidos">Mais vendidos</flux:select.option>
                        </flux:select>
                    </div>

                    {{-- Results count for mobile --}}
                    <div class="text-sm text-zinc-500 lg:hidden dark:text-zinc-400">
                        {{ $this->totalProducts }} {{ $this->totalProducts === 1 ? 'resultado' : 'resultados' }}
                    </div>
                </div>

                {{-- Active Filters --}}
                @if($categoria || $this->hasPriceFilter() || $this->hasAttributeFilter() || $promocao)
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">Filtros ativos:</span>

                        {{-- Promo Filter Badge --}}
                        @if($promocao)
                            <flux:badge variant="outline" class="gap-1 border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
                                <flux:icon name="tag" class="size-3" />
                                Ofertas
                                <button wire:click="clearPromoFilter" class="ml-1 hover:text-red-900 dark:hover:text-red-300">
                                    <flux:icon name="x-mark" class="size-3" />
                                </button>
                            </flux:badge>
                        @endif

                        {{-- Category Filter Badge --}}
                        @if($categoria)
                            @php
                                $activeCategory = $this->categories->flatten(1)->first(fn($c) => $c->slug === $categoria) ?? $this->categories->flatMap->children->first(fn($c) => $c->slug === $categoria);
                            @endphp
                            @if($activeCategory)
                                <flux:badge variant="outline" class="gap-1">
                                    {{ $activeCategory->name }}
                                    <button wire:click="clearCategory" class="ml-1 hover:text-red-500">
                                        <flux:icon name="x-mark" class="size-3" />
                                    </button>
                                </flux:badge>
                            @endif
                        @endif

                        {{-- Price Filter Badge --}}
                        @if($this->hasPriceFilter())
                            <flux:badge variant="outline" class="gap-1">
                                @if($precoMin && $precoMax)
                                    R$ {{ number_format($precoMin, 0, ',', '.') }} - R$ {{ number_format($precoMax, 0, ',', '.') }}
                                @elseif($precoMin)
                                    A partir de R$ {{ number_format($precoMin, 0, ',', '.') }}
                                @elseif($precoMax)
                                    Ate R$ {{ number_format($precoMax, 0, ',', '.') }}
                                @endif
                                <button wire:click="clearPriceFilter" class="ml-1 hover:text-red-500">
                                    <flux:icon name="x-mark" class="size-3" />
                                </button>
                            </flux:badge>
                        @endif

                        {{-- Attribute Filter Badges --}}
                        @foreach($atributos as $attributeSlug => $valueIds)
                            @if(is_array($valueIds) && !empty($valueIds))
                                @php
                                    $attribute = $this->availableAttributes->firstWhere('slug', $attributeSlug);
                                    $selectedValues = $attribute?->values->whereIn('id', $valueIds);
                                @endphp
                                @if($attribute && $selectedValues && $selectedValues->isNotEmpty())
                                    <flux:badge variant="outline" class="gap-1">
                                        {{ $attribute->name }}: {{ $selectedValues->pluck('value')->join(', ') }}
                                        <button wire:click="clearAttributeFilter('{{ $attributeSlug }}')" class="ml-1 hover:text-red-500">
                                            <flux:icon name="x-mark" class="size-3" />
                                        </button>
                                    </flux:badge>
                                @endif
                            @endif
                        @endforeach

                        {{-- Clear All Button --}}
                        <button wire:click="clearAllFilters" class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200">
                            Limpar todos
                        </button>
                    </div>
                @endif

                @if($products->isNotEmpty())
                    {{-- Products Grid --}}
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3" wire:loading.class="opacity-50">
                        @foreach($products as $product)
                            <x-storefront.product-card :product="$product" wire:key="product-{{ $product->id }}" />
                        @endforeach
                    </div>

                    {{-- Pagination --}}
                    @if($products->hasPages())
                        <div class="mt-8 sm:mt-12">
                            {{ $products->links(data: ['scrollTo' => '#products-grid']) }}
                        </div>
                    @endif
                @else
                    {{-- Empty State --}}
                    @php
                        $hasAnyFilter = $categoria || $this->hasPriceFilter() || $this->hasAttributeFilter() || $promocao;
                    @endphp

                    @if($this->hasSearchQuery())
                        {{-- Search Empty State with suggestions --}}
                        @include('livewire.storefront.partials.search-empty-state')
                    @else
                        {{-- Generic Empty State --}}
                        <div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center sm:p-12 dark:border-zinc-700 dark:bg-zinc-800">
                            <flux:icon name="shopping-bag" class="mx-auto size-10 text-zinc-400 sm:size-12" />
                            <h3 class="mt-4 text-base font-medium text-zinc-900 sm:text-lg dark:text-white">
                                Nenhum produto encontrado
                            </h3>
                            <p class="mt-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
                                @if($hasAnyFilter)
                                    Nenhum produto encontrado com os filtros selecionados.
                                @else
                                    Em breve teremos novidades para voce!
                                @endif
                            </p>
                            <div class="mt-6">
                                @if($hasAnyFilter)
                                    <flux:button wire:click="clearAllFilters" variant="primary">
                                        Limpar filtros
                                    </flux:button>
                                @else
                                    <flux:button href="{{ route('home') }}" variant="primary">
                                        Voltar para o inicio
                                    </flux:button>
                                @endif
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
