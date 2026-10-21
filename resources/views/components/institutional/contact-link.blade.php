@props([
    'type' => 'email',
    'value' => '',
    'onDark' => false,
])

@php
    $isPlaceholder = str_starts_with((string) $value, '[PREENCHER');
    $href = match (true) {
        $isPlaceholder => null,
        $type === 'phone' => 'tel:+55' . preg_replace('/\D/', '', (string) $value),
        default => 'mailto:' . $value,
    };
@endphp

@php
    $tone = $onDark
        ? 'text-primary-light decoration-primary-light/50 hover:decoration-primary-light'
        : 'text-primary-strong decoration-primary-strong/40 hover:decoration-primary-strong';
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => "inline-block py-1 underline underline-offset-4 transition-colors {$tone}"]) }}>{{ $value }}</a>
@else
    <span {{ $attributes->except('class') }}>{{ $value }}</span>
@endif
