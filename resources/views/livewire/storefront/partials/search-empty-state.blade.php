{{-- Search Empty State --}}
<div class="rounded-lg border border-zinc-200 bg-zinc-50 p-8 text-center sm:p-12 dark:border-zinc-700 dark:bg-zinc-800">
    <flux:icon name="magnifying-glass" class="mx-auto size-10 text-zinc-400 sm:size-12" />

    <h3 class="mt-4 text-base font-medium text-zinc-900 sm:text-lg dark:text-white">
        Nenhum resultado encontrado para "{{ $q }}"
    </h3>

    <p class="mt-2 text-xs text-zinc-600 sm:text-sm dark:text-zinc-400">
        Verifique a ortografia ou tente usar termos diferentes.
    </p>

    {{-- New Search Input --}}
    <div class="mx-auto mt-6 max-w-md">
        <p class="mb-2 text-sm text-zinc-500 dark:text-zinc-400">Tente uma nova busca</p>
        <form action="{{ route('search') }}" method="GET" class="flex gap-2">
            <flux:input type="search" name="q" placeholder="Buscar produtos..." class="flex-1" />
            <flux:button type="submit" variant="primary" icon="magnifying-glass" />
        </form>
    </div>

    {{-- Suggested Products --}}
    @if($this->suggestedProducts->isNotEmpty())
        <div class="mt-10 border-t border-zinc-200 pt-8 dark:border-zinc-700">
            <h4 class="text-sm font-medium text-zinc-900 dark:text-white">Talvez você goste</h4>
            <div class="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-4">
                @foreach($this->suggestedProducts as $product)
                    <x-storefront.product-card :product="$product" wire:key="suggested-{{ $product->id }}" />
                @endforeach
            </div>
        </div>
    @endif

    {{-- Popular Categories --}}
    @if($this->popularCategories->isNotEmpty())
        <div class="mt-8 border-t border-zinc-200 pt-8 dark:border-zinc-700">
            <h4 class="text-sm font-medium text-zinc-900 dark:text-white">Categorias populares</h4>
            <div class="mt-4 flex flex-wrap justify-center gap-2">
                @foreach($this->popularCategories as $category)
                    <a
                        href="{{ route('products.index', ['categoria' => $category->slug]) }}"
                        wire:key="popular-category-{{ $category->id }}"
                        wire:navigate
                        class="inline-flex items-center rounded-full border border-zinc-200 bg-white px-4 py-2 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:border-zinc-600 dark:hover:bg-zinc-700"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </div>
    @endif
</div>
