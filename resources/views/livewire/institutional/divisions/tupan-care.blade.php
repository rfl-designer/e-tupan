@php
    $page = $division['page'] ?? [];
    $cta = $page['cta'] ?? [];
    $partnership = $page['partnership'] ?? [];
    $origin = $division['contact_form'] ?? 'tupan-care';
    $whatsappReady = \App\Domain\Institutional\Services\WhatsappLink::isAvailable();
    $whatsappUrl = $whatsappReady ? \App\Domain\Institutional\Services\WhatsappLink::make($cta['whatsapp_message'] ?? null) : '#fale-conosco';
@endphp

<div>
    <div class="relative flex min-h-[560px] items-center overflow-hidden pb-16 pt-36">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/institucional/division-curativos.webp') }}" srcset="{{ asset('images/institucional/division-curativos-800.webp') }} 800w, {{ asset('images/institucional/division-curativos.webp') }} 1600w" sizes="100vw" fetchpriority="high" alt="" class="h-full w-full object-cover" />
            <div class="absolute inset-0 bg-neutral-strong/80 mix-blend-multiply"></div>
            <div class="absolute inset-0 bg-gradient-to-t from-neutral-strong via-transparent to-transparent"></div>
        </div>

        <div class="container relative z-10 mx-auto max-w-7xl px-6">
            <x-institutional.reveal>
                <a href="{{ route('institutional.home') }}#solucoes" class="mb-6 inline-flex w-fit items-center gap-2 text-white hover:text-primary-light">
                    <flux:icon name="arrow-left" class="size-5" />
                    Voltar para soluções
                </a>

                <div class="mb-4">
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-light">{{ $page['eyebrow'] ?? 'Divisão de cuidados de feridas' }}</span>
                </div>

                @if(!empty($page['logo']))
                    <div class="mb-5 w-fit rounded-xl bg-white px-3 py-2 shadow-xl">
                        <img src="{{ asset($page['logo']) }}" alt="Logo Tupan Care" width="112" height="54" class="h-auto max-h-[56px] w-auto object-contain" />
                    </div>
                @endif

                <h1 class="mb-4 max-w-3xl text-4xl font-medium text-white lg:text-5xl">{{ $page['headline'] ?? ($division['title'] ?? 'Tupan Care') }}</h1>
                <p class="max-w-3xl text-xl leading-relaxed text-neutral-100 md:text-2xl">{{ $division['subtitle'] ?? '' }}</p>
                <p class="mt-4 text-sm text-white/70">{{ $page['endorsement'] ?? 'Uma operação Tupan Saúde' }}</p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="{{ $origin }}-hero-contato" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                        {{ $cta['primary_label'] ?? 'Falar com um consultor' }}
                    </a>
                    <a href="{{ $whatsappUrl }}" x-data x-on:click="$dispatch('contact-topic', { topic: 'Treinamento' })" @if($whatsappReady) target="_blank" rel="noopener" @endif data-cta="{{ $origin }}-hero-treinamento" class="inline-flex items-center justify-center gap-2 rounded-[999px] border-2 border-white/70 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        <flux:icon name="academic-cap" class="size-4" />
                        {{ $cta['secondary_label'] ?? 'Solicitar treinamento' }}
                    </a>
                </div>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="white" class="py-12 md:py-24">
        <div class="grid gap-12 lg:grid-cols-5 lg:gap-16">
            <x-institutional.reveal class="lg:col-span-3">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Sobre a Tupan Care</span>
                <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Representação local da Gentell Casex no Nordeste</h2>
                <div class="space-y-5 text-lg leading-relaxed text-neutral-medium">
                    @foreach(array_slice($page['intro'] ?? [], 0, 3) as $paragraph)
                        <p wire:key="tupan-care-intro-{{ $loop->index }}">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </x-institutional.reveal>

            @if(!empty($partnership['highlight']))
                <x-institutional.reveal :delay="150" class="flex items-center lg:col-span-2">
                    <blockquote class="rounded-3xl bg-neutral-strong p-8 text-white shadow-xl lg:p-10">
                        <flux:icon name="heart" class="mb-6 size-8 text-secondary-light" />
                        <p class="mb-4 text-xs font-bold uppercase tracking-wider text-white/70">{{ $partnership['title'] ?? 'Tupan Care + Gentell Casex' }}</p>
                        <p class="text-2xl font-medium leading-snug">{{ $partnership['highlight'] }}</p>
                    </blockquote>
                </x-institutional.reveal>
            @endif
        </div>
    </x-institutional.section>

    <x-institutional.section variant="white" class="py-12 md:py-24">
        <x-institutional.reveal>
            <div class="mb-8 max-w-2xl md:mb-12">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Atuação</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Como atuamos</h2>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-3">
            @foreach($page['approach'] ?? [] as $item)
                <x-institutional.reveal :delay="($loop->index % 3) * 100" wire:key="tupan-care-approach-{{ $loop->index }}">
                    <x-institutional.card variant="feature" class="h-full" :hover-effect="false">
                        <div class="mb-3 flex items-center gap-3 md:mb-0 md:block">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-primary-bg text-primary-dark md:mb-5 md:size-12">
                                <flux:icon name="{{ $item['icon'] ?? 'sparkles' }}" class="size-5 md:size-6" />
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-strong md:mb-3">{{ $item['title'] }}</h3>
                        </div>
                        <p class="text-base leading-relaxed text-neutral-medium">{{ $item['description'] }}</p>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.section variant="light" class="py-12 md:py-24">
        <x-institutional.reveal>
            <div class="mb-8 max-w-2xl md:mb-12">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Soluções</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Soluções Gentell Casex</h2>
                <p class="text-lg text-neutral-medium">A indicação de cada produto deve seguir a avaliação profissional, os protocolos institucionais e os materiais oficiais do fabricante.</p>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-4 md:grid-cols-2 md:gap-6 lg:grid-cols-4">
            @foreach($page['solutions'] ?? [] as $item)
                <x-institutional.reveal :delay="$loop->index * 100" wire:key="tupan-care-solution-{{ $loop->index }}">
                    <x-institutional.card variant="feature" class="h-full" :hover-effect="false">
                        <div class="mb-3 flex items-center gap-3 md:mb-0 md:block">
                            <div class="flex size-10 shrink-0 items-center justify-center rounded-2xl bg-primary-bg text-primary-dark md:mb-5 md:size-12">
                                <flux:icon name="{{ $item['icon'] ?? 'sparkles' }}" class="size-5 md:size-6" />
                            </div>
                            <h3 class="text-lg font-semibold text-neutral-strong md:mb-3">{{ $item['title'] }}</h3>
                        </div>
                        <p class="text-base leading-relaxed text-neutral-medium">{{ $item['description'] }}</p>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.section variant="white" class="py-12 md:py-24">
        <div class="grid gap-12 lg:grid-cols-3">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Público</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Para quem atuamos</h2>
                <p class="text-lg text-neutral-medium">Instituições, equipes assistenciais e profissionais envolvidos no cuidado de feridas e estomias.</p>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <ul class="flex flex-wrap gap-2 sm:grid sm:grid-cols-2 sm:gap-3">
                    @foreach($page['audience'] ?? [] as $audience)
                        <li wire:key="tupan-care-audience-{{ $loop->index }}" class="flex items-start gap-3 rounded-xl border border-neutral-border bg-bg-light px-4 py-2 text-base sm:py-3 text-neutral-strong">
                            <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-primary-strong" />
                            {{ $audience }}
                        </li>
                    @endforeach
                </ul>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    <x-institutional.section id="parceiros" variant="cream" class="py-12 md:py-24">
        <x-institutional.reveal>
            <div class="mb-8 max-w-2xl md:mb-12">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Parceiro representado</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Quem está por trás da Tupan Care</h2>
                <p class="text-lg text-neutral-medium">Atendimento local da Tupan Care, portfólio Gentell Casex e o respaldo do grupo Tupan Saúde.</p>
            </div>
        </x-institutional.reveal>

        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($page['partners'] ?? [] as $partner)
                <x-institutional.reveal :delay="$loop->index * 100" wire:key="tupan-care-partner-{{ $loop->index }}">
                    <x-institutional.partner-card :partner="$partner" :origin="$origin" />
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <section class="relative overflow-hidden bg-primary-strong py-20">
        <div class="absolute left-0 top-0 h-64 w-64 -translate-x-1/2 -translate-y-1/2 rounded-full bg-white/10"></div>
        <div class="absolute bottom-0 right-0 h-96 w-96 translate-x-1/3 translate-y-1/3 rounded-full bg-white/10"></div>

        <div class="container relative z-10 mx-auto max-w-4xl px-6 text-center">
            <x-institutional.reveal>
                <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">{{ $cta['title'] ?? '' }}</h2>
                <p class="mx-auto mb-10 max-w-2xl text-lg text-primary-bg">{{ $cta['text'] ?? '' }}</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="{{ $origin }}-cta-contato" class="inline-flex items-center justify-center rounded-[999px] bg-white px-6 py-3 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg">
                        {{ $cta['primary_label'] ?? 'Falar com um consultor' }}
                    </a>
                    <a href="{{ $whatsappUrl }}" x-data x-on:click="$dispatch('contact-topic', { topic: 'Treinamento' })" @if($whatsappReady) target="_blank" rel="noopener" @endif data-cta="{{ $origin }}-cta-treinamento" class="inline-flex items-center justify-center rounded-[999px] border-2 border-white/80 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
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
            title="Fale com a equipe Tupan Care"
            text="Conte o que sua instituição precisa em curativos, estomias ou treinamento."
            :whatsapp-message="$cta['whatsapp_message'] ?? null"
        />
    </div>
</div>
