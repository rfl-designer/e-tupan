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

    @php
        $sentences = preg_split('/(?<=[.!?])\s+/', trim($division['full_description'] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $totalWords = str_word_count(implode(' ', $sentences), 0, 'àáâãçéêíóôõúÀÁÂÃÇÉÊÍÓÔÕÚ');
        $aboutParagraphs = [];
        $currentParagraph = [];
        $currentWords = 0;
        foreach ($sentences as $sentence) {
            $currentParagraph[] = $sentence;
            $currentWords += count(preg_split('/\s+/', $sentence));
            if (count($aboutParagraphs) === 0 && $currentWords >= $totalWords / 2) {
                $aboutParagraphs[] = implode(' ', $currentParagraph);
                $currentParagraph = [];
            }
        }
        if ($currentParagraph !== []) {
            $aboutParagraphs[] = implode(' ', $currentParagraph);
        }
        $whatsappReady = \App\Domain\Institutional\Services\WhatsappLink::isAvailable();
        $whatsappUrl = $whatsappReady ? \App\Domain\Institutional\Services\WhatsappLink::make() : null;
        $divisionId = $division['id'] ?? 'divisao';
        $divisionTitle = $division['title'] ?? 'Divisão';
    @endphp

    <x-institutional.section variant="white">
        <div class="grid gap-12 lg:grid-cols-5 lg:gap-16">
            <x-institutional.reveal class="lg:col-span-3">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Sobre a divisão</span>
                <h2 class="mb-6 text-3xl font-medium text-neutral-strong">{{ $division['about_title'] ?? ($division['description'] ?? 'Sobre a divisão') }}</h2>
                <div class="space-y-5 text-lg leading-relaxed text-neutral-medium">
                    @foreach($aboutParagraphs as $paragraph)
                        <p wire:key="division-about-{{ $loop->index }}">{{ $paragraph }}</p>
                    @endforeach
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <div class="rounded-2xl border border-neutral-border bg-bg-light p-8">
                    <h3 class="mb-6 flex items-center gap-2 text-xl font-semibold text-neutral-strong">
                        <flux:icon name="shield-check" class="size-5 text-primary-strong" />
                        Diferenciais TUPAN
                    </h3>
                    <ul class="space-y-4">
                        @foreach(($division['differentiators'] ?? []) ?: ['Conhecimento técnico aplicado', 'Consultoria especializada', 'Logística ágil e segura', 'Conformidade ANVISA'] as $diff)
                            <li wire:key="division-diff-{{ $loop->index }}" class="flex items-start gap-3">
                                <flux:icon name="check-circle" class="size-5 shrink-0 text-primary-strong" />
                                <span class="text-neutral-strong">{{ $diff }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    <x-institutional.section id="solucoes-oferecidas" variant="light" class="scroll-mt-32">
        <x-institutional.reveal>
            <div class="mb-12 max-w-2xl">
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Soluções</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Soluções oferecidas</h2>
            </div>
        </x-institutional.reveal>

        <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
            @foreach($division['features'] ?? [] as $feature)
                @php
                    $featureTitle = is_array($feature) ? ($feature['title'] ?? '') : $feature;
                    $featureText = is_array($feature) ? ($feature['text'] ?? null) : null;
                    $featureIcon = is_array($feature) ? ($feature['icon'] ?? 'check-badge') : 'check-badge';
                @endphp
                <x-institutional.reveal :delay="($loop->index % 4) * 100" wire:key="division-feature-{{ $loop->index }}">
                    <x-institutional.card variant="feature" class="h-full" :hover-effect="false">
                        <div class="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-bg text-primary-dark">
                            <flux:icon name="{{ $featureIcon }}" class="size-6" />
                        </div>
                        <h3 class="text-lg font-semibold text-neutral-strong">{{ $featureTitle }}</h3>
                        @if($featureText)
                            <p class="mt-2 text-base leading-relaxed text-neutral-medium">{{ $featureText }}</p>
                        @endif
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
                @if(! empty($division['description']))
                    <p class="mb-4 text-base leading-relaxed text-neutral-medium">{{ $division['description'] }}</p>
                @endif
                @if(($division['id'] ?? '') === 'imagem')
                    <p class="text-lg text-neutral-medium">Atuação em nível nacional (Brasil), fornecendo para empresas de diagnóstico de todos os portes.</p>
                @endif
            </x-institutional.reveal>

            <x-institutional.reveal :delay="100" class="lg:col-span-2">
                <ul class="grid gap-3 sm:grid-cols-2">
                    @foreach($division['target_audience'] ?? [] as $audience)
                        <li wire:key="division-audience-{{ $loop->index }}" class="flex items-start gap-3 rounded-xl border border-neutral-border bg-bg-light px-4 py-3 text-base text-neutral-strong">
                            <flux:icon name="check-circle" class="mt-0.5 size-5 shrink-0 text-primary-strong" />
                            {{ $audience }}
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
                <h2 class="mb-6 text-3xl font-bold text-white md:text-4xl">{{ $division['cta_title'] ?? 'Conte o que sua instituição precisa e indicamos o caminho mais adequado.' }}</h2>
                <p class="mx-auto mb-10 max-w-2xl text-lg text-white">Fale com a equipe técnica e receba uma indicação adequada à rotina da sua operação.</p>
                <div class="flex flex-col justify-center gap-4 sm:flex-row">
                    <a href="#fale-conosco" data-cta="divisao-{{ $divisionId }}-cta-contato" class="inline-flex items-center justify-center rounded-[999px] bg-white px-6 py-3 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg">
                        Falar com um consultor
                    </a>
                    @if($whatsappReady)
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="divisao-{{ $divisionId }}-cta-whatsapp" class="inline-flex items-center justify-center rounded-[999px] border-2 border-white/80 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/10">
                            Falar pelo WhatsApp
                        </a>
                    @endif
                </div>
            </x-institutional.reveal>
        </div>
    </section>

    <x-institutional.sections.contact
        origin="default"
        background="cream"
        :eyebrow="$divisionTitle"
        :title="'Fale com a equipe '.$divisionTitle"
        :text="! empty($division['need_area']) ? 'Conte o que sua instituição precisa em '.$division['need_area'].' e indicamos o caminho mais adequado.' : 'Conte o que sua instituição precisa e indicamos o caminho mais adequado.'"
        :division="$division"
    />
</div>
