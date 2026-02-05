<div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
    <flux:heading size="lg" class="mb-4">{{ __('Acoes Rapidas') }}</flux:heading>

    <div class="grid grid-cols-1 gap-3">
        <a
            href="{{ route('admin.products.create') }}"
            class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900">
                <flux:icon name="plus" class="size-5 text-blue-600 dark:text-blue-400" />
            </div>
            <div>
                <flux:text class="font-medium">{{ __('Novo Produto') }}</flux:text>
                <flux:text class="text-xs text-zinc-500">{{ __('Adicionar produto ao catalogo') }}</flux:text>
            </div>
        </a>

        <a
            href="{{ route('admin.orders.index') }}?status=pending"
            class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 dark:bg-amber-900">
                <flux:icon name="clock" class="size-5 text-amber-600 dark:text-amber-400" />
            </div>
            <div>
                <flux:text class="font-medium">{{ __('Pedidos Pendentes') }}</flux:text>
                <flux:text class="text-xs text-zinc-500">
                    @if($this->pendingOrdersCount > 0)
                        {{ $this->pendingOrdersCount }} {{ trans_choice('pedido|pedidos', $this->pendingOrdersCount) }} aguardando
                    @else
                        {{ __('Nenhum pedido pendente') }}
                    @endif
                </flux:text>
            </div>
            @if($this->pendingOrdersCount > 0)
                <flux:badge color="amber" class="ml-auto">{{ $this->pendingOrdersCount }}</flux:badge>
            @endif
        </a>

        <a
            href="{{ route('admin.shipping.index') }}"
            class="flex items-center gap-3 rounded-lg border border-zinc-200 bg-zinc-50 p-3 transition-colors hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:bg-zinc-700"
        >
            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900">
                <flux:icon name="truck" class="size-5 text-green-600 dark:text-green-400" />
            </div>
            <div>
                <flux:text class="font-medium">{{ __('Gerar Etiquetas') }}</flux:text>
                <flux:text class="text-xs text-zinc-500">{{ __('Gerenciar envios e etiquetas') }}</flux:text>
            </div>
        </a>

        @if($this->lowStockCount > 0)
            <a
                href="{{ route('admin.inventory.index') }}?filter=low_stock"
                class="flex items-center gap-3 rounded-lg border border-red-200 bg-red-50 p-3 transition-colors hover:bg-red-100 dark:border-red-900 dark:bg-red-950 dark:hover:bg-red-900"
            >
                <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-red-100 dark:bg-red-900">
                    <flux:icon name="exclamation-triangle" class="size-5 text-red-600 dark:text-red-400" />
                </div>
                <div>
                    <flux:text class="font-medium text-red-700 dark:text-red-300">{{ __('Estoque Baixo') }}</flux:text>
                    <flux:text class="text-xs text-red-600 dark:text-red-400">
                        {{ $this->lowStockCount }} {{ trans_choice('produto|produtos', $this->lowStockCount) }} precisando reposicao
                    </flux:text>
                </div>
                <flux:badge color="red" class="ml-auto">{{ $this->lowStockCount }}</flux:badge>
            </a>
        @endif
    </div>
</div>
