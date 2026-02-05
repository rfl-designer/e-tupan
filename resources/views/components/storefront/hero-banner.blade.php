@props([
    'title' => 'Bem-vindo a nossa loja',
    'subtitle' => 'Encontre os melhores produtos com os melhores precos',
    'ctaText' => 'Ver produtos',
    'ctaLink' => '#produtos',
    'backgroundImage' => null,
])

<section class="relative overflow-hidden bg-gradient-to-r from-zinc-900 to-zinc-800">
    @if($backgroundImage)
        <div class="absolute inset-0">
            <img
                src="{{ $backgroundImage }}"
                alt=""
                class="size-full object-cover opacity-30"
            />
        </div>
    @endif

    <div class="relative mx-auto max-w-7xl px-4 py-24 sm:px-6 sm:py-32 lg:px-8">
        <div class="max-w-2xl">
            <h1 class="text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl">
                {{ $title }}
            </h1>
            <p class="mt-6 text-lg text-zinc-300">
                {{ $subtitle }}
            </p>
            <div class="mt-10">
                <flux:button
                    variant="primary"
                    href="{{ $ctaLink }}"
                    icon-trailing="arrow-right"
                    class="px-8 py-3"
                >
                    {{ $ctaText }}
                </flux:button>
            </div>
        </div>
    </div>

    {{-- Decorative Element --}}
    <div class="absolute -right-20 -top-20 size-96 rounded-full bg-white/5 blur-3xl"></div>
    <div class="absolute -bottom-20 -left-20 size-96 rounded-full bg-white/5 blur-3xl"></div>
</section>
