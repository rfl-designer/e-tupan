@php
    $division = $division ?? [];
@endphp

<div>
    <div class="relative flex min-h-[560px] items-start overflow-hidden pb-16 pt-36">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset($division['image'] ?? '') }}" srcset="{{ asset(str_replace('.webp', '-800.webp', $division['image'] ?? '')) }} 800w, {{ asset($division['image'] ?? '') }} 1600w" sizes="100vw" fetchpriority="high" alt="" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-neutral-strong/80 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-strong via-transparent to-transparent"></div>
        </div>

        <div class="container mx-auto max-w-7xl px-6 relative z-10">
            <x-institutional.reveal>
                <a href="{{ route('institutional.home') }}#solucoes" class="mb-6 inline-flex w-fit items-center gap-2 text-white hover:text-primary-light">
                    <flux:icon name="arrow-left" class="size-5" />
                    Voltar para soluções
                </a>

                <div class="mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-light">Divisão especializada</span>
                </div>

                <h1 class="mb-6 max-w-4xl text-4xl font-medium text-white md:text-5xl lg:text-6xl">{{ $division['title'] ?? 'Divisão' }}</h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light md:text-2xl">{{ $division['subtitle'] ?? '' }}</p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="#contato" data-cta="divisao-{{ $division['id'] ?? 'divisao' }}-hero-contato" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                        Falar com um consultor
                    </a>
                    <a href="#solucoes-oferecidas" data-cta="divisao-{{ $division['id'] ?? 'divisao' }}-hero-solucoes" class="inline-flex items-center justify-center rounded-[999px] border-2 border-white/70 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        Ver soluções
                    </a>
                </div>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="white">
        <div class="grid gap-12 lg:grid-cols-3">
            <div class="space-y-8 lg:col-span-2">
                <x-institutional.reveal>
                    <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Sobre a divisão</span>
                    <h2 class="mb-6 text-3xl font-medium text-neutral-strong">{{ $division['about_title'] ?? ($division['description'] ?? 'Sobre a divisão') }}</h2>
                    <p class="text-lg leading-relaxed text-neutral-medium">
                        {{ $division['full_description'] ?? '' }}
                    </p>
                </x-institutional.reveal>

                <x-institutional.reveal :delay="100">
                    <div class="mt-8 rounded-2xl border border-neutral-border bg-bg-light p-8">
                        <h3 class="mb-6 flex items-center gap-2 text-xl font-semibold text-neutral-strong">
                            <flux:icon name="shield-check" class="size-5 text-primary-strong" />
                            Diferenciais TUPAN
                        </h3>
                        <div class="grid gap-4 sm:grid-cols-2">
                            @if(!empty($division['differentiators']))
                                @foreach($division['differentiators'] as $diff)
                                    <div class="flex items-start gap-3">
                                        <flux:icon name="check-circle" class="size-5 text-primary-strong" />
                                        <span class="text-neutral-strong">{{ $diff }}</span>
                                    </div>
                                @endforeach
                            @else
                                <div class="flex items-start gap-3">
                                    <flux:icon name="check-circle" class="size-5 text-primary-strong" />
                                    <span class="text-neutral-strong">Conhecimento técnico aplicado</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <flux:icon name="check-circle" class="size-5 text-primary-strong" />
                                    <span class="text-neutral-strong">Consultoria especializada</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <flux:icon name="check-circle" class="size-5 text-primary-strong" />
                                    <span class="text-neutral-strong">Logística ágil e segura</span>
                                </div>
                                <div class="flex items-start gap-3">
                                    <flux:icon name="check-circle" class="size-5 text-primary-strong" />
                                    <span class="text-neutral-strong">Conformidade ANVISA</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </x-institutional.reveal>
            </div>

            <div class="space-y-6">
                <x-institutional.reveal :delay="200" id="solucoes-oferecidas" class="scroll-mt-32">
                    <x-institutional.card variant="standard" class="border-t-4 border-t-primary-strong" :hover-effect="false">
                        <h3 class="mb-4 text-sm font-bold uppercase tracking-wide text-neutral-strong">Soluções oferecidas</h3>
                        <ul class="space-y-3">
                            @foreach($division['features'] ?? [] as $feature)
                                <li class="flex items-start gap-3 text-sm text-neutral-medium">
                                    <flux:icon name="check-circle" class="size-5 shrink-0 text-primary-strong" />
                                    {{ $feature }}
                                </li>
                            @endforeach
                        </ul>
                    </x-institutional.card>
                </x-institutional.reveal>

                <x-institutional.reveal :delay="300">
                    <x-institutional.card variant="standard" class="border-t-4 border-t-primary-strong" :hover-effect="false">
                        <div class="mb-4 flex items-center gap-2 text-primary-strong">
                            <flux:icon name="users" class="size-5" />
                            <h3 class="text-sm font-bold uppercase tracking-wide">Para quem atuamos</h3>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach($division['target_audience'] ?? [] as $audience)
                                <span class="rounded-full bg-primary-bg px-3 py-1.5 text-xs font-medium text-primary-strong">
                                    {{ $audience }}
                                </span>
                            @endforeach
                        </div>
                    </x-institutional.card>
                </x-institutional.reveal>

                @if(($division['id'] ?? '') === 'imagem')
                    <x-institutional.reveal :delay="400">
                    <x-institutional.card variant="standard" class="bg-neutral-strong text-white" :hover-effect="false">
                        <div class="mb-2 flex items-center gap-2 text-primary-light">
                            <flux:icon name="map-pin" class="size-5" />
                            <h3 class="text-sm font-bold uppercase tracking-wide">Alcance</h3>
                        </div>
                        <p class="text-sm text-neutral-light">
                                Atuação em nível nacional (Brasil), fornecendo para empresas de diagnóstico de todos os portes.
                        </p>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endif
        </div>
    </div>
</x-institutional.section>

    <x-institutional.sections.contact
        origin="default"
        background="cream"
        :eyebrow="$division['title'] ?? 'Divisão'"
        title="Fale com a Tupan Saúde"
        text="Nossa equipe está preparada para entender sua necessidade e indicar o caminho mais adequado para sua instituição."
    />
</div>
