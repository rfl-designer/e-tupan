<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400 mb-2">
            <a href="{{ route('customer.dashboard') }}" class="hover:text-zinc-700 dark:hover:text-zinc-200" wire:navigate>
                {{ __('Minha Conta') }}
            </a>
            <flux:icon name="chevron-right" class="size-4" />
            <span>{{ __('Meus Pedidos') }}</span>
        </div>
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Meus Pedidos') }}</flux:heading>
                <flux:text class="mt-1 text-zinc-500 dark:text-zinc-400">
                    {{ __('Acompanhe o status dos seus pedidos') }}
                </flux:text>
            </div>
            <div class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $ordersCount }} {{ $ordersCount === 1 ? 'pedido' : 'pedidos' }}
            </div>
        </div>
    </div>

    {{-- Search and Filter --}}
    <div class="mb-6 space-y-4">
        {{-- Search Input --}}
        <div class="relative">
            <flux:icon name="magnifying-glass" class="pointer-events-none absolute left-3 top-1/2 size-5 -translate-y-1/2 text-zinc-400" />
            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                placeholder="{{ __('Buscar por numero do pedido...') }}"
                class="w-full rounded-lg border border-zinc-200 bg-white py-2.5 pl-10 pr-10 text-sm text-zinc-900 placeholder-zinc-400 focus:border-zinc-400 focus:outline-none focus:ring-1 focus:ring-zinc-400 dark:border-zinc-700 dark:bg-zinc-900 dark:text-white dark:placeholder-zinc-500 dark:focus:border-zinc-500 dark:focus:ring-zinc-500"
            />
            {{-- Loading indicator for search --}}
            <div wire:loading wire:target="search" class="absolute right-3 top-1/2 -translate-y-1/2">
                <flux:icon name="arrow-path" class="size-5 animate-spin text-zinc-400" />
            </div>
            @if($search !== '')
                <button
                    type="button"
                    wire:click="clearSearch"
                    wire:loading.remove
                    wire:target="search"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300"
                >
                    <flux:icon name="x-mark" class="size-5" />
                </button>
            @endif
        </div>

        {{-- Filter Tabs with horizontal scroll on mobile --}}
        <div class="-mx-4 px-4 sm:mx-0 sm:px-0">
            <div class="flex gap-2 overflow-x-auto pb-2 scrollbar-hide sm:flex-wrap sm:overflow-visible sm:pb-0">
                @foreach($statusFilters as $value => $label)
                    <button
                        wire:click="$set('status', '{{ $value }}')"
                        wire:key="filter-{{ $value ?: 'all' }}"
                        class="shrink-0 rounded-lg px-4 py-2 text-sm font-medium transition-colors focus:outline-none focus:ring-2 focus:ring-zinc-400 focus:ring-offset-2 dark:focus:ring-offset-zinc-900 {{ $status === $value ? 'bg-zinc-900 text-white dark:bg-white dark:text-zinc-900' : 'bg-zinc-100 text-zinc-700 hover:bg-zinc-200 dark:bg-zinc-800 dark:text-zinc-300 dark:hover:bg-zinc-700' }}"
                    >
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Skeleton Loading --}}
    <div wire:loading.flex wire:target="status, search, gotoPage, nextPage, previousPage" class="hidden flex-col gap-4">
        @for($i = 0; $i < 3; $i++)
            <div class="animate-pulse rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900 sm:p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="flex-1 space-y-3">
                        <div class="flex items-center gap-3">
                            <div class="h-5 w-28 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                            <div class="h-5 w-20 rounded-full bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                        <div class="flex gap-4">
                            <div class="h-4 w-24 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                            <div class="h-4 w-16 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                        <div class="flex items-center gap-3 pt-2">
                            <div class="flex -space-x-2">
                                <div class="size-10 rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                                <div class="size-10 rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                                <div class="size-10 rounded-lg bg-zinc-200 dark:bg-zinc-700"></div>
                            </div>
                            <div class="h-4 flex-1 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                    </div>
                    <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end">
                        <div class="space-y-1 text-right">
                            <div class="h-3 w-10 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                            <div class="h-6 w-24 rounded bg-zinc-200 dark:bg-zinc-700"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    </div>

    {{-- Orders List --}}
    <div wire:loading.class="opacity-50 pointer-events-none" wire:target="status, search, gotoPage, nextPage, previousPage">
        @if($orders->isEmpty())
            {{-- Empty State --}}
            <div class="rounded-lg border border-zinc-200 bg-white p-8 text-center dark:border-zinc-700 dark:bg-zinc-900 sm:p-12">
                <div class="mx-auto mb-4 flex size-16 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="{{ $hasAnyOrders ? 'magnifying-glass' : 'shopping-bag' }}" class="size-8 text-zinc-400" />
                </div>
                <flux:heading size="lg" class="mb-2">{{ __('Nenhum pedido encontrado') }}</flux:heading>
                @if($hasAnyOrders)
                    {{-- Search/filter has no results --}}
                    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
                        {{ __('Tente buscar por outro numero ou ajuste os filtros.') }}
                    </flux:text>
                    <flux:button wire:click="clearSearch" variant="primary" icon="arrow-path">
                        {{ __('Limpar busca') }}
                    </flux:button>
                @else
                    {{-- User has no orders at all --}}
                    <flux:text class="mb-6 text-zinc-500 dark:text-zinc-400">
                        {{ __('Voce ainda nao realizou nenhum pedido. Que tal explorar nossos produtos?') }}
                    </flux:text>
                    <flux:button href="{{ route('products.index') }}" variant="primary" icon="shopping-cart" wire:navigate>
                        {{ __('Ir as Compras') }}
                    </flux:button>
                @endif
            </div>
        @else
        {{-- Orders Table/Cards --}}
        <div class="space-y-4">
            @foreach($orders as $order)
                <a
                    href="{{ route('customer.orders.show', $order) }}"
                    wire:key="order-{{ $order->id }}"
                    wire:navigate
                    class="block rounded-lg border border-zinc-200 bg-white p-4 transition-shadow hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900 sm:p-6"
                >
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        {{-- Order Info --}}
                        <div class="flex-1">
                            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                                <span class="font-mono text-sm font-semibold text-zinc-900 dark:text-white">
                                    {{ $order->order_number }}
                                </span>
                                <flux:badge :color="$order->status->color()" size="sm">
                                    <flux:icon :name="$order->status->icon()" class="size-3" />
                                    {{ $order->status->label() }}
                                </flux:badge>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
                                <span class="flex items-center gap-1">
                                    <flux:icon name="calendar" class="size-4" />
                                    {{ $order->placed_at->format('d/m/Y') }}
                                </span>
                                <span class="flex items-center gap-1">
                                    <flux:icon name="clock" class="size-4" />
                                    {{ $order->placed_at->format('H:i') }}
                                </span>
                            </div>

                            @if($order->tracking_number && in_array($order->status, [\App\Domain\Checkout\Enums\OrderStatus::Shipped, \App\Domain\Checkout\Enums\OrderStatus::Completed]))
                                <div class="mt-3 flex items-center gap-2">
                                    <flux:icon name="map-pin" class="size-4 text-indigo-500" />
                                    <span class="text-sm text-zinc-600 dark:text-zinc-300">
                                        {{ $order->tracking_number }}
                                    </span>
                                    <span class="inline-flex items-center gap-1 text-xs text-indigo-600 dark:text-indigo-400">
                                        <flux:icon name="arrow-top-right-on-square" class="size-3" />
                                        {{ __('Rastrear') }}
                                    </span>
                                </div>
                            @endif

                            {{-- Items Preview --}}
                            @if($order->items->count() > 0)
                                <div class="mt-4 flex items-center gap-3">
                                    {{-- Product Thumbnails --}}
                                    <div class="flex -space-x-2">
                                        @foreach($order->items->take(3) as $item)
                                            @php
                                                $image = $item->product?->primaryImage();
                                            @endphp
                                            <div class="relative size-10 overflow-hidden rounded-lg border-2 border-white bg-zinc-100 dark:border-zinc-800 dark:bg-zinc-700">
                                                @if($image)
                                                    <img
                                                        src="{{ $image->getUrl() }}"
                                                        alt="{{ $item->product_name }}"
                                                        class="size-full object-cover"
                                                    />
                                                @else
                                                    <div class="flex size-full items-center justify-center">
                                                        <flux:icon name="cube" class="size-5 text-zinc-400" />
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>

                                    {{-- Product Names --}}
                                    <div class="flex-1 min-w-0">
                                        <p class="truncate text-sm text-zinc-600 dark:text-zinc-300">
                                            {{ $order->items->take(3)->pluck('product_name')->join(', ') }}
                                        </p>
                                        @if($order->items->count() > 3)
                                            <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                                +{{ $order->items->count() - 3 }} {{ $order->items->count() - 3 === 1 ? 'item' : 'itens' }}
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Order Total --}}
                        <div class="flex items-center justify-between gap-4 sm:flex-col sm:items-end sm:justify-start">
                            <div class="text-right">
                                <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Total') }}</p>
                                <p class="text-lg font-bold text-zinc-900 dark:text-white">
                                    R$ {{ number_format($order->total_in_reais, 2, ',', '.') }}
                                </p>
                            </div>
                            <flux:icon name="chevron-right" class="hidden size-5 text-zinc-400 sm:block" />
                        </div>
                    </div>
                </a>
            @endforeach
        </div>

            {{-- Pagination --}}
            @if($orders->hasPages())
                <div class="mt-6">
                    {{ $orders->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
