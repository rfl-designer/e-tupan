<div class="space-y-6">
    {{-- Statistics Cards --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
        {{-- Pending Labels --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="document-text" class="size-5 text-amber-400" />
                <span class="text-sm text-neutral-400">{{ __('Etiquetas Pendentes') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['pending_labels'] }}</p>
        </div>

        {{-- In Transit --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="truck" class="size-5 text-sky-400" />
                <span class="text-sm text-neutral-400">{{ __('Em Transito') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['in_transit'] }}</p>
        </div>

        {{-- Delivered Today --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="check-circle" class="size-5 text-green-400" />
                <span class="text-sm text-neutral-400">{{ __('Entregas Hoje') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['delivered_today'] }}</p>
        </div>

        {{-- Delivered This Week --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="calendar" class="size-5 text-blue-400" />
                <span class="text-sm text-neutral-400">{{ __('Esta Semana') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['delivered_this_week'] }}</p>
        </div>

        {{-- Delivered This Month --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="chart-bar" class="size-5 text-indigo-400" />
                <span class="text-sm text-neutral-400">{{ __('Este Mes') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['delivered_this_month'] }}</p>
        </div>

        {{-- Problems --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-2">
                <flux:icon name="exclamation-triangle" class="size-5 text-red-400" />
                <span class="text-sm text-neutral-400">{{ __('Problemas') }}</span>
            </div>
            <p class="mt-2 text-2xl font-bold text-white">{{ $stats['problems'] }}</p>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Pending Shipments (Need Label) --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900">
            <div class="flex items-center justify-between border-b border-neutral-800 p-4">
                <div class="flex items-center gap-2">
                    <flux:icon name="document-text" class="size-5 text-amber-400" />
                    <flux:heading size="sm">{{ __('Aguardando Etiqueta') }}</flux:heading>
                </div>
                @if (Route::has('admin.shipping.shipments'))
                    <a href="{{ route('admin.shipping.shipments', ['status' => 'pending']) }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                        {{ __('Ver todos') }}
                    </a>
                @endif
            </div>

            <div class="p-4">
                @forelse ($pendingShipments as $shipment)
                    <div wire:key="pending-{{ $shipment->id }}" class="flex items-center justify-between rounded-lg bg-neutral-800/50 p-3 mb-3 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">
                                {{ $shipment->order?->order_number ?? __('Pedido #:id', ['id' => $shipment->order_id]) }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ $shipment->carrier_name ?? '-' }} - {{ $shipment->service_name ?? '-' }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ $shipment->created_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="ml-4 text-right">
                            <flux:badge size="sm" :color="$shipment->status->color()">
                                {{ $shipment->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon name="check-circle" class="size-10 text-green-500 mb-2" />
                        <p class="text-sm text-neutral-400">{{ __('Nenhuma etiqueta pendente') }}</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- In Transit Shipments --}}
        <div class="rounded-lg border border-neutral-800 bg-neutral-900">
            <div class="flex items-center justify-between border-b border-neutral-800 p-4">
                <div class="flex items-center gap-2">
                    <flux:icon name="truck" class="size-5 text-sky-400" />
                    <flux:heading size="sm">{{ __('Em Transito') }}</flux:heading>
                </div>
                @if (Route::has('admin.shipping.shipments'))
                    <a href="{{ route('admin.shipping.shipments', ['status' => 'in_transit']) }}" class="text-sm text-blue-400 hover:text-blue-300 transition-colors">
                        {{ __('Ver todos') }}
                    </a>
                @endif
            </div>

            <div class="p-4">
                @forelse ($inTransitShipments as $shipment)
                    <div wire:key="transit-{{ $shipment->id }}" class="flex items-center justify-between rounded-lg bg-neutral-800/50 p-3 mb-3 last:mb-0">
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium text-white">
                                {{ $shipment->order?->order_number ?? __('Pedido #:id', ['id' => $shipment->order_id]) }}
                            </p>
                            <p class="text-xs text-neutral-500">
                                @if ($shipment->tracking_number)
                                    <span class="font-mono">{{ $shipment->tracking_number }}</span>
                                @else
                                    {{ $shipment->carrier_name ?? '-' }}
                                @endif
                            </p>
                            <p class="text-xs text-neutral-500">
                                {{ $shipment->updated_at->diffForHumans() }}
                            </p>
                        </div>
                        <div class="ml-4 text-right">
                            <flux:badge size="sm" :color="$shipment->status->color()">
                                {{ $shipment->status->label() }}
                            </flux:badge>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-8 text-center">
                        <flux:icon name="inbox" class="size-10 text-neutral-500 mb-2" />
                        <p class="text-sm text-neutral-400">{{ __('Nenhum envio em transito') }}</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>
