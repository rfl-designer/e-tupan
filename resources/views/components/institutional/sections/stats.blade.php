@php
    $stats = [
        ['value' => '16', 'label' => 'Anos de conhecimento aplicado'],
        ['value' => '[PREENCHER: nº de soluções em catálogo]', 'label' => 'Soluções em catálogo'],
        ['value' => '[PREENCHER: nº de estados atendidos]', 'label' => 'Estados atendidos no Nordeste'],
        ['value' => '[PREENCHER: janela de suporte técnico]', 'label' => 'Suporte técnico especializado'],
    ];
    $stats = array_values(array_filter($stats, fn ($stat) => ! str_starts_with($stat['value'], '[PREENCHER')));
@endphp

@php
    $singleStat = count($stats) === 1 ? $stats[0] : null;
@endphp

<x-institutional.section variant="dark" class="relative overflow-hidden py-10! md:py-12!">
    <div class="absolute right-0 top-0 h-96 w-96 -translate-y-1/2 translate-x-1/2 rounded-full bg-primary-strong opacity-5 blur-3xl"></div>

    <div class="relative z-10">
        @if(! $singleStat && $stats !== [])
            <div class="mb-10 flex flex-wrap justify-center gap-12 border-b border-white/10 pb-10 md:gap-20">
                @foreach($stats as $index => $stat)
                    <x-institutional.reveal :delay="$index * 100">
                        <div class="text-center">
                            <div class="mb-2 text-4xl font-bold text-white md:text-5xl">{{ $stat['value'] }}</div>
                            <div class="text-sm font-medium uppercase tracking-wider text-neutral-light">{{ $stat['label'] }}</div>
                        </div>
                    </x-institutional.reveal>
                @endforeach
            </div>
        @endif

        <div class="text-center">
            <x-institutional.reveal>
                <h3 class="mb-6 font-medium text-white">Credenciais que sustentam nossa autoridade</h3>
            </x-institutional.reveal>
            <x-institutional.reveal :delay="100">
                <div class="flex flex-wrap justify-center gap-4 md:gap-6">
                    @if($singleStat)
                        <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                            <flux:icon name="calendar-days" class="size-5 text-primary-light" />
                            <span class="font-semibold text-white">{{ $singleStat['value'] }} anos de conhecimento aplicado</span>
                        </div>
                    @endif
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                        <flux:icon name="shield-check" class="size-5 text-primary-light" />
                        <span class="font-semibold text-white">ANVISA</span>
                    </div>
                    {{-- [PREENCHER: certificações reais (ex.: ISO) quando confirmadas] --}}
                    <div class="flex items-center gap-3 rounded-full border border-white/10 bg-white/5 px-6 py-3">
                        <flux:icon name="hand-thumb-up" class="size-5 text-primary-light" />
                        <span class="font-semibold text-white">Parcerias com multinacionais</span>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    </div>
</x-institutional.section>
