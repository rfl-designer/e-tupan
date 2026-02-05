<x-institutional.section id="sobre" variant="white">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Nossa Essencia</span>
            <h2 class="mb-6 text-3xl font-medium text-neutral-strong md:text-4xl">Feita do Sal da Terra do Sertao</h2>
            <p class="text-lg text-neutral-medium">
                Ha 16 anos, construimos nossa reputacao com a mesma seriedade que aprendemos no Sertao: onde palavra vale mais que contrato e o compromisso e honrado no aperto de mao. Cada produto que entregamos pode impactar a vida de alguem. E assim que trabalhamos.
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
                    Prover solucoes tecnicas em saude com conhecimento aplicado, garantindo que cada decisao de compra seja uma decisao mais segura para o paciente, o profissional e a instituicao.
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
                    Ser referencia em solucoes tecnicas de saude em Pernambuco e no Nordeste, reconhecida pela autoridade construida com estudo, responsabilidade e entrega consistente.
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
                    Confianca, sobriedade, solidez, modernidade e tradicao em harmonia. Colocamos pessoas acima de numeros, porque saude nao e lugar para improviso.
                </p>
            </x-institutional.card>
        </x-institutional.reveal>
    </div>
</x-institutional.section>
