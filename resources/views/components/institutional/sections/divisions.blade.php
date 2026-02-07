@php
    $divisions = config('institutional.divisions');
    $mainDivisions = array_values(array_filter($divisions, fn ($division) => $division['id'] !== 'equipahosp'));
    $equipahosp = collect($divisions)->firstWhere('id', 'equipahosp');
    $icons = [
        'cirurgica' => 'shield-check',
        'farma' => 'beaker',
        'curativos' => 'heart',
        'lab' => 'beaker',
        'imagem' => 'building-office-2',
        'proprios' => 'archive-box',
        'equipahosp' => 'wrench',
    ];
@endphp

<x-institutional.section id="solucoes" variant="cream">
    <div class="mb-16 flex flex-col items-end justify-between gap-6 md:flex-row">
        <div class="max-w-2xl">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Divisões Especializadas</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Conhecimento Técnico em Cada Segmento</h2>
            </x-institutional.reveal>
        </div>
        <x-institutional.reveal :delay="100">
            <a href="#contato" class="inline-flex items-center justify-center rounded-[999px] border-2 border-primary bg-transparent px-6 py-2 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
                Solicitar Consultoria
            </a>
        </x-institutional.reveal>
    </div>

    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
        @foreach($mainDivisions as $index => $division)
            <x-institutional.reveal :delay="$index * 100">
                <a href="{{ route('solutions.show', $division['id']) }}" class="group block h-full">
                    <x-institutional.card class="relative flex h-full cursor-pointer flex-col overflow-hidden border border-neutral-border bg-white">
                        <div class="absolute right-4 top-4 translate-x-2 opacity-0 transition-all group-hover:translate-x-0 group-hover:opacity-100">
                            <flux:icon name="arrow-right" class="size-5 text-primary" />
                        </div>
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-bg-cream text-primary transition-colors duration-300 group-hover:bg-primary group-hover:text-white">
                            <flux:icon name="{{ $icons[$division['id']] ?? 'sparkles' }}" class="size-6" />
                        </div>
                        <h3 class="mb-3 text-xl font-semibold text-neutral-strong transition-colors group-hover:text-primary">{{ $division['title'] }}</h3>
                        <p class="mb-6 flex-grow text-sm leading-relaxed text-neutral-medium">{{ $division['description'] }}</p>
                        <div class="space-y-2 border-t border-neutral-border pt-4">
                            @foreach(array_slice($division['features'], 0, 3) as $feature)
                                <div class="flex items-center text-xs font-medium text-neutral-medium">
                                    <div class="mr-2 h-1.5 w-1.5 rounded-full bg-primary"></div>
                                    {{ $feature }}
                                </div>
                            @endforeach
                        </div>
                    </x-institutional.card>
                </a>
            </x-institutional.reveal>
        @endforeach
    </div>

    @if($equipahosp)
        <div id="equipahosp" class="mt-16 scroll-mt-32">
            <x-institutional.reveal>
                <div class="relative overflow-hidden rounded-3xl bg-neutral-strong shadow-2xl">
                    <div class="absolute right-0 top-0 h-full w-1/2 translate-x-20 skew-x-12 bg-[#263354]"></div>

                    <div class="relative z-10 flex flex-col items-center gap-10 p-8 lg:flex-row lg:p-12">
                        <div class="flex-1 space-y-6">
                        <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3 py-1">
                            <flux:icon name="wrench" class="size-4 text-secondary-light" />
                            <span class="text-xs font-bold uppercase tracking-wide text-white">Engenharia Clínica Especializada</span>
                        </div>
                        <h3 class="text-3xl font-medium text-white lg:text-4xl">
                            Divisão <span class="text-primary-light">EquipaHosp</span>
                        </h3>
                        <p class="text-lg leading-relaxed text-neutral-light">
                            Saúde não é lugar para improviso. Cada equipamento precisa estar no lugar certo, na hora certa, com o suporte certo. Oferecemos engenharia clínica, manutenção e assistência técnica para equipamentos hospitalares em UTIs, centros cirúrgicos e ambulatórios. É assim que honramos quem está na ponta: o paciente.
                        </p>
                            <div class="flex gap-4 pt-4">
                                <a href="{{ route('solutions.show', 'equipahosp') }}" class="inline-flex items-center justify-center rounded-[999px] border border-transparent bg-primary px-6 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                                    Conhecer a EquipaHosp
                                </a>
                            </div>
                        </div>
                        <div class="w-full flex-1">
                            <div class="grid grid-cols-2 gap-4 rounded-2xl border border-white/10 bg-white/5 p-6 backdrop-blur-sm">
                                <div class="rounded-xl bg-white/5 p-4">
                                    <h4 class="mb-1 text-lg font-bold text-primary-light">Preventiva</h4>
                                    <p class="text-sm text-neutral-light">Antecipamos problemas. Prolongamos a vida útil dos equipamentos.</p>
                                </div>
                                <div class="rounded-xl bg-white/5 p-4">
                                    <h4 class="mb-1 text-lg font-bold text-primary-light">Corretiva</h4>
                                    <p class="text-sm text-neutral-light">Resposta técnica ágil para restabelecer a operação.</p>
                                </div>
                                <div class="rounded-xl bg-white/5 p-4">
                                    <h4 class="mb-1 text-lg font-bold text-primary-light">Calibração</h4>
                                    <p class="text-sm text-neutral-light">Precisão conforme normas técnicas e regulatórias.</p>
                                </div>
                                <div class="rounded-xl bg-white/5 p-4">
                                    <h4 class="mb-1 text-lg font-bold text-primary-light">Eng. Clínica</h4>
                                    <p class="text-sm text-neutral-light">Gestão completa do parque tecnológico hospitalar.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>
    @endif
</x-institutional.section>
