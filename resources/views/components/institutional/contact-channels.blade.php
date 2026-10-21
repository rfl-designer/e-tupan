@props([
    'compact' => false,
])

@php
    $contact = config('institutional.contact');
@endphp

<div {{ $attributes }}>
    <x-institutional.reveal :delay="100">
                <div @class(['h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white shadow-xl', 'p-8' => ! $compact, 'flex items-start gap-4 p-5' => $compact])>
                    <div @class(['flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong', 'mb-6' => ! $compact])>
                        <flux:icon name="phone" class="size-6" />
                    </div>
                    <div class="min-w-0">
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">Central de atendimento</h3>
                    <p class="text-neutral-medium"><x-institutional.contact-link type="phone" :value="$contact['phone'] ?? ''" /></p>
                    <p class="mt-2 text-sm text-neutral-medium">{{ $contact['hours'] ?? 'Segunda a sexta, 8h às 18h' }}</p>
                    </div>
                </div>
    </x-institutional.reveal>
    <x-institutional.reveal :delay="200">
                <div @class(['h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white shadow-xl', 'p-8' => ! $compact, 'flex items-start gap-4 p-5' => $compact])>
                    <div @class(['flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong', 'mb-6' => ! $compact])>
                        <flux:icon name="envelope" class="size-6" />
                    </div>
                    <div class="min-w-0">
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">E-mails</h3>
                    @foreach($contact['emails'] ?? [] as $email)
                        <p class="break-words text-neutral-medium" wire:key="contact-email-{{ $loop->index }}"><x-institutional.contact-link type="email" :value="$email" /></p>
                    @endforeach
                    </div>
                </div>
    </x-institutional.reveal>
    <x-institutional.reveal :delay="300">
                <div @class(['h-full min-w-0 rounded-2xl border-t-4 border-primary-strong bg-white shadow-xl', 'p-8' => ! $compact, 'flex items-start gap-4 p-5' => $compact])>
                    <div @class(['flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-bg-light text-primary-strong', 'mb-6' => ! $compact])>
                        <flux:icon name="map-pin" class="size-6" />
                    </div>
                    <div class="min-w-0">
                    <h3 class="mb-2 text-xl font-semibold text-neutral-strong">Localização</h3>
                    <p class="text-neutral-medium">{{ $contact['city'] ?? 'Recife, Pernambuco' }}</p>
                    <p class="mt-2 text-sm text-neutral-medium">{{ $contact['coverage'] ?? 'Atuação em todo o Nordeste' }}</p>
                    </div>
                </div>
    </x-institutional.reveal>
</div>
