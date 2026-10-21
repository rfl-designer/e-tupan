@php
    $contact = config('institutional.contact');
@endphp

<div>
    <div class="relative overflow-hidden bg-bg-dark pb-20 pt-40 text-white">
        <div class="absolute right-0 top-0 h-full w-1/2 rounded-l-[100px] bg-primary-strong opacity-10"></div>
        <div class="absolute bottom-0 left-0 h-32 w-32 rounded-tr-full bg-secondary opacity-20"></div>

        <div class="container mx-auto max-w-7xl px-6 relative z-10">
            <x-institutional.reveal>
                <span class="mb-4 block text-xs font-bold uppercase tracking-wider text-primary-light">Fale Conosco</span>
                <h1 class="mb-6 text-4xl font-medium md:text-5xl lg:text-6xl">
                    Vamos conversar sobre <br />
                    <span class="text-primary-light">sua necessidade.</span>
                </h1>
                <p class="max-w-2xl text-xl leading-relaxed text-neutral-light">
                    Cada demanda em saúde é única. Nossa equipe técnica e comercial está preparada para ouvir, entender e propor a solução mais adequada para sua instituição.
                </p>
            </x-institutional.reveal>
        </div>
    </div>

    <x-institutional.section variant="light" class="pb-0">
        <h2 class="sr-only">Canais de atendimento</h2>
        <div class="relative z-20 -mt-32 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
            <x-institutional.reveal :delay="100">
                <div class="h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white p-8 shadow-xl">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                        <flux:icon name="phone" class="size-6" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">Central de Atendimento</h3>
                    <p class="text-neutral-medium"><x-institutional.contact-link type="phone" :value="$contact['phone'] ?? ''" /></p>
                    <p class="mt-2 text-sm text-neutral-medium">{{ $contact['hours'] ?? 'Segunda a Sexta, 8h às 18h' }}</p>
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="200">
                <div class="h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white p-8 shadow-xl">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                        <flux:icon name="envelope" class="size-6" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">E-mails</h3>
                    @foreach($contact['emails'] ?? [] as $email)
                        <p class="break-words text-neutral-medium" wire:key="contact-email-{{ $loop->index }}"><x-institutional.contact-link type="email" :value="$email" /></p>
                    @endforeach
                </div>
            </x-institutional.reveal>

            <x-institutional.reveal :delay="300">
                <div class="h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white p-8 shadow-xl">
                    <div class="mb-6 flex h-12 w-12 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                        <flux:icon name="map-pin" class="size-6" />
                    </div>
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">Localização</h3>
                    <p class="text-neutral-medium">{{ $contact['city'] ?? 'Recife, Pernambuco' }}</p>
                    <p class="mt-2 text-sm text-neutral-medium">{{ $contact['coverage'] ?? 'Atuação em todo o Nordeste' }}</p>
                </div>
            </x-institutional.reveal>
        </div>
    </x-institutional.section>

    <div class="bg-bg-light">
        <x-institutional.sections.contact eyebrow="Formulário" title="Envie sua solicitação" text="" :show-channels="false" />
    </div>
</div>
