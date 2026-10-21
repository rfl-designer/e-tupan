<x-institutional.section id="sobre" variant="white">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Nossa essência</span>
            <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Feita do sal da terra do Sertão</h2>
            <p class="text-lg text-neutral-medium">
                Crescemos em uma região onde o compromisso se resolve olhando nos olhos. Esse jeito de trabalhar orienta cada produto e cada serviço que entregamos, porque do outro lado sempre há alguém que depende de uma decisão bem feita.
            </p>
        </x-institutional.reveal>
    </div>

    <div class="grid gap-8 md:grid-cols-2 lg:grid-cols-3 md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
        <x-institutional.reveal :delay="100">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary-strong">
                    <flux:icon name="flag" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossa missão</h3>
                <p class="text-neutral-medium">
                    Prover soluções técnicas em saúde com conhecimento aplicado, garantindo que cada decisão de compra seja uma decisão mais segura para o paciente, o profissional e a instituição.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>

        <x-institutional.reveal :delay="200">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary-strong">
                    <flux:icon name="eye" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossa visão</h3>
                <p class="text-neutral-medium">
                    Ser referência em soluções técnicas de saúde em Pernambuco e no Nordeste, reconhecida pela autoridade construída com estudo, responsabilidade e entrega consistente.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>

        <x-institutional.reveal :delay="300">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary-strong">
                    <flux:icon name="heart" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossos valores</h3>
                <p class="text-neutral-medium">
                    Confiança, sobriedade, solidez, modernidade e tradição em harmonia. Colocamos pessoas acima de números, porque saúde não é lugar para improviso.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>
    </div>
</x-institutional.section>
