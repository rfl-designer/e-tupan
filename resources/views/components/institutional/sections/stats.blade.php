@php
    $stats = [
        ['value' => '16', 'label' => 'Anos de Conhecimento Aplicado'],
        ['value' => '5k+', 'label' => 'Solucoes em Catalogo'],
        ['value' => '9', 'label' => 'Estados Atendidos no Nordeste'],
        ['value' => '24h', 'label' => 'Suporte Tecnico Especializado'],
    ];
@endphp

<x-institutional.section variant="dark" class="relative overflow-hidden">
    <div class="absolute right-0 top-0 h-96 w-96 -translate-y-1/2 translate-x-1/2 rounded-full bg-primary opacity-5 blur-3xl"></div>

    <div class="relative z-10">
        <div class="mb-16 grid gap-8 border-b border-white/10 pb-16 md:grid-cols-4">
            @foreach($stats as $index => $stat)
                <x-institutional.reveal :delay="$index * 100">
                    <div class="text-center">
                        <div class="mb-2 text-4xl font-bold text-white md:text-5xl">{{ $stat['value'] }}</div>
                        <div class="text-sm font-medium uppercase tracking-wider text-neutral-light">{{ $stat['label'] }}</div>
                    </div>
                </x-institutional.reveal>
            @endforeach
        </div>

        <div class="text-center">
            <x-institutional.reveal>
                <h3 class="mb-8 font-medium text-white">Credenciais que Sustentam Nossa Autoridade</h3>
            </x-institutional.reveal>
            <x-institutional.reveal :delay="100">
                <div class="flex flex-wrap justify-center gap-8 opacity-80 md:gap-16">
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                        <flux:icon name="shield-check" class="size-5 text-primary-light" />
                        <span class="font-semibold text-white">ANVISA</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                        <flux:icon name="trophy" class="size-5 text-primary-light" />
                        <span class="font-semibold text-white">ISO 9001</span>
                    </div>
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                        <flux:icon name="hand-thumb-up" class="size-5 text-primary-light" />
                        <span class="font-semibold text-white">Parcerias com Multinacionais</span>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    </div>
</x-institutional.section>
