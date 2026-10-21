@php
    $audiences = config('institutional.target_audience');

    $colorMap = [
        'primary' => [
            'border' => 'border-t-primary-strong',
            'bg' => 'bg-primary-bg text-primary-strong',
            'check' => 'bg-primary-strong text-white',
        ],
        'secondary' => [
            'border' => 'border-t-primary-strong',
            'bg' => 'bg-primary-bg text-primary-strong',
            'check' => 'bg-primary-strong text-white',
        ],
        'accent' => [
            'border' => 'border-t-primary-strong',
            'bg' => 'bg-primary-bg text-primary-strong',
            'check' => 'bg-primary-strong text-white',
        ],
    ];
@endphp

<x-institutional.section variant="white" class="border-t border-neutral-border">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Quem atendemos</span>
            <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Para quem trabalhamos</h2>
        </x-institutional.reveal>
    </div>

    <div class="mx-auto grid max-w-6xl grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 md:[&>*:last-child:nth-child(odd)]:col-span-2 lg:[&>*:last-child:nth-child(odd)]:col-span-1">
        @foreach($audiences as $index => $audience)
            @php
                $colors = $colorMap[$audience['color']] ?? $colorMap['primary'];
            @endphp
            <x-institutional.reveal :delay="$index * 100">
                <x-institutional.card class="h-full border-t-4 {{ $colors['border'] }}" :hover-effect="false">
                    <div class="flex h-full flex-col">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl {{ $colors['bg'] }}">
                            <flux:icon name="users" class="size-6" />
                        </div>
                        <h3 class="mb-2 text-2xl font-bold text-neutral-strong">{{ $audience['title'] }}</h3>
                        <p class="text-neutral-medium">{{ $audience['description'] }}</p>

                        <div class="mt-auto mt-8 rounded-xl bg-bg-light p-6">
                            <ul class="space-y-3">
                                @foreach($audience['profiles'] as $profile)
                                    <li class="flex min-w-0 items-center gap-3 break-words font-medium text-neutral-strong">
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] {{ $colors['check'] }}">✓</div>
                                        {{ $profile }}
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </x-institutional.card>
            </x-institutional.reveal>
        @endforeach
    </div>
</x-institutional.section>
