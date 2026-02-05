<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Ultimos Pedidos') }}</flux:heading>
        <flux:button variant="ghost" size="sm" :href="route('admin.orders.index')">
            {{ __('Ver todos') }}
            <flux:icon name="arrow-right" class="size-4 ml-1" />
        </flux:button>
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse($this->orders as $order)
            <a
                href="{{ route('admin.orders.show', $order) }}"
                wire:key="order-{{ $order->id }}"
                class="flex items-center justify-between p-4 hover:bg-zinc-50 dark:hover:bg-zinc-800 transition-colors"
            >
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <div class="flex h-10 w-10 items-center justify-center rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <flux:icon name="shopping-bag" class="size-5 text-zinc-500" />
                        </div>
                    </div>
                    <div>
                        <flux:text class="font-medium">{{ $order->order_number }}</flux:text>
                        <flux:text class="text-xs text-zinc-500">{{ $order->customer_name ?? $order->guest_email }}</flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <flux:text class="font-medium">R$ {{ number_format($order->total / 100, 2, ',', '.') }}</flux:text>
                    <div class="mt-1">
                        <flux:badge size="sm" :color="$order->status->color()">
                            {{ $order->status->label() }}
                        </flux:badge>
                    </div>
                </div>
            </a>
        @empty
            <div class="p-8 text-center">
                <flux:icon name="inbox" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500">{{ __('Nenhum pedido ainda') }}</flux:text>
            </div>
        @endforelse
    </div>
</div>
