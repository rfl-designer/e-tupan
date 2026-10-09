@props([
    'delay' => 0,
])

<div
    x-data="{ shown: false }"
    x-intersect.once="shown = true"
    x-bind:class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    style="transition-delay: {{ (int) $delay }}ms;"
    {{ $attributes->merge(['class' => 'transition duration-700 ease-out']) }}
>
    {{ $slot }}
</div>
