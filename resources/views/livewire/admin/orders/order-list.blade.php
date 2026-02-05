<div class="space-y-6">
    {{-- Status Tabs --}}
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="-mb-px flex gap-4 overflow-x-auto" aria-label="Tabs">
            @foreach ($tabs as $tab)
                <button
                    wire:click="setTab('{{ $tab['key'] }}')"
                    wire:key="tab-{{ $tab['key'] }}"
                    class="whitespace-nowrap border-b-2 px-1 py-3 text-sm font-medium transition-colors
                        {{ $activeTab === $tab['key']
                            ? 'border-sky-500 text-sky-600 dark:border-sky-400 dark:text-sky-400'
                            : 'border-transparent text-zinc-500 hover:border-zinc-300 hover:text-zinc-700 dark:text-zinc-400 dark:hover:border-zinc-600 dark:hover:text-zinc-300' }}"
                >
                    {{ $tab['label'] }}
                    <span class="ml-2 rounded-full px-2 py-0.5 text-xs
                        {{ $activeTab === $tab['key']
                            ? 'bg-sky-100 text-sky-600 dark:bg-sky-900/50 dark:text-sky-400'
                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400' }}">
                        {{ $tab['count'] }}
                    </span>
                </button>
            @endforeach
        </nav>
    </div>

    {{-- Filters --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-end gap-4">
            {{-- Search --}}
            <div class="min-w-[200px] flex-1">
                <flux:input
                    wire:model.live.debounce.300ms="search"
                    type="search"
                    placeholder="Buscar pedidos..."
                    icon="magnifying-glass"
                />
            </div>

            {{-- Payment Status Filter --}}
            <div class="w-40">
                <flux:select wire:model.live="paymentStatusFilter" placeholder="Pagamento">
                    <flux:select.option value="">{{ __('Todos') }}</flux:select.option>
                    @foreach ($paymentStatusOptions as $value => $label)
                        <flux:select.option value="{{ $value }}">{{ $label }}</flux:select.option>
                    @endforeach
                </flux:select>
            </div>

            {{-- Date From --}}
            <div class="w-40">
                <flux:input
                    wire:model.live="dateFrom"
                    type="date"
                    label=""
                    placeholder="Data inicial"
                />
            </div>

            {{-- Date To --}}
            <div class="w-40">
                <flux:input
                    wire:model.live="dateTo"
                    type="date"
                    label=""
                    placeholder="Data final"
                />
            </div>

            {{-- Clear Filters --}}
            @if ($search || $paymentStatusFilter || $dateFrom || $dateTo || $activeTab !== 'all')
                <flux:button wire:click="clearFilters" variant="ghost" size="sm">
                    <flux:icon name="x-mark" class="size-4" />
                    {{ __('Limpar') }}
                </flux:button>
            @endif
        </div>
    </div>

    {{-- Batch Actions --}}
    @if (count($selectedOrders) > 0)
        <div class="flex items-center gap-4 rounded-lg border border-sky-200 bg-sky-50 p-4 dark:border-sky-800 dark:bg-sky-900/20">
            <span class="text-sm font-medium text-sky-800 dark:text-sky-300">
                {{ count($selectedOrders) }} {{ __('pedido(s) selecionado(s)') }}
            </span>
            <div class="flex gap-2">
                <flux:button
                    wire:click="batchUpdateStatus('processing')"
                    variant="outline"
                    size="sm"
                >
                    {{ __('Marcar como Processando') }}
                </flux:button>
                <flux:button
                    wire:click="batchUpdateStatus('cancelled')"
                    wire:confirm="{{ __('Tem certeza que deseja cancelar os pedidos selecionados?') }}"
                    variant="danger"
                    size="sm"
                >
                    {{ __('Cancelar Pedidos') }}
                </flux:button>
            </div>
        </div>
    @endif

    {{-- Orders Table --}}
    <div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
        <div class="table-responsive overflow-x-auto scroll-touch">
            <table class="w-full min-w-[800px] text-left text-sm">
                <thead class="border-b border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <flux:checkbox
                                wire:model.live="selectAll"
                            />
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            <button wire:click="sortBy('order_number')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Pedido') }}
                                @if ($sortField === 'order_number')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Cliente') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            <button wire:click="sortBy('total')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Total') }}
                                @if ($sortField === 'total')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Status') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Pagamento') }}
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            <button wire:click="sortBy('placed_at')" class="flex items-center gap-1 hover:text-zinc-900 dark:hover:text-white">
                                {{ __('Data') }}
                                @if ($sortField === 'placed_at')
                                    <flux:icon name="{{ $sortDirection === 'asc' ? 'chevron-up' : 'chevron-down' }}" class="size-4" />
                                @endif
                            </button>
                        </th>
                        <th class="px-4 py-3 font-medium text-zinc-700 dark:text-zinc-300">
                            {{ __('Acoes') }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($orders as $order)
                        <tr wire:key="order-{{ $order->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <flux:checkbox
                                    wire:model.live="selectedOrders"
                                    value="{{ $order->id }}"
                                />
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.orders.show', $order) }}" class="font-medium text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-300">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-col">
                                    <span class="font-medium text-zinc-900 dark:text-white">
                                        {{ $order->customer_name ?? '-' }}
                                    </span>
                                    <span class="text-xs text-zinc-500">
                                        {{ $order->customer_email ?? '-' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white">
                                R$ {{ number_format($order->total / 100, 2, ',', '.') }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $order->status->color() }}" size="sm">
                                    {{ $order->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $order->payment_status->color() }}" size="sm">
                                    {{ $order->payment_status->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                {{ $order->placed_at?->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:button href="{{ route('admin.orders.show', $order) }}" variant="ghost" size="xs" class="touch-target">
                                        <flux:icon name="eye" class="size-4" />
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <flux:icon name="inbox" class="size-12 text-zinc-300 dark:text-zinc-600" />
                                    <p class="mt-4 text-zinc-500">{{ __('Nenhum pedido encontrado') }}</p>
                                    @if ($search || $paymentStatusFilter || $dateFrom || $dateTo || $activeTab !== 'all')
                                        <flux:button wire:click="clearFilters" variant="ghost" size="sm" class="mt-2">
                                            {{ __('Limpar filtros') }}
                                        </flux:button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($orders->hasPages())
            <div class="border-t border-zinc-200 px-4 py-3 dark:border-zinc-700">
                {{ $orders->links() }}
            </div>
        @endif
    </div>
</div>
