<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="border-b border-zinc-200 px-4 py-3 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Timeline') }}</flux:heading>
    </div>
    <div class="p-4">
        <div class="relative space-y-4">
            @foreach ($timelineEvents as $index => $event)
                <div wire:key="event-{{ $index }}" class="flex items-start gap-3">
                    {{-- Icon --}}
                    <div class="relative">
                        @if ($event['completed'])
                            <div class="flex size-8 items-center justify-center rounded-full bg-{{ $event['color'] }}-100 dark:bg-{{ $event['color'] }}-900/30">
                                <flux:icon name="{{ $event['icon'] }}" class="size-4 text-{{ $event['color'] }}-600 dark:text-{{ $event['color'] }}-400" />
                            </div>
                        @else
                            <div class="flex size-8 items-center justify-center rounded-full border-2 border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                                <flux:icon name="{{ $event['icon'] }}" class="size-4 text-zinc-400" />
                            </div>
                        @endif
                        {{-- Connector line --}}
                        @if (!$loop->last)
                            <div class="absolute left-4 top-8 h-full w-px -translate-x-1/2 bg-zinc-200 dark:bg-zinc-700"></div>
                        @endif
                    </div>
                    {{-- Content --}}
                    <div class="flex-1 pb-4">
                        <p class="font-medium {{ $event['completed'] ? 'text-zinc-900 dark:text-white' : 'text-zinc-400' }}">
                            {{ $event['label'] }}
                        </p>
                        @if ($event['date'])
                            <p class="text-sm text-zinc-500">
                                {{ $event['date']->format('d/m/Y H:i') }}
                            </p>
                        @else
                            <p class="text-sm text-zinc-400">{{ __('Aguardando') }}</p>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
