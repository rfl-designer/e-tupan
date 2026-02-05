<div wire:poll.300s class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
    {{-- Today Sales --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Vendas Hoje') }}</flux:text>
            <flux:icon name="currency-dollar" class="size-5 text-zinc-400" />
        </div>
        <div class="mt-2">
            <flux:heading size="xl">
                R$ {{ number_format($this->overview['today']['total'] / 100, 2, ',', '.') }}
            </flux:heading>
            <div class="mt-1 flex items-center gap-1">
                @if($this->overview['today']['comparison'] > 0)
                    <flux:icon name="arrow-trending-up" class="size-4 text-green-500" />
                    <flux:text class="text-green-500">{{ $this->overview['today']['comparison'] }}%</flux:text>
                @elseif($this->overview['today']['comparison'] < 0)
                    <flux:icon name="arrow-trending-down" class="size-4 text-red-500" />
                    <flux:text class="text-red-500">{{ abs($this->overview['today']['comparison']) }}%</flux:text>
                @else
                    <flux:icon name="minus" class="size-4 text-zinc-400" />
                    <flux:text class="text-zinc-400">0%</flux:text>
                @endif
                <flux:text class="text-zinc-400 text-xs">vs ontem</flux:text>
            </div>
        </div>
    </div>

    {{-- Week Sales --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Vendas Semana') }}</flux:text>
            <flux:icon name="calendar" class="size-5 text-zinc-400" />
        </div>
        <div class="mt-2">
            <flux:heading size="xl">
                R$ {{ number_format($this->overview['week']['total'] / 100, 2, ',', '.') }}
            </flux:heading>
            <div class="mt-1 flex items-center gap-1">
                @if($this->overview['week']['comparison'] > 0)
                    <flux:icon name="arrow-trending-up" class="size-4 text-green-500" />
                    <flux:text class="text-green-500">{{ $this->overview['week']['comparison'] }}%</flux:text>
                @elseif($this->overview['week']['comparison'] < 0)
                    <flux:icon name="arrow-trending-down" class="size-4 text-red-500" />
                    <flux:text class="text-red-500">{{ abs($this->overview['week']['comparison']) }}%</flux:text>
                @else
                    <flux:icon name="minus" class="size-4 text-zinc-400" />
                    <flux:text class="text-zinc-400">0%</flux:text>
                @endif
                <flux:text class="text-zinc-400 text-xs">vs semana anterior</flux:text>
            </div>
        </div>
    </div>

    {{-- Month Sales --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Vendas Mes') }}</flux:text>
            <flux:icon name="chart-bar" class="size-5 text-zinc-400" />
        </div>
        <div class="mt-2">
            <flux:heading size="xl">
                R$ {{ number_format($this->overview['month']['total'] / 100, 2, ',', '.') }}
            </flux:heading>
            <div class="mt-1 flex items-center gap-1">
                @if($this->overview['month']['comparison'] > 0)
                    <flux:icon name="arrow-trending-up" class="size-4 text-green-500" />
                    <flux:text class="text-green-500">{{ $this->overview['month']['comparison'] }}%</flux:text>
                @elseif($this->overview['month']['comparison'] < 0)
                    <flux:icon name="arrow-trending-down" class="size-4 text-red-500" />
                    <flux:text class="text-red-500">{{ abs($this->overview['month']['comparison']) }}%</flux:text>
                @else
                    <flux:icon name="minus" class="size-4 text-zinc-400" />
                    <flux:text class="text-zinc-400">0%</flux:text>
                @endif
                <flux:text class="text-zinc-400 text-xs">vs mes anterior</flux:text>
            </div>
        </div>
    </div>

    {{-- Pending Orders --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Pedidos Pendentes') }}</flux:text>
            <flux:icon name="clock" class="size-5 text-amber-500" />
        </div>
        <div class="mt-2">
            <flux:heading size="xl">{{ $this->overview['pending_orders'] }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-400">{{ __('aguardando processamento') }}</flux:text>
        </div>
    </div>

    {{-- Low Stock --}}
    <div class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex items-center justify-between">
            <flux:text class="text-zinc-500 dark:text-zinc-400">{{ __('Estoque Baixo') }}</flux:text>
            <flux:icon name="exclamation-triangle" class="size-5 text-red-500" />
        </div>
        <div class="mt-2">
            <flux:heading size="xl">{{ $this->overview['low_stock'] }}</flux:heading>
            <flux:text class="mt-1 text-xs text-zinc-400">{{ __('produtos precisam reposicao') }}</flux:text>
        </div>
    </div>
</div>
