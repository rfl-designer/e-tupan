<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.inventory.index') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Historico de Movimentacoes') }}</flux:heading>
                <flux:subheading>{{ __('Visualize todas as movimentacoes de estoque') }}</flux:subheading>
            </div>
        </div>

        {{-- Export Button --}}
        <flux:button wire:click="exportCsv" variant="ghost">
            <flux:icon name="arrow-down-tray" class="size-4 mr-1" />
            {{ __('Exportar CSV') }}
        </flux:button>
    </div>

    {{-- Search and Filters --}}
    <div class="mb-6 space-y-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
            {{-- Search --}}
            <div class="flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="searchSku"
                    type="search"
                    placeholder="{{ __('Buscar por SKU ou nome do produto...') }}"
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
                @php
                    $activeFilters = collect([$movementType, $createdBy, $dateFrom, $dateTo])->filter()->count();
                @endphp
                @if ($activeFilters > 0)
                    <flux:badge size="sm" color="blue" class="ml-1">{{ $activeFilters }}</flux:badge>
                @endif
            </flux:button>
        </div>

        {{-- Filters Panel --}}
        @if ($showFilters)
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4 p-4 bg-neutral-900/50 rounded-lg border border-neutral-800">
                {{-- Movement Type Filter --}}
                <flux:select wire:model.live="movementType" label="{{ __('Tipo de Movimentacao') }}">
                    <option value="">{{ __('Todos os tipos') }}</option>
                    @foreach ($movementTypeOptions as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </flux:select>

                {{-- User Filter --}}
                <flux:select wire:model.live="createdBy" label="{{ __('Usuario') }}">
                    <option value="">{{ __('Todos os usuarios') }}</option>
                    @foreach ($admins as $admin)
                        <option value="{{ $admin->id }}">{{ $admin->name }}</option>
                    @endforeach
                </flux:select>

                {{-- Date From --}}
                <flux:input
                    wire:model.live="dateFrom"
                    type="date"
                    label="{{ __('Data Inicial') }}"
                />

                {{-- Date To --}}
                <flux:input
                    wire:model.live="dateTo"
                    type="date"
                    label="{{ __('Data Final') }}"
                />

                {{-- Clear Filters --}}
                <div class="sm:col-span-2 lg:col-span-4 flex justify-end">
                    <flux:button variant="ghost" size="sm" wire:click="clearFilters">
                        <flux:icon name="x-mark" class="size-4 mr-1" />
                        {{ __('Limpar Filtros') }}
                    </flux:button>
                </div>
            </div>
        @endif
    </div>

    {{-- Movements Table --}}
    <div class="overflow-hidden rounded-lg border border-neutral-800 bg-neutral-900">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-neutral-800 bg-neutral-900/50">
                    <tr>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Data/Hora') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('SKU') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Produto') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Tipo') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Qtd') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Anterior') }}</th>
                        <th class="px-4 py-4 text-center text-sm font-medium text-neutral-400">{{ __('Posterior') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Usuario') }}</th>
                        <th class="px-4 py-4 text-left text-sm font-medium text-neutral-400">{{ __('Observacao') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-neutral-800">
                    @forelse ($movements as $movement)
                        <tr wire:key="movement-{{ $movement->id }}" class="hover:bg-neutral-800/50 transition-colors">
                            <td class="px-4 py-4 text-sm text-neutral-300 whitespace-nowrap">
                                {{ $movement->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-300">
                                {{ $this->getStockableSku($movement) }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="text-sm text-white">{{ $this->getStockableName($movement) }}</span>
                            </td>
                            <td class="px-4 py-4 text-center">
                                @php
                                    $color = $movement->movement_type->color();
                                @endphp
                                <flux:badge size="sm" color="{{ $color }}">
                                    {{ $movement->movement_type->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-4 text-center">
                                <span class="text-sm font-medium {{ $movement->quantity > 0 ? 'text-green-400' : 'text-red-400' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-neutral-400">
                                {{ $movement->quantity_before }}
                            </td>
                            <td class="px-4 py-4 text-center text-sm text-white font-medium">
                                {{ $movement->quantity_after }}
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-300">
                                {{ $movement->creator?->name ?? 'Sistema' }}
                            </td>
                            <td class="px-4 py-4 text-sm text-neutral-400 max-w-xs truncate" title="{{ $movement->notes }}">
                                {{ $movement->notes ?? '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center">
                                <flux:icon name="clipboard-document-list" class="mx-auto size-12 mb-4 text-neutral-600" />
                                <p class="text-lg font-medium text-neutral-400">{{ __('Nenhuma movimentacao encontrada') }}</p>
                                @if ($searchSku || $movementType || $createdBy || $dateFrom || $dateTo)
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Tente ajustar os filtros de busca.') }}</p>
                                    <flux:button variant="ghost" size="sm" wire:click="clearFilters" class="mt-4">
                                        {{ __('Limpar Filtros') }}
                                    </flux:button>
                                @else
                                    <p class="mt-1 text-sm text-neutral-500">{{ __('Nenhuma movimentacao de estoque registrada.') }}</p>
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($movements->hasPages())
            <div class="border-t border-neutral-800 px-6 py-4">
                {{ $movements->links() }}
            </div>
        @endif
    </div>

    {{-- Back to Stock List --}}
    <div class="mt-6">
        <a href="{{ route('admin.inventory.index') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
            &larr; {{ __('Voltar para Estoque') }}
        </a>
    </div>
</div>
