@php
    $audiences = config('institutional.target_audience');

    $colorMap = [
        'primary' => [
            'border' => 'border-t-primary',
            'bg' => 'bg-primary-bg text-primary',
            'check' => 'bg-primary text-white',
        ],
        'secondary' => [
            'border' => 'border-t-secondary',
            'bg' => 'bg-secondary-light/20 text-secondary',
            'check' => 'bg-secondary text-white',
        ],
        'accent' => [
            'border' => 'border-t-primary',
            'bg' => 'bg-primary-bg text-primary',
            'check' => 'bg-primary text-white',
        ],
    ];
@endphp

<x-institutional.section variant="white" class="border-t border-neutral-border">
    <div class="mx-auto mb-16 max-w-3xl text-center">
        <x-institutional.reveal>
            <span class="mb-2 block text-xs font-bold uppercase tracking-[0.5px] text-neutral-medium">Para Quem Atuamos</span>
            <h2 class="text-3xl font-medium text-neutral-strong md:text-4xl">Parceiros que Compartilham Nosso Compromisso</h2>
        </x-institutional.reveal>
    </div>

    <div class="mx-auto grid max-w-6xl gap-8 md:grid-cols-3">
        @foreach($audiences as $index => $audience)
            @php
                $colors = $colorMap[$audience['color']] ?? $colorMap['primary'];
            @endphp
            <x-institutional.reveal :delay="$index * 100">
                <x-institutional.card class="h-full border-t-4 {{ $colors['border'] }}" hover-effect>
                    <div class="flex h-full flex-col">
                        <div class="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl {{ $colors['bg'] }}">
                            <flux:icon name="users" class="size-6" />
                        </div>
                        <h3 class="mb-2 text-2xl font-bold text-neutral-strong">{{ $audience['title'] }}</h3>
                        <p class="text-neutral-medium">{{ $audience['description'] }}</p>

                        <div class="mt-auto mt-8 rounded-xl bg-bg-light p-6">
                            <ul class="space-y-3">
                                @foreach($audience['profiles'] as $profile)
                                    <li class="flex items-center gap-3 font-medium text-neutral-strong">
                                        <div class="flex h-5 w-5 items-center justify-center rounded-full text-[10px] {{ $colors['check'] }}">✓</div>
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
