<x-institutional.section id="sobre" variant="white">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Nossa Essência</span>
            <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Feita do Sal da Terra do Sertão</h2>
            <p class="text-lg text-neutral-medium">
                Há 16 anos, construímos nossa reputação com a mesma seriedade que aprendemos no Sertão: onde palavra vale mais que contrato e o compromisso é honrado no aperto de mão. Cada produto que entregamos pode impactar a vida de alguém. É assim que trabalhamos.
            </p>
        </x-institutional.reveal>
    </div>

    <div class="grid gap-8 md:grid-cols-3">
        <x-institutional.reveal :delay="100">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary">
                    <flux:icon name="flag" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossa Missao</h3>
                <p class="text-neutral-medium">
                    Prover soluções técnicas em saúde com conhecimento aplicado, garantindo que cada decisão de compra seja uma decisão mais segura para o paciente, o profissional e a instituição.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>

        <x-institutional.reveal :delay="200">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary">
                    <flux:icon name="eye" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossa Visao</h3>
                <p class="text-neutral-medium">
                    Ser referência em soluções técnicas de saúde em Pernambuco e no Nordeste, reconhecida pela autoridade construída com estudo, responsabilidade e entrega consistente.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>

        <x-institutional.reveal :delay="300">
            <x-institutional.card variant="feature" class="h-full">
                <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-primary-bg text-primary">
                    <flux:icon name="heart" class="size-6" />
                </div>
                <h3 class="mb-3 text-xl font-semibold text-neutral-strong">Nossos Valores</h3>
                <p class="text-neutral-medium">
                    Confiança, sobriedade, solidez, modernidade e tradição em harmonia. Colocamos pessoas acima de números, porque saúde não é lugar para improviso.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>
    </div>
</x-institutional.section>
