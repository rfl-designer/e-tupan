@props([
    'id' => null,
    'variant' => 'white',
])

@php
    $backgrounds = [
        'white' => 'bg-bg-white',
        'light' => 'bg-bg-light',
        'cream' => 'bg-bg-cream',
        'dark' => 'bg-bg-dark text-white',
        'accent' => 'bg-bg-accent',
    ];
@endphp

<section
    @if($id) id="{{ $id }}" @endif
    {{ $attributes->merge(['class' => 'py-16 md:py-24 ' . ($backgrounds[$variant] ?? $backgrounds['white'])]) }}
>
    <div class="container mx-auto max-w-7xl px-6">
        {{ $slot }}
    </div>
</section>
