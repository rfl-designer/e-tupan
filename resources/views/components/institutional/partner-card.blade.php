@props([
    'partner' => [],
    'origin' => null,
])

@php
    $name = $partner['name'] ?? '';
    $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode('');
    $ctaPrefix = $origin ? $origin . '-parceiro-' : 'parceiro-';
@endphp

<x-institutional.card {{ $attributes->merge(['class' => 'flex h-full flex-col']) }}>
    <div class="mb-6 flex h-16 items-center">
        @if(!empty($partner['logo']))
            <img src="{{ asset($partner['logo']) }}" alt="Logo {{ $name }}" class="max-h-12 w-auto max-w-[180px] object-contain" loading="lazy" />
        @else
            <div class="flex items-center gap-3" role="img" aria-label="{{ $name }}">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-bg-cream text-sm font-bold uppercase text-primary">{{ $initials }}</span>
                <span class="text-lg font-semibold text-neutral-strong">{{ $name }}</span>
            </div>
        @endif
    </div>

    <span class="mb-2 text-xs font-bold uppercase tracking-wide text-primary">{{ $partner['role'] ?? '' }}</span>
    <h3 class="mb-3 text-lg font-semibold text-neutral-strong">{{ $name }}</h3>
    <p class="mb-6 flex-grow text-sm leading-relaxed text-neutral-medium">{{ $partner['description'] ?? '' }}</p>

    @if(!empty($partner['links']))
        <ul class="mb-6 space-y-2 border-t border-neutral-border pt-4">
            @foreach($partner['links'] as $link)
                <li wire:key="partner-link-{{ \Illuminate\Support\Str::slug($name) }}-{{ $loop->index }}">
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($link['label']) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-medium transition-colors hover:text-primary">
                        <flux:icon name="arrow-top-right-on-square" class="size-4" />
                        {{ $link['label'] }}
                        <span class="sr-only">(abre em nova aba)</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if(!empty($partner['url']))
        <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($name) }}" class="inline-flex items-center justify-center gap-2 self-start rounded-[999px] border-2 border-primary px-5 py-2 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
            Conhecer parceiro
            <flux:icon name="arrow-top-right-on-square" class="size-4" />
            <span class="sr-only">{{ $name }} (abre em nova aba)</span>
        </a>
    @endif
</x-institutional.card>
