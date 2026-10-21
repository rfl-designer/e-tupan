@props([
    'origin' => null,
    'eyebrow' => 'Fale conosco',
    'title' => 'Consultoria técnica para sua necessidade específica.',
    'text' => 'Nossa equipe técnica e comercial está preparada para entender sua demanda e propor a solução mais adequada.',
    'whatsappMessage' => null,
    'showChannels' => true,
    'background' => 'white',
    'division' => null,
])

@php
    $contact = config('institutional.contact');
    $divisionContact = $division['contact'] ?? [];
    $phone = $divisionContact['phone'] ?? ($contact['phone'] ?? '');
    $phoneLabel = isset($divisionContact['phone']) ? 'Atendimento '.$division['title'] : 'Central de atendimento';
    $instagram = $divisionContact['instagram'] ?? ($contact['social']['instagram'] ?? null);
    $instagramHandle = $instagram ? '@'.trim((string) parse_url($instagram, PHP_URL_PATH), '/') : null;
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
                            <p class="text-neutral-medium">{{ $contact['address'] ?? '' }}@if(! empty($contact['postal_code'])), <span class="whitespace-nowrap">CEP {{ $contact['postal_code'] }}</span>@endif</p>
                            <p class="mt-1 text-sm text-neutral-medium">{{ $contact['coverage'] ?? 'Atuação em todo o Nordeste' }}</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                            <flux:icon name="phone" class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-neutral-strong">{{ $phoneLabel }}</h3>
                            <p class="text-neutral-medium"><x-institutional.contact-link type="phone" :value="$phone" /></p>
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

                    @if($instagram)
                    <div class="flex items-start gap-4">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect x="3" y="3" width="18" height="18" rx="5" ry="5"></rect>
                                <path d="M16 11.37a4 4 0 1 1-7.87 1.13 4 4 0 0 1 7.87-1.13z"></path>
                                <line x1="17.5" y1="6.5" x2="17.5" y2="6.5"></line>
                            </svg>
                        </div>
                        <div class="min-w-0 break-words">
                            <h3 class="font-semibold text-neutral-strong">Instagram</h3>
                            <p class="text-neutral-medium"><a href="{{ $instagram }}" target="_blank" rel="noopener noreferrer" class="inline-block py-1 text-primary-strong underline decoration-primary-strong/40 underline-offset-4 transition-colors hover:decoration-primary-strong">{{ $instagramHandle }}</a></p>
                        </div>
                    </div>
                    @endif
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
