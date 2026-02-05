<div>
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.dashboard') }}" class="text-neutral-400 hover:text-white transition-colors">
                <flux:icon name="arrow-left" class="size-5" />
            </a>
            <div>
                <flux:heading size="xl">{{ __('Dashboard de Estoque') }}</flux:heading>
                <flux:subheading>{{ __('Visao geral do inventario') }}</flux:subheading>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.inventory.index') }}">
                <flux:button variant="primary">
                    <flux:icon name="cube" class="size-4 mr-1" />
                    {{ __('Gerenciar Estoque') }}
                </flux:button>
            </a>
            <a href="{{ route('admin.inventory.movements') }}">
                <flux:button variant="ghost">
                    <flux:icon name="clipboard-document-list" class="size-4 mr-1" />
                    {{ __('Ver Historico') }}
                </flux:button>
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-6">
        @livewire(\App\Domain\Inventory\Livewire\Admin\StockStatsCard::class)
    </div>

    {{-- Reservations Summary --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-purple-500/10">
                    <flux:icon name="clock" class="size-5 text-purple-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $activeReservationsCount }}</p>
                    <p class="text-sm text-neutral-400">{{ __('Reservas Ativas') }}</p>
                </div>
            </div>
        </div>

        <div class="rounded-lg border border-neutral-800 bg-neutral-900 p-4">
            <div class="flex items-center gap-3">
                <div class="flex size-10 items-center justify-center rounded-lg bg-indigo-500/10">
                    <flux:icon name="shopping-cart" class="size-5 text-indigo-400" />
                </div>
                <div>
                    <p class="text-2xl font-bold text-white">{{ $totalReservedQuantity }}</p>
                    <p class="text-sm text-neutral-400">{{ __('Unidades Reservadas') }}</p>
                </div>
            </div>
        </div>
    </div>

    {{-- Movements Chart --}}
    <div class="mb-6 rounded-lg border border-neutral-800 bg-neutral-900 p-4">
        <div class="mb-4 flex items-center gap-2">
            <flux:icon name="chart-bar" class="size-5 text-blue-400" />
            <flux:heading size="sm">{{ __('Movimentacoes nos ultimos 7 dias') }}</flux:heading>
        </div>

        <div class="flex items-end justify-between gap-2 h-32">
            @php
                $maxCount = max(1, collect($movementsChartData)->max('count'));
            @endphp
            @foreach ($movementsChartData as $day)
                <div class="flex flex-1 flex-col items-center gap-1">
                    <div class="relative w-full bg-neutral-800 rounded-t" style="height: 100px;">
                        <div
                            class="absolute bottom-0 left-0 right-0 bg-blue-500 rounded-t transition-all"
                            style="height: {{ $maxCount > 0 ? ($day['count'] / $maxCount) * 100 : 0 }}%;"
                        ></div>
                    </div>
                    <span class="text-xs text-neutral-500">{{ $day['date'] }}</span>
                    <span class="text-xs font-medium text-neutral-300">{{ $day['count'] }}</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Widgets Grid --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @livewire(\App\Domain\Inventory\Livewire\Admin\LowStockWidget::class)
        @livewire(\App\Domain\Inventory\Livewire\Admin\RecentMovementsWidget::class)
    </div>

    {{-- Back to Dashboard --}}
    <div class="mt-6">
        <a href="{{ route('admin.dashboard') }}" class="text-sm text-neutral-400 hover:text-white transition-colors">
            &larr; {{ __('Voltar ao Dashboard') }}
        </a>
    </div>
</div>
