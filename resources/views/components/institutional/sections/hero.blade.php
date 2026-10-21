<x-institutional.section variant="light" class="pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden">
    <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
        <div class="z-10 flex-1 space-y-8">
            <x-institutional.reveal>
                <div class="inline-flex items-center gap-2 rounded-full border border-primary-light bg-primary-bg px-3 py-1">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-primary-strong"></span>
                    <span class="text-xs font-bold uppercase tracking-wide text-primary-strong">16 anos de conhecimento técnico</span>
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="80">
                <h1 class="text-3xl font-medium leading-[1.15] text-neutral-strong md:text-4xl lg:text-[40px]">
                    <span class="text-primary-strong">Distribuição técnica em saúde</span> para hospitais, laboratórios e profissionais do Nordeste
                </h1>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="160">
                <p class="max-w-xl text-lg leading-relaxed text-neutral-medium md:text-xl">
                    Produtos, equipamentos e consultoria com respaldo técnico. Sede em Recife, 16 anos de atuação.
                </p>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="240">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="{{ route('contact') }}" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary-strong px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                        Falar com um consultor
                    </a>
                    <a href="#solucoes" class="inline-flex items-center justify-center rounded-[999px] border-2 border-primary-strong bg-transparent px-6 py-3 text-sm font-semibold text-primary-strong transition hover:bg-primary-bg hover:text-primary-strong-hover">
                        Conheça nossas soluções
                    </a>
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="320">
                <div class="flex flex-wrap gap-6 pt-4 text-sm font-medium text-neutral-medium">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary-strong" />
                        <span>Conformidade ANVISA</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary-strong" />
                        <span>Consultoria técnica</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary-strong" />
                        <span>Cobertura Nordeste</span>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>

        <div class="relative h-[400px] w-full flex-1 lg:h-full">
            <x-institutional.reveal :delay="200" class="h-full w-full">
                <div class="relative h-full w-full">
                    <div class="absolute bottom-10 left-10 h-24 w-24 rounded-full bg-secondary-light opacity-20"></div>
                    <div class="relative z-10 h-full overflow-hidden rounded-[32px] border-4 border-white shadow-2xl">
                        <img
                            src="{{ asset('images/institucional/hero-home.webp') }}"
                            srcset="{{ asset('images/institucional/hero-home-800.webp') }} 800w, {{ asset('images/institucional/hero-home.webp') }} 1038w"
                            sizes="(min-width: 1024px) 560px, 100vw"
                            width="1038"
                            height="1300"
                            fetchpriority="high"
                            alt="Equipe técnica TUPAN em ambiente hospitalar"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute bottom-6 left-6 right-6 rounded-xl border-l-4 border-primary-strong bg-white/95 p-4 shadow-lg backdrop-blur-sm">
                            <p class="text-sm font-medium text-neutral-strong">7 divisões especializadas</p>
                            <p class="mt-1 text-xs uppercase tracking-wider text-neutral-medium">Atuação em todo o Nordeste</p>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    </div>
</x-institutional.section>
