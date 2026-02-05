<div>
    {{-- Breadcrumbs --}}
    <nav class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/50">
        <div class="mx-auto max-w-7xl px-4 py-3 sm:px-6 lg:px-8">
            <ol class="flex flex-wrap items-center gap-1 text-sm">
                @foreach($this->breadcrumbs as $index => $breadcrumb)
                    <li class="flex items-center">
                        @if($index > 0)
                            <flux:icon name="chevron-right" class="mx-1 size-4 text-zinc-400" />
                        @endif
                        @if($breadcrumb['url'])
                            <a href="{{ $breadcrumb['url'] }}" class="text-zinc-600 transition-colors hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-white">
                                {{ $breadcrumb['name'] }}
                            </a>
                        @else
                            <span class="font-medium text-zinc-900 dark:text-white">
                                {{ $breadcrumb['name'] }}
                            </span>
                        @endif
                    </li>
                @endforeach
            </ol>
        </div>
    </nav>

    {{-- Product Content --}}
    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
        <div class="lg:grid lg:grid-cols-2 lg:gap-x-8">
            {{-- Product Gallery (US-02, US-04) --}}
            @php
                $images = $this->currentImages;
                $hasImages = $images->isNotEmpty();
            @endphp

            @if($hasImages)
                <div x-data="{
                    currentImage: 0,
                    images: {{ Js::from($images->map(fn($img) => [
                        'url' => Storage::url($img->path),
                        'alt' => $img->alt_text ?? $product->name,
                    ])) }},
                    lightboxOpen: false,
                    next() {
                        this.currentImage = (this.currentImage + 1) % this.images.length;
                    },
                    prev() {
                        this.currentImage = (this.currentImage - 1 + this.images.length) % this.images.length;
                    },
                    goTo(index) {
                        this.currentImage = index;
                    }
                }" class="space-y-4">
                    {{-- Main Image --}}
                    <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                        <template x-for="(image, index) in images" :key="index">
                            <img
                                x-show="currentImage === index"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                :src="image.url"
                                :alt="image.alt"
                                class="size-full cursor-zoom-in object-cover"
                                loading="lazy"
                                @click="lightboxOpen = true"
                            />
                        </template>

                        {{-- Navigation Arrows --}}
                        <template x-if="images.length > 1">
                            <div>
                                <button
                                    type="button"
                                    @click="prev()"
                                    class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-zinc-800 shadow-lg transition-colors hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    aria-label="Imagem anterior"
                                >
                                    <flux:icon name="chevron-left" class="size-5" />
                                </button>
                                <button
                                    type="button"
                                    @click="next()"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-2 text-zinc-800 shadow-lg transition-colors hover:bg-white dark:bg-zinc-800/80 dark:text-zinc-200 dark:hover:bg-zinc-800"
                                    aria-label="Proxima imagem"
                                >
                                    <flux:icon name="chevron-right" class="size-5" />
                                </button>
                            </div>
                        </template>

                        {{-- Image Counter --}}
                        <div class="absolute bottom-2 right-2 rounded-full bg-black/50 px-2 py-1 text-xs text-white">
                            <span x-text="currentImage + 1"></span> / <span x-text="images.length"></span>
                        </div>
                    </div>

                    {{-- Thumbnails --}}
                    <div class="grid grid-cols-4 gap-2 sm:grid-cols-5 sm:gap-3">
                        <template x-for="(image, index) in images" :key="'thumb-' + index">
                            <button
                                type="button"
                                @click="goTo(index)"
                                :class="{
                                    'ring-2 ring-zinc-900 dark:ring-white': currentImage === index,
                                    'ring-1 ring-zinc-200 dark:ring-zinc-700': currentImage !== index
                                }"
                                class="aspect-square overflow-hidden rounded-md transition-all hover:opacity-80"
                            >
                                <img
                                    :src="image.url"
                                    :alt="image.alt"
                                    class="size-full object-cover"
                                    loading="lazy"
                                />
                            </button>
                        </template>
                    </div>

                    {{-- Lightbox Modal --}}
                    <flux:modal x-model="lightboxOpen" variant="bare" class="max-w-5xl">
                        <div class="relative">
                            <img
                                :src="images[currentImage]?.url"
                                :alt="images[currentImage]?.alt"
                                class="max-h-[80vh] w-full object-contain"
                            />

                            {{-- Lightbox Navigation --}}
                            <template x-if="images.length > 1">
                                <div>
                                    <button
                                        type="button"
                                        @click="prev()"
                                        class="absolute left-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-3 text-zinc-800 shadow-lg transition-colors hover:bg-white"
                                        aria-label="Imagem anterior"
                                    >
                                        <flux:icon name="chevron-left" class="size-6" />
                                    </button>
                                    <button
                                        type="button"
                                        @click="next()"
                                        class="absolute right-2 top-1/2 -translate-y-1/2 rounded-full bg-white/80 p-3 text-zinc-800 shadow-lg transition-colors hover:bg-white"
                                        aria-label="Proxima imagem"
                                    >
                                        <flux:icon name="chevron-right" class="size-6" />
                                    </button>
                                </div>
                            </template>

                            {{-- Close Button --}}
                            <button
                                type="button"
                                @click="lightboxOpen = false"
                                class="absolute right-2 top-2 rounded-full bg-white/80 p-2 text-zinc-800 shadow-lg transition-colors hover:bg-white"
                                aria-label="Fechar"
                            >
                                <flux:icon name="x-mark" class="size-5" />
                            </button>
                        </div>
                    </flux:modal>
                </div>
            @else
                {{-- Placeholder when no images --}}
                <div class="aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
                    <div class="flex size-full items-center justify-center">
                        <flux:icon name="photo" class="size-24 text-zinc-300 dark:text-zinc-600" />
                    </div>
                </div>
            @endif

            {{-- Product Info --}}
            <div class="mt-8 lg:mt-0">
                {{-- Product Title --}}
                <h1 class="text-2xl font-bold tracking-tight text-zinc-900 sm:text-3xl dark:text-white">
                    {{ $product->name }}
                </h1>

                {{-- Short Description --}}
                @if($product->short_description)
                    <p class="mt-4 text-base text-zinc-600 dark:text-zinc-400">
                        {{ $product->short_description }}
                    </p>
                @endif

                {{-- Price Section (US-03) --}}
                <div class="mt-6">
                    @php
                        $isOnSale = $product->isOnSale() && $selectedVariantId === null;
                        $discountPercentage = $product->getDiscountPercentage();
                        $installments = $this->installments;
                        $currentPriceInReais = $this->currentPrice / 100;
                    @endphp
                    <div class="flex items-baseline gap-3">
                        @if($isOnSale)
                            <span class="text-lg text-zinc-500 line-through dark:text-zinc-400">
                                {{ Number::currency($product->price_in_reais, 'BRL') }}
                            </span>
                            <span class="text-3xl font-bold text-red-600 dark:text-red-500">
                                {{ Number::currency($product->sale_price_in_reais, 'BRL') }}
                            </span>
                            @if($discountPercentage)
                                <span class="rounded-md bg-red-100 px-2 py-1 text-sm font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                                    -{{ $discountPercentage }}%
                                </span>
                            @endif
                        @else
                            <span class="text-3xl font-bold text-zinc-900 dark:text-white">
                                {{ Number::currency($currentPriceInReais, 'BRL') }}
                            </span>
                        @endif
                    </div>

                    {{-- Installments (US-03) --}}
                    @if($installments['max_interest_free'] || $installments['max_with_interest'])
                        <div class="mt-2 space-y-1">
                            @if($installments['max_interest_free'])
                                <p class="text-sm text-green-600 dark:text-green-400">
                                    <span class="font-medium">{{ $installments['max_interest_free']['installments'] }}x</span>
                                    de {{ Number::currency($installments['max_interest_free']['value'] / 100, 'BRL') }}
                                    <span class="font-medium">sem juros</span>
                                </p>
                            @endif
                            @if($installments['max_with_interest'])
                                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                    ou {{ $installments['max_with_interest']['installments'] }}x
                                    de {{ Number::currency($installments['max_with_interest']['value'] / 100, 'BRL') }}
                                    com juros
                                </p>
                            @endif
                        </div>
                    @endif
                </div>

                {{-- Variant Selectors (US-04) --}}
                @if($this->variantAttributes->isNotEmpty())
                    <div class="mt-6 space-y-4">
                        @foreach($this->variantAttributes as $attrData)
                            @php
                                $attribute = $attrData['attribute'];
                                $values = $attrData['values'];
                                $isColor = $attribute->isColor();
                            @endphp
                            <div class="variant-selector">
                                <label class="mb-2 block text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ $attribute->name }}
                                </label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($values as $valueData)
                                        @php
                                            $attrValue = $valueData['value'];
                                            $inStock = $valueData['in_stock'];
                                            $variantId = $valueData['variant_id'];
                                            $isSelected = $selectedVariantId === $variantId;
                                        @endphp
                                        @if($isColor && $attrValue->hasColor())
                                            {{-- Color Swatch --}}
                                            <button
                                                type="button"
                                                wire:click="selectVariant({{ $variantId }})"
                                                @class([
                                                    'relative size-10 rounded-full border-2 transition-all',
                                                    'ring-2 ring-zinc-900 ring-offset-2 dark:ring-white' => $isSelected,
                                                    'border-zinc-300 hover:border-zinc-400 dark:border-zinc-600' => !$isSelected,
                                                    'opacity-50 cursor-not-allowed' => !$inStock,
                                                ])
                                                style="background-color: {{ $attrValue->color_hex }}"
                                                title="{{ $attrValue->value }}{{ !$inStock ? ' - Esgotado' : '' }}"
                                                @if(!$inStock) disabled @endif
                                            >
                                                @if(!$inStock)
                                                    <span class="absolute inset-0 flex items-center justify-center">
                                                        <span class="h-px w-full rotate-45 bg-zinc-500"></span>
                                                    </span>
                                                @endif
                                            </button>
                                        @else
                                            {{-- Size/Text Button --}}
                                            <button
                                                type="button"
                                                wire:click="selectVariant({{ $variantId }})"
                                                @class([
                                                    'min-w-12 rounded-md border px-3 py-2 text-sm font-medium transition-all',
                                                    'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-zinc-900' => $isSelected,
                                                    'border-zinc-300 bg-white text-zinc-900 hover:border-zinc-400 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white dark:hover:border-zinc-500' => !$isSelected && $inStock,
                                                    'border-zinc-200 bg-zinc-100 text-zinc-400 line-through cursor-not-allowed dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-600' => !$inStock,
                                                ])
                                                @if(!$inStock) disabled @endif
                                            >
                                                {{ $attrValue->value }}
                                            </button>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- SKU --}}
                @if($product->sku)
                    <div class="mt-4">
                        <span class="text-sm text-zinc-500 dark:text-zinc-400">
                            SKU: <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $product->sku }}</span>
                        </span>
                    </div>
                @endif

                {{-- Stock Status (US-05) --}}
                <div class="mt-4">
                    @if($this->isCurrentlyInStock)
                        @if($this->isCurrentlyLowStock)
                            <div class="flex items-center gap-2">
                                <flux:icon name="exclamation-triangle" class="size-5 text-amber-500" variant="solid" />
                                <span class="text-sm font-medium text-amber-700 dark:text-amber-400">Ultimas unidades</span>
                            </div>
                        @else
                            <div class="flex items-center gap-2">
                                <flux:icon name="check-circle" class="size-5 text-green-500" variant="solid" />
                                <span class="text-sm font-medium text-green-700 dark:text-green-400">Em estoque</span>
                            </div>
                        @endif
                    @else
                        <div class="flex items-center gap-2">
                            <flux:icon name="x-circle" class="size-5 text-red-500" variant="solid" />
                            <span class="text-sm font-medium text-red-700 dark:text-red-400">Esgotado</span>
                        </div>
                    @endif
                </div>

                {{-- Add to Cart Section (US-06) --}}
                <div class="mt-8 space-y-4">
                    {{-- Error Message --}}
                    @if($cartErrorMessage)
                        <div>
                            <flux:callout variant="danger" icon="exclamation-triangle">
                                {{ $cartErrorMessage }}
                            </flux:callout>
                        </div>
                    @endif

                    {{-- Quantity + Add to Cart Row --}}
                    <div class="flex items-end gap-4">
                        {{-- Quantity Selector --}}
                        <div class="shrink-0">
                            <label class="mb-2 block text-sm font-medium text-zinc-700 dark:text-zinc-300">
                                Quantidade
                            </label>
                            <div class="flex items-center gap-1">
                                <flux:button
                                    wire:click="decrementQuantity"
                                    variant="outline"
                                    size="sm"
                                    icon="minus"
                                    :disabled="$quantity <= 1"
                                    aria-label="Diminuir quantidade"
                                />

                                <flux:input
                                    wire:model.live.debounce.300ms="quantity"
                                    type="number"
                                    min="1"
                                    :max="$maxQuantity"
                                    class="w-16 text-center"
                                    inputmode="numeric"
                                />

                                <flux:button
                                    wire:click="incrementQuantity"
                                    variant="outline"
                                    size="sm"
                                    icon="plus"
                                    :disabled="$quantity >= $maxQuantity"
                                    aria-label="Aumentar quantidade"
                                />
                            </div>
                        </div>

                        {{-- Add to Cart Button --}}
                        <div class="flex-1">
                            @if($this->mustSelectVariant)
                                <flux:button variant="primary" class="w-full py-3 text-base" icon="shopping-cart" disabled>
                                    Selecione uma opcao
                                </flux:button>
                            @else
                                <flux:button
                                    wire:click="addToCart"
                                    variant="primary"
                                    class="w-full py-3 text-base"
                                    icon="shopping-cart"
                                    :disabled="!$this->canAddToCart"
                                >
                                    Adicionar ao Carrinho
                                </flux:button>
                            @endif
                        </div>
                    </div>

                    {{-- Stock Warning --}}
                    @if($maxQuantity > 0 && $maxQuantity < 10)
                        <p class="text-sm text-amber-600 dark:text-amber-400">
                            Apenas {{ $maxQuantity }} disponiveis
                        </p>
                    @elseif($maxQuantity === 0)
                        <p class="text-sm text-red-600 dark:text-red-400">
                            Produto esgotado
                        </p>
                    @endif
                </div>

                {{-- Cart Success Modal --}}
                <flux:modal wire:model="showCartModal" class="md:w-96">
                    <div class="space-y-6">
                        <div class="text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-green-100 dark:bg-green-900">
                                <flux:icon name="check" class="h-6 w-6 text-green-600 dark:text-green-400" />
                            </div>
                            <flux:heading size="lg" class="mt-4">
                                Produto adicionado!
                            </flux:heading>
                            <flux:text class="mt-2">
                                {{ $addedItemName }}
                            </flux:text>
                            @if($addedItemPrice)
                                <p class="mt-1 text-lg font-semibold text-zinc-900 dark:text-white">
                                    {{ Number::currency($addedItemPrice / 100, 'BRL') }}
                                </p>
                            @endif
                        </div>

                        <div class="flex flex-col gap-2">
                            <flux:button wire:click="goToCart" variant="primary" class="w-full" icon="shopping-cart">
                                Ver Carrinho
                            </flux:button>
                            <flux:button wire:click="closeCartModal" variant="ghost" class="w-full">
                                Continuar Comprando
                            </flux:button>
                        </div>
                    </div>
                </flux:modal>

                {{-- Categories Links --}}
                @if($product->categories->isNotEmpty())
                    <div class="mt-6 border-t border-zinc-200 pt-6 dark:border-zinc-700">
                        <span class="text-sm font-medium text-zinc-900 dark:text-white">Categorias:</span>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach($product->categories as $category)
                                <a href="{{ route('products.index', ['categoria' => $category->slug]) }}"
                                   class="rounded-full bg-zinc-100 px-3 py-1 text-sm text-zinc-700 transition-colors hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- Product Description Section --}}
        @if($product->description)
            <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Descricao</h2>
                <div class="prose prose-zinc mt-4 max-w-none dark:prose-invert">
                    {!! nl2br(e($product->description)) !!}
                </div>
            </div>
        @endif

        {{-- Related Products Section (US-08) --}}
        @if($this->relatedProducts->isNotEmpty())
            <div class="mt-12 border-t border-zinc-200 pt-8 dark:border-zinc-700">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">Produtos Relacionados</h2>
                <div class="mt-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach($this->relatedProducts as $relatedProduct)
                        <x-storefront.product-card :product="$relatedProduct" wire:key="related-{{ $relatedProduct->id }}" />
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- Mobile Fixed Action Bar (US-07) --}}
    <div class="fixed bottom-0 left-0 right-0 z-40 border-t border-zinc-200 bg-white p-4 shadow-lg lg:hidden dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center gap-3">
            {{-- Price --}}
            <div class="shrink-0">
                <p class="text-lg font-bold text-zinc-900 dark:text-white">
                    {{ Number::currency($this->currentPrice / 100, 'BRL') }}
                </p>
            </div>

            {{-- Add to Cart Button --}}
            <div class="flex-1">
                @if($this->mustSelectVariant)
                    <flux:button variant="primary" class="w-full" icon="shopping-cart" disabled size="sm">
                        Selecione uma opcao
                    </flux:button>
                @else
                    <flux:button
                        wire:click="addToCart"
                        variant="primary"
                        class="w-full"
                        icon="shopping-cart"
                        size="sm"
                        :disabled="!$this->canAddToCart"
                    >
                        Adicionar ao Carrinho
                    </flux:button>
                @endif
            </div>
        </div>
    </div>

    {{-- Spacer for fixed bottom bar on mobile --}}
    <div class="h-20 lg:hidden"></div>
</div>
