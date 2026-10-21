@props([
    'partner' => [],
    'origin' => null,
])

@php
    $name = $partner['name'] ?? '';
    $ctaPrefix = $origin ? $origin . '-parceiro-' : 'parceiro-';
    $logos = array_values(array_filter((array) ($partner['logo'] ?? [])));
    $logoAlt = count($logos) > 1 ? 'Logos ' . $name : 'Logo ' . $name;
@endphp

<x-institutional.card :hover-effect="false" {{ $attributes->merge(['class' => 'flex h-full min-w-0 flex-col']) }}>
    <div class="mb-6 flex h-14 items-center justify-start">
        @if($logos !== [])
            <div class="flex h-14 max-w-full items-center gap-4" role="img" aria-label="{{ $logoAlt }}">
                @foreach($logos as $logo)
                    <img src="{{ asset($logo) }}" alt="" wire:key="partner-logo-{{ \Illuminate\Support\Str::slug($name) }}-{{ $loop->index }}" @class([
                        'w-auto object-contain object-left',
                        'h-14 max-w-[160px]' => count($logos) === 1,
                        'h-8 max-w-[140px]' => count($logos) > 1,
                    ]) loading="lazy" />
                @endforeach
            </div>
        @else
            <p class="text-xl font-semibold text-neutral-strong" aria-hidden="true">{{ $name }}</p>
        @endif
    </div>

    <span class="mb-2 text-xs font-bold uppercase tracking-wide text-primary-strong">{{ $partner['role'] ?? '' }}</span>
    <h3 class="mb-3 text-lg font-semibold text-neutral-strong">{{ $name }}</h3>
    <p class="mb-6 flex-grow text-sm leading-relaxed text-neutral-medium">{{ $partner['description'] ?? '' }}</p>

    @if(!empty($partner['links']))
        <ul class="mb-6 space-y-2 border-t border-neutral-border pt-4">
            @foreach($partner['links'] as $link)
                <li wire:key="partner-link-{{ \Illuminate\Support\Str::slug($name) }}-{{ $loop->index }}">
                    <a href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($link['label']) }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-neutral-medium transition-colors hover:text-primary-strong">
                        <flux:icon name="arrow-top-right-on-square" class="size-4" />
                        {{ $link['label'] }}
                        <span class="sr-only">(abre em nova aba)</span>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    @if(!empty($partner['internal_link']))
        <a href="{{ $partner['internal_link']['href'] }}" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($name) }}-{{ $partner['internal_link']['cta'] ?? 'contato' }}" class="inline-flex items-center justify-center gap-2 self-start rounded-[999px] border-2 border-primary-strong px-5 py-2 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
            {{ $partner['internal_link']['label'] }}
            @if(($partner['internal_link']['icon'] ?? 'arrow-down') !== null)
                <flux:icon name="{{ $partner['internal_link']['icon'] ?? 'arrow-down' }}" class="size-4" />
            @endif
        </a>
    @endif

    @if(!empty($partner['url']))
        <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($name) }}" class="inline-flex items-center justify-center gap-2 self-start rounded-[999px] border-2 border-primary-strong px-5 py-2 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
            Conhecer parceiro
            <flux:icon name="arrow-top-right-on-square" class="size-4" />
            <span class="sr-only">{{ $name }} (abre em nova aba)</span>
        </a>
    @endif
</x-institutional.card>
