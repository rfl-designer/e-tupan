@props([
    'type' => 'email',
    'value' => '',
])

@php
    $isPlaceholder = str_starts_with((string) $value, '[PREENCHER');
    $href = match (true) {
        $isPlaceholder => null,
        $type === 'phone' => 'tel:+55' . preg_replace('/\D/', '', (string) $value),
        default => 'mailto:' . $value,
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => 'transition-colors hover:text-primary-strong hover:underline']) }}>{{ $value }}</a>
@else
    <span {{ $attributes->except('class') }}>{{ $value }}</span>
@endif
