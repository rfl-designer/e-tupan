@php
    $contact = config('institutional.contact');
@endphp

<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary-strong opacity-10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-tr-full bg-secondary opacity-20"></div>

        <div class="container relative z-10 mx-auto grid max-w-7xl items-center gap-12 px-6 lg:grid-cols-2 lg:gap-16">
            <x-institutional.reveal>
                <span class="mb-4 block text-xs font-bold uppercase tracking-wider text-primary-light">Fale conosco</span>
                <h1 class="mb-6 text-4xl font-medium md:text-5xl">
                    Vamos conversar sobre <br />
                    <span class="text-primary-light">sua necessidade.</span>
                </h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light">
                    Cada demanda em saúde é única. Nossa equipe técnica e comercial está preparada para ouvir, entender e propor a solução mais adequada para sua instituição.
                </p>
            </x-institutional.reveal>

            <div class="hidden lg:block">
                <h2 class="sr-only">Canais de atendimento</h2>
                <x-institutional.contact-channels class="flex flex-col gap-4" compact />
            </div>
        </div>
    </div>

    <x-institutional.section variant="light" class="pb-0 lg:hidden">
        <h2 class="sr-only">Canais de atendimento</h2>
        <x-institutional.contact-channels class="relative z-20 -mt-32 grid grid-cols-1 gap-6 md:grid-cols-2 md:[&>*:last-child:nth-child(odd)]:col-span-2" />
    </x-institutional.section>

    <div class="bg-bg-light">
        <x-institutional.sections.contact eyebrow="Formulário" title="Envie sua solicitação" text="Respondemos em até 2 dias. Seus dados são usados só para retornar esta solicitação." :show-channels="false" />
    </div>
</div>
