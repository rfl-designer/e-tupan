<x-admin-layout title="Dashboard">
    <div class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Dashboard') }}</flux:heading>
                <flux:subheading>
                    {{ __('Bem-vindo, :name!', ['name' => auth('admin')->user()->name]) }}
                </flux:subheading>
            </div>
        </div>

        {{-- Sales Overview Cards --}}
        @livewire(\App\Domain\Admin\Livewire\Dashboard\SalesOverview::class)

        {{-- Charts and Lists Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Sales Chart --}}
            @livewire(\App\Domain\Admin\Livewire\Dashboard\SalesChart::class)

            {{-- Quick Actions --}}
            @livewire(\App\Domain\Admin\Livewire\Dashboard\QuickActions::class)
        </div>

        {{-- Recent Orders and Top Products --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Recent Orders --}}
            @livewire(\App\Domain\Admin\Livewire\Dashboard\RecentOrders::class)

            {{-- Top Products --}}
            @livewire(\App\Domain\Admin\Livewire\Dashboard\TopProducts::class)
        </div>

        {{-- Widgets Grid --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @livewire(\App\Domain\Inventory\Livewire\Admin\LowStockWidget::class)
            @livewire(\App\Domain\Shipping\Livewire\Admin\ShippingDashboardWidget::class)
        </div>
    </div>
</x-admin-layout>
