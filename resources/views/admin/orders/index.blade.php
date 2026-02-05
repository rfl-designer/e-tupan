<x-admin-layout
    title="Pedidos"
    :breadcrumbs="[['label' => 'Pedidos']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Pedidos') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie os pedidos da loja') }}</flux:subheading>
            </div>
        </div>

        <livewire:admin.orders.order-list />
    </div>
</x-admin-layout>
