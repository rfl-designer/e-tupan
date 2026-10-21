@php
    $page = $division['page'] ?? [];
    $cta = $page['cta'] ?? [];
    $origin = $division['contact_form'] ?? 'equipa-hosp';
    $whatsappReady = \App\Domain\Institutional\Services\WhatsappLink::isAvailable();
    $whatsappUrl = $whatsappReady ? \App\Domain\Institutional\Services\WhatsappLink::make($cta['whatsapp_message'] ?? null) : null;
@endphp

<div>
    <div class="relative flex min-h-[560px] items-center overflow-hidden pb-16 pt-36">
        <div class="absolute inset-0 z-0">
            <img src="{{ asset($division['image'] ?? '') }}" srcset="{{ asset(str_replace('.webp', '-800.webp', $division['image'] ?? '')) }} 800w, {{ asset($division['image'] ?? '') }} 1600w" sizes="100vw" fetchpriority="high" alt="Equipe técnica da EquipaHosp em ambiente hospitalar" class="h-full w-full object-cover" />
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
                    <span class="text-xs font-bold uppercase tracking-wider text-primary-light">{{ $page['eyebrow'] ?? 'Divisão de equipamentos' }}</span>
                </div>

                <div class="mb-5 w-fit max-w-[220px] drop-shadow-lg [&_svg]:h-auto [&_svg]:max-h-[56px] [&_svg]:w-full">
                    <x-assets.logo-equipahosp />
                </div>

                <h1 class="mb-4 max-w-3xl text-4xl font-medium text-white lg:text-5xl">{{ $page['headline'] ?? ($division['title'] ?? 'EquipaHosp') }}</h1>
                <p class="max-w-3xl text-xl leading-relaxed text-neutral-100 md:text-2xl">{{ $division['subtitle'] ?? '' }}</p>
                <p class="mt-4 text-sm text-white/70">Uma operação Tupan Saúde</p>

                <div class="mt-8 flex flex-col gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="{{ $origin }}-hero-avaliacao-tecnica" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                        {{ $cta['primary_label'] ?? 'Falar com um consultor' }}
                    </a>
                    @if($whatsappReady)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-hero-consultor" class="inline-flex items-center justify-center gap-2 rounded-[999px] border-2 border-white/70 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        <flux:icon name="chat-bubble-left-right" class="size-4" />
                        {{ $cta['secondary_label'] ?? 'Falar pelo WhatsApp' }}
                    </a>
                    @endif
                </div>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="white">
        <div class="grid gap-12 lg:grid-cols-5 lg:gap-16">
            <x-institutional.reveal class="lg:col-span-3">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Sobre a EquipaHosp</span>
                <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Da avaliação à manutenção do seu parque tecnológico</h2>
                <div class="space-y-5 text-lg leading-relaxed text-neutral-medium">
                    @foreach($page['intro'] ?? [] as $paragraph)
                        <p wire:key="equipahosp-intro-{{ $loop->index }}">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </x-institutional.reveal>

            @if(!empty($page['highlight']))
                <x-institutional.reveal :delay="150" class="flex items-center lg:col-span-2">
                    <blockquote class="relative rounded-3xl bg-neutral-strong p-8 text-white shadow-xl lg:p-10">
                        <flux:icon name="sparkles" class="mb-6 size-8 text-primary-light" />
                        <p class="text-2xl font-medium leading-snug">{{ $page['highlight'] }}</p>
                    </blockquote>
                </x-institutional.reveal>
            @endif
        </div>
    </x-institutional.section>

    <x-institutional.section variant="cream">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Soluções</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">O que a EquipaHosp oferece</h2>
            </div>
        </x-institutional.reveal>

        @php
            $hubItems = $page['hub_items'] ?? [];
            $hubCount = count($hubItems);
        @endphp
        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
            @foreach($hubItems as $item)
                @php
                    $spansMd = $loop->last && $hubCount % 2 === 1;
                    $spansLg = $loop->last && $hubCount % 3 === 1;
                @endphp
                <x-institutional.reveal :delay="($loop->index % 3) * 100" wire:key="equipahosp-hub-{{ $loop->index }}" class="{{ $spansMd ? 'md:col-span-2' : '' }} {{ $spansLg ? 'lg:col-span-3' : ($spansMd ? 'lg:col-span-1' : '') }}">
                    <x-institutional.card variant="feature" class="h-full" :hover-effect="false">
                        <div @class(['flex flex-col', 'lg:flex-row lg:items-start lg:gap-6' => $spansLg])>
                            <div @class(['mb-5 flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary-bg text-primary-dark', 'lg:mb-0' => $spansLg])>
                                <flux:icon name="{{ $item['icon'] ?? 'sparkles' }}" class="size-6" />
                            </div>
                            <div @class(['lg:max-w-3xl' => $spansLg])>
                                <h3 class="mb-3 text-lg font-semibold text-neutral-strong">{{ $item['title'] }}</h3>
                                <p class="text-base leading-relaxed text-neutral-medium">{{ $item['description'] }}</p>
                            </div>
                        </div>
                    </x-institutional.card>
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.section variant="white">
        <div class="grid gap-12 lg:grid-cols-3">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Público</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Para quem atuamos</h2>
                <p class="text-lg text-neutral-medium">Instituições e profissionais responsáveis pela tecnologia, pela operação e pelas decisões de compra em saúde.</p>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach($page['audience'] ?? [] as $audience)
                        <li wire:key="equipahosp-audience-{{ $loop->index }}" class="flex items-start gap-3 rounded-xl border border-neutral-border bg-bg-light px-4 py-3 text-base text-neutral-strong">
                            <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-primary-strong" />
                            {{ $audience }}
                        </li>
                    @endforeach
                </ul>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    <x-institutional.section id="parceiros" variant="light">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Parceiros</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">Parceiros e soluções conectadas</h2>
                <p class="text-lg text-neutral-medium">Cada parceiro entra com uma função específica para atender às necessidades das instituições.</p>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach($page['partners'] ?? [] as $partner)
                <x-institutional.reveal :delay="$loop->index * 100" wire:key="equipahosp-partner-{{ $loop->index }}">
                    <x-institutional.partner-card :partner="$partner" :origin="$origin" />
                </x-institutional.reveal>
            @endforeach
        </div>
    </x-institutional.section>

    <x-institutional.section variant="dark">
        <div class="grid gap-12 lg:grid-cols-3">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-primary-light">Diferenciais</span>
                <h2 class="text-3xl font-medium text-white md:text-4xl">Por que escolher a EquipaHosp</h2>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <ul class="grid gap-4 sm:grid-cols-2">
                    @foreach($page['reasons'] ?? [] as $reason)
                        <li wire:key="equipahosp-reason-{{ $loop->index }}" class="flex items-start gap-3 rounded-xl bg-white/5 p-4">
                            <flux:icon name="check-circle" class="size-5 shrink-0 text-primary-light" />
                            <span class="text-neutral-light">{{ $reason }}</span>
                        </li>
                    @endforeach
                </ul>
            </x-institutional.reveal>
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
                    <a href="#fale-conosco" data-cta="{{ $origin }}-cta-avaliacao-tecnica" class="inline-flex items-center justify-center rounded-[999px] bg-white px-6 py-3 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg">
                        {{ $cta['primary_label'] ?? 'Falar com um consultor' }}
                    </a>
                    @if($whatsappReady)
                    <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-cta-consultor" class="inline-flex items-center justify-center rounded-[999px] border-2 border-white/80 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                        {{ $cta['secondary_label'] ?? 'Falar pelo WhatsApp' }}
                    </a>
                    @endif
                </div>
            </x-institutional.reveal>
        </div>
    </section>

    <div class="bg-bg-cream">
        <x-institutional.sections.contact
            :origin="$origin"
            eyebrow="EquipaHosp · Uma operação Tupan Saúde"
            title="Fale com a equipe EquipaHosp"
            text="Conte o que sua instituição precisa em equipamentos, manutenção ou engenharia clínica."
            :whatsapp-message="$cta['whatsapp_message'] ?? null"
        />
    </div>
</div>
