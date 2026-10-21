@props([
    'origin' => null,
    'eyebrow' => 'Fale conosco',
    'title' => 'Consultoria técnica para sua necessidade específica.',
    'text' => 'Nossa equipe técnica e comercial está preparada para entender sua demanda e propor a solução mais adequada. Não vendemos apenas produtos. Intermediamos segurança.',
    'whatsappMessage' => null,
    'showChannels' => true,
    'background' => 'white',
])

@php
    $contact = config('institutional.contact');
    $whatsappReady = \App\Domain\Institutional\Services\WhatsappLink::isAvailable();
    $whatsappUrl = $whatsappReady ? \App\Domain\Institutional\Services\WhatsappLink::make($whatsappMessage) : null;
@endphp

<x-institutional.section id="contato" :variant="$background">
    <div @class(['grid gap-12', 'lg:grid-cols-2 lg:gap-20' => $showChannels, 'mx-auto max-w-3xl' => ! $showChannels])>
        <div class="space-y-8">
            <x-institutional.reveal>
                <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">{{ $eyebrow }}</span>
                <h2 class="mb-4 text-3xl font-medium text-neutral-strong md:text-4xl">
                    {{ $title }}
                </h2>
                @if($text !== '')
                    <p class="text-lg text-neutral-medium">
                        {{ $text }}
                    </p>
                @endif
                @if($origin && $whatsappReady)
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener" data-cta="{{ $origin }}-contato-whatsapp" class="inline-flex items-center justify-center gap-2 rounded-[999px] border border-transparent bg-primary-strong px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-primary-strong-hover hover:shadow-md">
                            <flux:icon name="chat-bubble-left-right" class="size-4" />
                            Falar pelo WhatsApp
                        </a>
                    </div>
                @endif
            </x-institutional.reveal>

            @if($showChannels)
            <x-institutional.reveal :delay="200">
                <div class="space-y-6">
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                            <flux:icon name="map-pin" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-neutral-strong">Sede TUPAN</h3>
                            <p class="text-neutral-medium">{{ $contact['city'] ?? 'Recife, Pernambuco' }}</p>
                            <p class="text-neutral-medium">{{ $contact['address'] ?? '' }}</p>
                            <p class="mt-1 text-sm text-neutral-medium">{{ $contact['coverage'] ?? 'Atuação em todo o Nordeste' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                            <flux:icon name="phone" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-neutral-strong">Central de atendimento</h3>
                            <p class="text-neutral-medium"><x-institutional.contact-link type="phone" :value="$contact['phone'] ?? ''" /></p>
                            <p class="mt-1 text-sm text-neutral-medium">{{ $contact['hours'] ?? 'Segunda a sexta, 8h às 18h' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                            <flux:icon name="envelope" class="size-5" />
                        </div>
                        <div class="min-w-0 break-words">
                            <h3 class="font-semibold text-neutral-strong">E-mail</h3>
                            <p class="text-neutral-medium"><x-institutional.contact-link type="email" :value="$contact['emails'][0] ?? 'contato@tupan.com.br'" /></p>
                            <p class="text-neutral-medium"><x-institutional.contact-link type="email" :value="$contact['emails'][1] ?? 'comercial@tupan.com.br'" /></p>
                        </div>
                    </div>
                </div>
            </x-institutional.reveal>
            @endif
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
