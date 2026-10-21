@props([
    'partner' => [],
    'origin' => null,
])

@php
    $name = $partner['name'] ?? '';
    $initials = collect(preg_split('/\s+/', $name))->filter()->take(2)->map(fn (string $word): string => mb_substr($word, 0, 1))->implode('');
    $ctaPrefix = $origin ? $origin . '-parceiro-' : 'parceiro-';
    $logos = array_values(array_filter((array) ($partner['logo'] ?? [])));
    $logoSize = $partner['logo_size'] ?? null;
    $logoAlt = count($logos) > 1 ? 'Logos ' . $name : 'Logo ' . $name;
@endphp

<x-institutional.card :hover-effect="false" {{ $attributes->merge(['class' => 'flex h-full min-w-0 flex-col']) }}>
    <div class="mb-6 flex h-16 items-center">
        @if($logos !== [])
            <div class="flex max-w-full flex-wrap items-center gap-4" role="img" aria-label="{{ $logoAlt }}">
                @foreach($logos as $logo)
                    <img src="{{ asset($logo) }}" alt="" wire:key="partner-logo-{{ \Illuminate\Support\Str::slug($name) }}-{{ $loop->index }}" @class([
                        'w-auto max-w-full object-contain',
                        'max-h-14 max-w-[180px]' => count($logos) === 1 && $logoSize === null,
                        'max-h-12 max-w-[180px]' => count($logos) === 1 && $logoSize === 'square',
                        'max-h-10 max-w-[180px]' => count($logos) === 1 && $logoSize === 'compact',
                        'h-10 max-w-[160px]' => count($logos) > 1,
                    ]) loading="lazy" />
                @endforeach
            </div>
        @else
            <div class="flex items-center gap-3" role="img" aria-label="{{ $name }}">
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-bg-cream text-sm font-bold uppercase text-primary-strong">{{ $initials }}</span>
                <span class="text-lg font-semibold text-neutral-strong">{{ $name }}</span>
            </div>
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

    @if(!empty($partner['url']))
        <a href="{{ $partner['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $ctaPrefix }}{{ \Illuminate\Support\Str::slug($name) }}" class="inline-flex items-center justify-center gap-2 self-start rounded-[999px] border-2 border-primary-strong px-5 py-2 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
            Conhecer parceiro
            <flux:icon name="arrow-top-right-on-square" class="size-4" />
            <span class="sr-only">{{ $name }} (abre em nova aba)</span>
        </a>
    @endif
</x-institutional.card>
