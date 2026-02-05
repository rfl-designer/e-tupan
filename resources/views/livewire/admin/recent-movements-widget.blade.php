<div class="rounded-lg border border-neutral-800 bg-neutral-900">
    {{-- Header --}}
    <div class="flex items-center justify-between border-b border-neutral-800 p-4">
        <div class="flex items-center gap-2">
            <flux:icon name="clipboard-document-list" class="size-5 text-blue-400" />
            <flux:heading size="sm">{{ __('Ultimas Movimentacoes') }}</flux:heading>
        </div>
        <a href="{{ route('admin.inventory.movements') }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
            {{ __('Ver todas') }}
        </a>
    </div>

    {{-- Content --}}
    <div class="p-4">
        @if ($movements->isEmpty())
            <div class="flex flex-col items-center justify-center py-8 text-center">
                <flux:icon name="clipboard-document-list" class="size-10 text-neutral-600 mb-2" />
                <p class="text-sm text-neutral-400">{{ __('Nenhuma movimentacao registrada') }}</p>
            </div>
        @else
            <div class="space-y-3">
                @foreach ($movements as $movement)
                    <div wire:key="movement-{{ $movement->id }}" class="flex items-center gap-3 rounded-lg bg-neutral-800/50 p-3">
                        {{-- Movement Type Badge --}}
                        <div class="flex-shrink-0">
                            <flux:badge size="sm" color="{{ $movement->movement_type->color() }}">
                                {{ $movement->movement_type->label() }}
                            </flux:badge>
                        </div>

                        {{-- Movement Info --}}
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm text-white">
                                {{ $this->getStockableName($movement) }}
                            </p>
                            <div class="flex items-center gap-2 text-xs text-neutral-500">
                                <span>{{ $movement->created_at->format('d/m H:i') }}</span>
                                @if ($movement->creator)
                                    <span>&bull;</span>
                                    <span>{{ $movement->creator->name }}</span>
                                @endif
                            </div>
                        </div>

                        {{-- Quantity --}}
                        <div class="flex-shrink-0 text-right">
                            <span class="text-sm font-medium {{ $movement->quantity > 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $this->formatQuantity($movement->quantity) }}
                            </span>
                            <p class="text-xs text-neutral-500">
                                {{ $movement->quantity_before }} &rarr; {{ $movement->quantity_after }}
                            </p>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
