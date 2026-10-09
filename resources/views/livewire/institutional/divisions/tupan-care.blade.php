@php
    $page = $division['page'] ?? [];
    $cta = $page['cta'] ?? [];
    $partnership = $page['partnership'] ?? [];
    $origin = $division['contact_form'] ?? 'tupan-care';
    $whatsappUrl = \App\Domain\Institutional\Services\WhatsappLink::make($cta['whatsapp_message'] ?? null);
    $gentell = collect($page['partners'] ?? [])->firstWhere('name', 'Gentell Casex');
@endphp

<div>
    <div class="relative flex min-h-[560px] items-center overflow-hidden pb-16 pt-36">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset($division['image'] ?? '') }}" alt="Profissional de saúde realizando cuidado de feridas" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-neutral-strong/80 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-strong via-transparent to-transparent"></div>
        </div>

        <div class="container relative z-10 mx-auto max-w-7xl px-6">
            <x-institutional.reveal>
                <a href="{{ route('institutional.home') }}#solucoes" class="mb-6 inline-flex items-center gap-2 text-white hover:text-primary-light">
                    <flux:icon name="arrow-left" class="size-5" />
                    Voltar para Soluções
                </a>

                <div class="mb-6 inline-flex flex-col items-start gap-2 rounded-2xl bg-white px-6 py-4 shadow-xl">
                    @if(!empty($page['logo']))
                        <h1 class="sr-only">{{ $division['title'] ?? 'Tupan Care' }}</h1>
                        <img src="{{ asset($page['logo']) }}" alt="Logo Tupan Care" class="h-14 w-auto md:h-16" />
                    @else
                        <h1 class="text-4xl font-bold tracking-tight md:text-5xl">
                            <span class="text-primary">TUPAN</span> <span class="font-medium text-secondary">Care</span>
                        </h1>
                    @endif
                    <span class="text-xs font-semibold uppercase tracking-wider text-neutral-medium">{{ $page['endorsement'] ?? 'Uma operação Tupan Saúde' }}</span>
                </div>

                <p class="max-w-3xl text-xl leading-relaxed text-neutral-light md:text-2xl">{{ $division['subtitle'] ?? '' }}</p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="{{ $origin }}-hero-contato" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                        {{ $cta['primary_label'] ?? 'Solicitar contato' }}
                    </a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-hero-treinamento" class="inline-flex items-center justify-center gap-2 rounded-[999px] border-2 border-white/70 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        <flux:icon name="academic-cap" class="size-4" />
                        {{ $cta['secondary_label'] ?? 'Solicitar treinamento' }}
                    </a>
                </div>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="white">
        <div class="max-w-3xl">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Sobre a Tupan Care</span>
                <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Cuidado de feridas e estomias com proximidade e conhecimento técnico</h2>
                <div class="space-y-5 text-lg leading-relaxed text-neutral-medium">
                    @foreach($page['intro'] ?? [] as $paragraph)
                        <p wire:key="tupan-care-intro-{{ $loop->index }}">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    @if(!empty($partnership))
        <x-institutional.section variant="cream">
            <div class="grid gap-12 lg:grid-cols-5 lg:gap-16">
                <x-institutional.reveal class="lg:col-span-3">
                    <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Parceria desde 2023</span>
                    <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">{{ $partnership['title'] ?? '' }}</h2>
                    <div class="space-y-5 text-lg leading-relaxed text-neutral-medium">
                        @foreach($partnership['paragraphs'] ?? [] as $paragraph)
                            <p wire:key="tupan-care-partnership-{{ $loop->index }}">{{ $paragraph }}</p>
                        @endforeach
                    </div>
                </x-institutional.reveal>

                @if(!empty($partnership['highlight']))
                    <x-institutional.reveal :delay="150" class="flex items-center lg:col-span-2">
                        <blockquote class="rounded-3xl bg-neutral-strong p-8 text-white shadow-xl lg:p-10">
                            <flux:icon name="heart" class="mb-6 size-8 text-secondary-light" />
                            <p class="text-2xl font-medium leading-snug">{{ $partnership['highlight'] }}</p>
                        </blockquote>
                    </x-institutional.reveal>
                @endif
            </div>
        </x-institutional.section>
    @endif

    <x-institutional.section variant="white">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Atuação</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Como atuamos</h2>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($page['approach'] ?? [] as $item)
                <x-institutional.reveal :delay="($loop->index % 3) * 100" wire:key="tupan-care-approach-{{ $loop->index }}">
                    <x-institutional.card variant="feature" class="h-full">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-bg-cream text-primary">
                            <flux:icon name="{{ $item['icon'] ?? 'sparkles' }}" class="size-6" />
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-neutral-strong">{{ $item['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-neutral-medium">{{ $item['description'] }}</p>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.section variant="light">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Soluções</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Soluções Gentell Casex</h2>
                <p class="text-lg text-neutral-medium">A indicação de cada produto deve seguir a avaliação profissional, os protocolos institucionais e os materiais oficiais do fabricante.</p>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach($page['solutions'] ?? [] as $item)
                <x-institutional.reveal :delay="$loop->index * 100" wire:key="tupan-care-solution-{{ $loop->index }}">
                    <x-institutional.card class="h-full">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-bg text-primary-dark">
                            <flux:icon name="{{ $item['icon'] ?? 'sparkles' }}" class="size-6" />
                        </div>
                        <h3 class="mb-3 text-lg font-semibold text-neutral-strong">{{ $item['title'] }}</h3>
                        <p class="text-sm leading-relaxed text-neutral-medium">{{ $item['description'] }}</p>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endforeach
        </div>

        @if(!empty($gentell['links']))
            <x-institutional.reveal :delay="200">
                <div class="mt-10 flex flex-wrap gap-3">
                    @foreach($gentell['links'] as $link)
                        <a wire:key="tupan-care-solution-link-{{ $loop->index }}" href="{{ $link['url'] }}" target="_blank" rel="noopener noreferrer" data-cta="{{ $origin }}-solucoes-{{ \Illuminate\Support\Str::slug($link['label']) }}" class="inline-flex items-center gap-2 rounded-[999px] border-2 border-primary px-5 py-2 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
                            {{ $link['label'] }}
                            <flux:icon name="arrow-top-right-on-square" class="size-4" />
                            <span class="sr-only">(abre em nova aba)</span>
                        </a>
                    @endforeach
                </div>
            </x-institutional.reveal>
        @endif
    </x-institutional.section>

    <x-institutional.section variant="white">
        <div class="grid gap-12 lg:grid-cols-3">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Público</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Para quem atuamos</h2>
                <p class="text-lg text-neutral-medium">Instituições, equipes assistenciais e profissionais envolvidos no cuidado de feridas e estomias.</p>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach($page['audience'] ?? [] as $audience)
                        <li wire:key="tupan-care-audience-{{ $loop->index }}" class="flex items-center gap-3 rounded-xl border border-neutral-border bg-bg-light px-4 py-3 text-neutral-strong">
                            <flux:icon name="check-circle" class="size-5 shrink-0 text-primary" />
                            {{ $audience }}
                        </li>
                    @endforeach
                </ul>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    <x-institutional.section id="parceiros" variant="cream">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Parceiro representado</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Quem está por trás da Tupan Care</h2>
                <p class="text-lg text-neutral-medium">Atendimento local da Tupan Care, portfólio Gentell Casex e o respaldo do grupo Tupan Saúde.</p>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-6 md:grid-cols-3">
            @foreach($page['partners'] ?? [] as $partner)
                <x-institutional.reveal :delay="$loop->index * 100" wire:key="tupan-care-partner-{{ $loop->index }}">
                    <x-institutional.partner-card :partner="$partner" :origin="$origin" />
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <section class="relative overflow-hidden bg-primary py-20">
        <div class="absolute left-0 top-0 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/10"></div>
        <div class="absolute bottom-0 right-0 h-96 w-96 translate-x-1/3 translate-y-1/3 rounded-full bg-white/10"></div>

        <div class="container relative z-10 mx-auto max-w-4xl px-6 text-center">
            <x-institutional.reveal>
                <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">{{ $cta['title'] ?? '' }}</h2>
                <p class="mx-auto mb-10 max-w-2xl text-lg text-primary-bg">{{ $cta['text'] ?? '' }}</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="{{ $origin }}-cta-contato" class="inline-flex items-center justify-center rounded-[999px] bg-white px-6 py-3 text-sm font-semibold text-primary transition hover:bg-primary-bg">
                        {{ $cta['primary_label'] ?? 'Solicitar contato' }}
                    </a>
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-cta-treinamento" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-secondary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-secondary-hover hover:shadow-md">
                        {{ $cta['secondary_label'] ?? 'Solicitar treinamento' }}
                    </a>
                </div>
            </x-institutional.reveal>
        </div>
    </section>

    <div class="bg-bg-cream">
        <x-institutional.sections.contact
            :origin="$origin"
            eyebrow="Tupan Care · Uma operação Tupan Saúde"
            title="Fale com a Tupan Saúde"
            text="Nossa equipe está preparada para entender sua necessidade e indicar o caminho mais adequado para sua instituição."
            :whatsapp-message="$cta['whatsapp_message'] ?? null"
        />
    </div>
</div>
