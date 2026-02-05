<x-institutional.section variant="light" class="pt-32 pb-20 md:pt-40 md:pb-32 overflow-hidden">
    <div class="flex flex-col items-center gap-12 lg:flex-row lg:gap-20">
        <div class="z-10 flex-1 space-y-8">
            <x-institutional.reveal>
                <div class="inline-flex items-center gap-2 rounded-full border border-primary-light bg-primary-bg px-3 py-1">
                    <span class="h-2 w-2 animate-pulse rounded-full bg-primary"></span>
                    <span class="text-xs font-bold uppercase tracking-wide text-primary">16 Anos de Conhecimento Tecnico</span>
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="80">
                <h1 class="text-4xl font-medium leading-[1.1] text-neutral-strong md:text-5xl lg:text-[56px]">
                    Mais que produtos. <span class="text-primary">Solucoes tecnicas</span> para quem cuida de vidas.
                </h1>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="160">
                <p class="max-w-xl text-lg leading-relaxed text-neutral-medium md:text-xl">
                    Ha 16 anos, intermediamos seguranca, tecnica e cuidado para hospitais, laboratorios e profissionais de saude em todo o Nordeste. Nascemos no Sertao. Construimos nossa autoridade com estudo, responsabilidade e entrega coerente.
                </p>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="240">
                <div class="flex flex-col gap-4 sm:flex-row">
                    <a href="#solucoes" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                        Conheca Nossas Solucoes
                    </a>
                    <a href="{{ route('about') }}" class="inline-flex items-center justify-center rounded-[999px] border-2 border-primary bg-transparent px-6 py-3 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
                        Nossa Historia
                    </a>
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="320">
                <div class="flex flex-wrap gap-6 pt-4 text-sm font-medium text-neutral-medium">
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary" />
                        <span>Conformidade ANVISA</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary" />
                        <span>Consultoria Tecnica</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <flux:icon name="check-circle" class="size-4 text-primary" />
                        <span>Cobertura Nordeste</span>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>

        <div class="relative h-[400px] w-full flex-1 lg:h-full">
            <x-institutional.reveal :delay="200" class="h-full w-full">
                <div class="relative h-full w-full">
                    <div class="absolute right-0 top-0 h-full w-3/4 rounded-tl-[100px] rounded-br-[40px] bg-bg-accent opacity-50"></div>
                    <div class="absolute bottom-10 left-10 h-24 w-24 rounded-full bg-secondary-light opacity-20"></div>
                    <div class="h-full z-10 overflow-hidden rounded-[32px] border-4 border-white shadow-2xl">
                        <img
                            src="{{ asset('images/institucional/hero-home.png') }}"
                            alt="Equipe tecnica TUPAN em ambiente hospitalar"
                            class="h-full w-full object-cover"
                        />
                        <div class="absolute bottom-6 left-6 right-6 rounded-xl border-l-4 border-primary bg-white/95 p-4 shadow-lg backdrop-blur-sm">
                            <p class="text-sm font-medium text-neutral-strong">"Autoridade nao se declara, se constroi."</p>
                            <p class="mt-1 text-xs uppercase tracking-wider text-neutral-medium">Manifesto TUPAN</p>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    </div>
</x-institutional.section>
