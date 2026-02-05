@props([
    'category',
])

@php
    $imageUrl = $category->image ? Storage::url($category->image) : null;
    $productCount = $category->products()->active()->count();
@endphp

<a
    href="{{ route('home') }}?categoria={{ $category->slug }}"
    {{ $attributes->merge(['class' => 'group relative block overflow-hidden rounded-xl']) }}
>
    {{-- Background Image --}}
    <div class="aspect-[4/3] bg-zinc-200 dark:bg-zinc-700">
        @if($imageUrl)
            <img
                src="{{ $imageUrl }}"
                alt="{{ $category->name }}"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-105"
                loading="lazy"
            />
        @else
            <div class="flex size-full items-center justify-center">
                <flux:icon name="squares-2x2" class="size-12 text-zinc-400 dark:text-zinc-500" />
            </div>
        @endif
    </div>

    {{-- Overlay --}}
    <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>

    {{-- Content --}}
    <div class="absolute inset-x-0 bottom-0 p-4">
        <h3 class="text-lg font-semibold text-white">
            {{ $category->name }}
        </h3>
        @if($productCount > 0)
            <p class="mt-1 text-sm text-zinc-300">
                {{ $productCount }} {{ $productCount === 1 ? 'produto' : 'produtos' }}
            </p>
        @endif
    </div>

    {{-- Hover Effect --}}
    <div class="absolute inset-0 border-2 border-transparent transition-colors group-hover:border-white/30 rounded-xl"></div>
</a>
