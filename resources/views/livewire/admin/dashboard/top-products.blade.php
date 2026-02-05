<div class="rounded-lg border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
        <flux:heading size="lg">{{ __('Produtos Mais Vendidos') }}</flux:heading>
        <div class="flex gap-2">
            <flux:button
                size="sm"
                :variant="$period === 'week' ? 'primary' : 'ghost'"
                wire:click="setPeriod('week')"
            >
                Semana
            </flux:button>
            <flux:button
                size="sm"
                :variant="$period === 'month' ? 'primary' : 'ghost'"
                wire:click="setPeriod('month')"
            >
                Mes
            </flux:button>
        </div>
    </div>

    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
        @forelse($this->products as $index => $item)
            <div
                wire:key="product-{{ $item['product']?->id ?? $index }}"
                class="flex items-center justify-between p-4"
            >
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0 w-8 text-center">
                        <flux:text class="font-bold text-zinc-400">#{{ $index + 1 }}</flux:text>
                    </div>
                    <div>
                        <flux:text class="font-medium">{{ $item['product']?->name ?? 'Produto removido' }}</flux:text>
                        <flux:text class="text-xs text-zinc-500">SKU: {{ $item['product']?->sku ?? '-' }}</flux:text>
                    </div>
                </div>
                <div class="text-right">
                    <flux:text class="font-medium">{{ $item['quantity'] }} vendas</flux:text>
                    <flux:text class="text-xs text-zinc-500">R$ {{ number_format($item['revenue'] / 100, 2, ',', '.') }}</flux:text>
                </div>
            </div>
        @empty
            <div class="p-8 text-center">
                <flux:icon name="chart-bar" class="size-12 mx-auto text-zinc-300 dark:text-zinc-600" />
                <flux:text class="mt-2 text-zinc-500">{{ __('Nenhuma venda no periodo') }}</flux:text>
            </div>
        @endforelse
    </div>
</div>
