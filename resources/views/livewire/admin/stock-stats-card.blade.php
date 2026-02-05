<div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
    {{-- Total Managed SKUs --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-blue-500/10">
                <flux:icon name="cube" class="size-5 text-blue-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $this->totalManagedSkus }}</p>
                <p class="text-sm text-neutral-400">{{ __('SKUs Gerenciados') }}</p>
            </div>
        </div>
    </div>

    {{-- Out of Stock --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-red-500/10">
                <flux:icon name="x-circle" class="size-5 text-red-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $this->outOfStockCount }}</p>
                <p class="text-sm text-neutral-400">{{ __('Sem Estoque') }}</p>
            </div>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-amber-500/10">
                <flux:icon name="exclamation-triangle" class="size-5 text-amber-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-white">{{ $this->lowStockCount }}</p>
                <p class="text-sm text-neutral-400">{{ __('Estoque Baixo') }}</p>
            </div>
        </div>
    </div>

    {{-- Total Stock Value --}}
    <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="flex items-center gap-3">
            <div class="flex size-10 items-center justify-center rounded-lg bg-green-500/10">
                <flux:icon name="currency-dollar" class="size-5 text-green-400" />
            </div>
            <div>
                <p class="text-2xl font-bold text-white">R$ {{ $this->formatCurrency($this->totalStockValue) }}</p>
                <p class="text-sm text-neutral-400">{{ __('Valor em Estoque') }}</p>
            </div>
        </div>
    </div>
</div>
