@props([
    'delay' => 0,
])

<div
    x-data="{ shown: false }"
    x-intersect.once="shown = true"
    x-bind:class="shown ? 'opacity-100 translate-y-0' : 'opacity-0 translate-y-4'"
    class="transition duration-700 ease-out"
    style="transition-delay: {{ (int) $delay }}ms;"
    {{ $attributes }}
>
    {{ $slot }}
</div>
