@props([
    'origin' => null,
    'eyebrow' => 'Fale Conosco',
    'title' => 'Consultoria técnica para sua necessidade específica.',
    'text' => 'Nossa equipe técnica e comercial está preparada para entender sua demanda e propor a solução mais adequada. Não vendemos apenas produtos. Intermediamos segurança.',
    'whatsappMessage' => null,
])

@php
    $contact = config('institutional.contact');
    $whatsappUrl = \App\Domain\Institutional\Services\WhatsappLink::make($whatsappMessage);
@endphp

<x-institutional.section id="contato" variant="white">
    <div class="grid gap-12 lg:grid-cols-2 lg:gap-20">
        <div class="space-y-8">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">{{ $eyebrow }}</span>
                <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">
                    {{ $title }}
                </h2>
                <p class="text-lg text-neutral-medium">
                    {{ $text }}
                </p>
                @if($origin)
                    <div class="flex flex-col gap-3 pt-2 sm:flex-row">
                        <a href="#fale-conosco" data-cta="{{ $origin }}-contato-consultoria" class="inline-flex items-center justify-center rounded-[999px] border-2 border-primary px-6 py-2.5 text-sm font-semibold text-primary transition hover:bg-primary-bg hover:text-primary-hover">
                            Solicitar consultoria técnica
                        </a>
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-contato-whatsapp" class="inline-flex items-center justify-center gap-2 rounded-[999px] border border-transparent bg-primary px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-hover hover:shadow-md">
                            <flux:icon name="chat-bubble-left-right" class="size-4" />
                            Falar pelo WhatsApp
                        </a>
                    </div>
                @endif
            </x-institutional.reveal>

            <x-institutional.reveal :delay="200">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-bg-light text-primary">
                            <flux:icon name="map-pin" class="size-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-neutral-strong">Sede TUPAN</h4>
                            <p class="text-neutral-medium">{{ $contact['city'] ?? 'Recife, Pernambuco' }}</p>
                            <p class="mt-1 text-sm text-neutral-light">{{ $contact['coverage'] ?? 'Atuação em todo o Nordeste' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-bg-light text-primary">
                            <flux:icon name="phone" class="size-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-neutral-strong">Central de Atendimento</h4>
                            <p class="text-neutral-medium">{{ $contact['phone'] ?? '(81) 3333-0000' }}</p>
                            <p class="mt-1 text-sm text-neutral-light">{{ $contact['hours'] ?? 'Segunda a Sexta, 8h às 18h' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-bg-light text-primary">
                            <flux:icon name="envelope" class="size-5" />
                        </div>
                        <div>
                            <h4 class="font-semibold text-neutral-strong">E-mail</h4>
                            <p class="text-neutral-medium">{{ $contact['emails'][0] ?? 'contato@tupan.com.br' }}</p>
                            <p class="text-neutral-medium">{{ $contact['emails'][1] ?? 'comercial@tupan.com.br' }}</p>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
        </div>

        <div id="fale-conosco">
            <x-institutional.reveal :delay="200">
                <x-institutional.card variant="standard" class="p-8">
                    <livewire:institutional.contact-form :origin="$origin" />
                </x-institutional.card>
            </x-institutional.reveal>
        </div>
    </div>
</x-institutional.section>
