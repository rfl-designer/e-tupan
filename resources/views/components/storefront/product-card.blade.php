@props([
    'product',
])

@php
    use Illuminate\Support\Number;

    $primaryImage = $product->primaryImage();
    $imageUrl = $primaryImage ? Storage::url($primaryImage->path) : null;
    $isOnSale = $product->isOnSale();
    $discountPercentage = $product->getDiscountPercentage();
@endphp

<article {{ $attributes->merge(['class' => 'group relative']) }}>
    <a href="{{ route('products.show', $product->slug) }}" class="block">
        {{-- Image Container --}}
        <div class="relative aspect-square overflow-hidden rounded-lg bg-zinc-100 dark:bg-zinc-800">
            @if($imageUrl)
                <img
                    src="{{ $imageUrl }}"
                    alt="{{ $product->name }}"
                    class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                    loading="lazy"
                />
            @else
                <div class="flex size-full items-center justify-center">
                    <flux:icon name="photo" class="size-8 text-zinc-300 sm:size-12 dark:text-zinc-600" />
                </div>
            @endif

            {{-- Badges --}}
            <div class="absolute left-1.5 top-1.5 flex flex-col gap-1 sm:left-2 sm:top-2">
                @if($isOnSale && $discountPercentage)
                    <span class="rounded-md bg-red-500 px-1.5 py-0.5 text-[10px] font-semibold text-white sm:px-2 sm:py-1 sm:text-xs">
                        -{{ $discountPercentage }}%
                    </span>
                @endif
                @if(!$product->isInStock())
                    <span class="rounded-md bg-zinc-900 px-1.5 py-0.5 text-[10px] font-semibold text-white sm:px-2 sm:py-1 sm:text-xs">
                        Esgotado
                    </span>
                @endif
            </div>

            {{-- Quick Actions - Hidden on touch devices, visible on hover --}}
            <div class="absolute bottom-2 right-2 hidden gap-1 opacity-0 transition-opacity group-hover:opacity-100 md:flex">
                <flux:button
                    variant="filled"
                    size="sm"
                    icon="shopping-cart"
                    class="bg-white text-zinc-900 shadow-lg hover:bg-zinc-100"
                    aria-label="Adicionar ao carrinho"
                />
            </div>
        </div>

        {{-- Product Info --}}
        <div class="mt-2 space-y-0.5 sm:mt-4 sm:space-y-1">
            {{-- Category --}}
            @if($product->categories->isNotEmpty())
                <p class="truncate text-[10px] text-zinc-500 sm:text-xs dark:text-zinc-400">
                    {{ $product->categories->first()->name }}
                </p>
            @endif

            {{-- Name --}}
            <h3 class="line-clamp-2 text-xs font-medium leading-tight text-zinc-900 group-hover:text-zinc-700 sm:text-sm dark:text-white dark:group-hover:text-zinc-300">
                {{ $product->name }}
            </h3>

            {{-- Price --}}
            <div class="flex flex-wrap items-baseline gap-1 sm:gap-2">
                @if($isOnSale)
                    <span class="text-[10px] text-zinc-500 line-through sm:text-sm dark:text-zinc-400">
                        {{ Number::currency($product->price_in_reais, 'BRL') }}
                    </span>
                    <span class="text-sm font-semibold text-red-600 sm:text-lg dark:text-red-500">
                        {{ Number::currency($product->sale_price_in_reais, 'BRL') }}
                    </span>
                @else
                    <span class="text-sm font-semibold text-zinc-900 sm:text-lg dark:text-white">
                        {{ Number::currency($product->price_in_reais, 'BRL') }}
                    </span>
                @endif
            </div>

            {{-- Installments --}}
            @if($product->getCurrentPrice() >= 10000)
                <p class="hidden text-xs text-zinc-500 sm:block dark:text-zinc-400">
                    em ate 12x de {{ Number::currency($product->current_price_in_reais / 12, 'BRL') }}
                </p>
            @endif
        </div>
    </a>
</article>
