@props([
    'variant' => 'standard',
    'hoverEffect' => true,
])

@php
    $variants = [
        'standard' => 'bg-white border border-neutral-border shadow-[0_2px_8px_rgba(28,37,65,0.08)]',
        'elevated' => 'bg-white shadow-[0_4px_16px_rgba(28,37,65,0.12)] border-none',
        'accent' => 'bg-primary-bg border border-primary-light',
        'feature' => 'bg-white border-l-4 border-primary rounded-[12px] shadow-sm',
        'stats' => 'bg-primary text-white',
        'dark' => 'bg-neutral-strong text-white',
    ];

    $hoverStyles = $hoverEffect && !in_array($variant, ['stats', 'dark'], true)
        ? 'hover:-translate-y-0.5 hover:shadow-[0_4px_16px_rgba(28,37,65,0.12)]'
        : '';
@endphp

<div {{ $attributes->merge(['class' => "rounded-[16px] p-6 transition-all duration-200 ease-out {$variants[$variant]} {$hoverStyles}"]) }}>
    {{ $slot }}
</div>
