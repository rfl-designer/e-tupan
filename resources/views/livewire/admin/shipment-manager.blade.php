<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Envios') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie os envios e etiquetas') }}</flux:subheading>
            </div>
        </div>

        {{-- Bulk Actions --}}
        <div class="flex items-center gap-2">
            @if (count($selected) > 0)
                <flux:button variant="primary" wire:click="generateSelectedLabels">
                    <flux:icon name="printer" class="size-4 mr-1" />
                    {{ __('Gerar Etiquetas') }} ({{ count($selected) }})
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Notification Listener --}}
    <div
        x-data="{
            show: false,
            type: 'success',
            message: ''
        }"
        x-on:notify.window="
            show = true;
            type = $event.detail.type;
            message = $event.detail.message;
            setTimeout(() => show = false, 4000);
        "
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 transform translate-y-2"
        x-transition:enter-end="opacity-100 transform translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 transform translate-y-0"
        x-transition:leave-end="opacity-0 transform translate-y-2"
        x-cloak
        class="fixed bottom-4 right-4 z-50"
    >
        <div
            :class="{
                'bg-green-900/90 border-green-700': type === 'success',
                'bg-red-900/90 border-red-700': type === 'error',
                'bg-amber-900/90 border-amber-700': type === 'warning'
            }"
            class="rounded-lg border px-4 py-3 shadow-lg"
        >
            <div class="flex items-center gap-2">
                <template x-if="type === 'success'">
                    <flux:icon name="check-circle" class="size-5 text-green-400" />
                </template>
                <template x-if="type === 'error'">
                    <flux:icon name="x-circle" class="size-5 text-red-400" />
                </template>
                <template x-if="type === 'warning'">
                    <flux:icon name="exclamation-triangle" class="size-5 text-amber-400" />
                </template>
                <span class="text-sm text-white" x-text="message"></span>
            </div>
        </div>
    </div>

    {{-- URL Opener --}}
    <div
        x-data
        x-on:open-url.window="window.open($event.detail.url, '_blank')"
    ></div>

    {{-- Stats Cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-lg border border-neutral-800 bg-neutral-900/50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>{{ __('Aguardando Etiqueta') }}</flux:subheading>
                    <flux:heading size="xl">{{ $this->stats['awaiting_shipment'] ?? 0 }}</flux:heading>
                </div>
                <flux:icon name="clock" class="size-8 text-amber-500" />
            </div>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-neutral-900/50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>{{ __('Em Transito') }}</flux:subheading>
                    <flux:heading size="xl">{{ $this->stats['in_transit'] ?? 0 }}</flux:heading>
                </div>
                <flux:icon name="truck" class="size-8 text-sky-500" />
            </div>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-neutral-900/50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>{{ __('Entregues Hoje') }}</flux:subheading>
                    <flux:heading size="xl">{{ $this->stats['delivered_today'] ?? 0 }}</flux:heading>
                </div>
                <flux:icon name="check-circle" class="size-8 text-green-500" />
            </div>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-neutral-900/50 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <flux:subheading>{{ __('Atrasados') }}</flux:subheading>
                    <flux:heading size="xl">{{ $this->stats['delayed'] ?? 0 }}</flux:heading>
                </div>
                <flux:icon name="exclamation-triangle" class="size-8 text-red-500" />
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="mb-6 space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            {{-- Search --}}
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="{{ __('Buscar por pedido, rastreio ou destinatario...') }}"
                    icon="magnifying-glass"
                />
            </div>

            {{-- Toggle Filters --}}
            <flux:button
                variant="ghost"
                wire:click="$toggle('showFilters')"
                class="{{ $showFilters ? 'bg-neutral-800' : '' }}"
            >
                <flux:icon name="funnel" class="size-4 mr-1" />
                {{ __('Filtros') }}
                @if ($status || $carrier)
                    <flux:badge size="sm" color="blue" class="ml-1">{{ collect([$status, $carrier])->filter()->count() }}</flux:badge>
                @endif
            </flux:button>
        </div>

        {{-- Filters Panel --}}
        @if ($showFilters)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 p-4 bg-neutral-900/50 rounded-lg border border-neutral-800">
                {{-- Status Filter --}}
                <flux:select wire:model.live="status" label="{{ __('Status') }}">
                    @foreach ($this->statusOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- Carrier Filter --}}
                <flux:select wire:model.live="carrier" label="{{ __('Transportadora') }}">
                    <option value="">{{ __('Todas') }}</option>
                    @foreach ($this->carriers as $c)
                        <option value="{{ $c->carrier_code }}">{{ $c->carrier_name }}</option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Clear Filters --}}
            @if ($status || $carrier)
                <div class="flex justify-end">
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                        <flux:icon name="x-mark" class="size-4 mr-1" />
                        {{ __('Limpar Filtros') }}
                    </flux:button>
                </div>
            @endif
        @endif
    </div>

    {{-- Shipments Table --}}
    <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900/50">
        <table class="min-w-full divide-y divide-neutral-800">
            <thead class="bg-neutral-800/50">
                <tr>
                    <th class="w-10 px-4 py-3">
                        <flux:checkbox wire:model.live="selectAll" />
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('created_at')">
                        <div class="flex items-center gap-1">
                            {{ __('Pedido') }}
                            @if ($sortBy === 'created_at')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">
                        {{ __('Destinatario') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">
                        {{ __('Transportadora') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider">
                        {{ __('Rastreio') }}
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-neutral-400 uppercase tracking-wider cursor-pointer" wire:click="sortBy('status')">
                        <div class="flex items-center gap-1">
                            {{ __('Status') }}
                            @if ($sortBy === 'status')
                                <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-3" />
                            @endif
                        </div>
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-neutral-400 uppercase tracking-wider">
                        {{ __('Acoes') }}
                    </th>
                </tr>
            </thead>
            <tbody class="divide-y divide-neutral-800">
                @forelse ($this->shipments as $shipment)
                    <tr wire:key="shipment-{{ $shipment->id }}" class="hover:bg-neutral-800/30 transition-colors">
                        <td class="px-4 py-3">
                            <flux:checkbox wire:model.live="selected" value="{{ $shipment->id }}" />
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                @if (Route::has('admin.orders.show'))
                                    <a href="{{ route('admin.orders.show', $shipment->order_id) }}" class="font-medium text-white hover:text-blue-400 transition-colors">
                                        {{ $shipment->order?->order_number ?? '-' }}
                                    </a>
                                @else
                                    <span class="font-medium text-white">{{ $shipment->order?->order_number ?? '-' }}</span>
                                @endif
                                <div class="text-xs text-neutral-500">
                                    {{ $shipment->created_at->format('d/m/Y H:i') }}
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div>
                                <div class="text-sm text-white">{{ $shipment->recipient_name }}</div>
                                <div class="text-xs text-neutral-500">{{ $shipment->address_city }}/{{ $shipment->address_state }}</div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm">{{ $shipment->carrier_name }}</div>
                            <div class="text-xs text-neutral-500">{{ $shipment->service_name }}</div>
                        </td>
                        <td class="px-4 py-3">
                            @if ($shipment->tracking_number)
                                <code class="text-xs bg-neutral-800 px-2 py-1 rounded text-green-400">
                                    {{ $shipment->tracking_number }}
                                </code>
                            @else
                                <span class="text-neutral-500">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <flux:badge color="{{ $shipment->status->color() }}" size="sm">
                                {{ $shipment->status->label() }}
                            </flux:badge>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-1">
                                {{-- Generate Label --}}
                                @if ($shipment->canGenerateLabel())
                                    <flux:button variant="ghost" size="sm" wire:click="generateLabel('{{ $shipment->id }}')" title="{{ __('Gerar Etiqueta') }}">
                                        <flux:icon name="document-arrow-down" class="size-4" />
                                    </flux:button>
                                @endif

                                {{-- Print Label --}}
                                @if ($shipment->hasLabel())
                                    <flux:button variant="ghost" size="sm" wire:click="printLabel('{{ $shipment->id }}')" title="{{ __('Imprimir Etiqueta') }}">
                                        <flux:icon name="printer" class="size-4" />
                                    </flux:button>
                                @endif

                                {{-- Mark as Posted --}}
                                @if ($shipment->status === \App\Domain\Shipping\Enums\ShipmentStatus::Generated)
                                    <flux:button variant="ghost" size="sm" wire:click="markAsPosted('{{ $shipment->id }}')" title="{{ __('Marcar como Postado') }}">
                                        <flux:icon name="paper-airplane" class="size-4" />
                                    </flux:button>
                                @endif

                                {{-- Cancel --}}
                                @if ($shipment->canBeCancelled())
                                    <flux:button variant="ghost" size="sm" wire:click="confirmCancel('{{ $shipment->id }}')" title="{{ __('Cancelar') }}" class="text-red-400 hover:text-red-300">
                                        <flux:icon name="x-mark" class="size-4" />
                                    </flux:button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-neutral-500">
                            <flux:icon name="inbox" class="size-12 mx-auto mb-4 text-neutral-600" />
                            <p>{{ __('Nenhum envio encontrado.') }}</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Pagination --}}
    @if ($this->shipments->hasPages())
        <div class="mt-4">
            {{ $this->shipments->links() }}
        </div>
    @endif

    {{-- Cancel Confirmation Modal --}}
    <flux:modal name="confirm-cancel" wire:model="confirmCancelModal">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-shrink-0 bg-red-900/50 rounded-full p-3">
                    <flux:icon name="exclamation-triangle" class="size-6 text-red-400" />
                </div>
                <div>
                    <flux:heading size="lg">{{ __('Cancelar Envio') }}</flux:heading>
                    <flux:subheading>{{ __('Esta acao nao pode ser desfeita.') }}</flux:subheading>
                </div>
            </div>

            <p class="text-neutral-400 mb-6">
                {{ __('Tem certeza que deseja cancelar este envio? Se a etiqueta ja foi gerada, ela sera invalidada na transportadora.') }}
            </p>

            <div class="flex justify-end gap-2">
                <flux:button variant="ghost" wire:click="closeCancelModal">
                    {{ __('Voltar') }}
                </flux:button>
                <flux:button variant="danger" wire:click="cancelShipment">
                    {{ __('Cancelar Envio') }}
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
