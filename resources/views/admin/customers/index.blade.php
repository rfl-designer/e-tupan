<x-admin-layout
    title="Clientes"
    :breadcrumbs="[['label' => 'Clientes']]"
>
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <flux:heading size="xl">{{ __('Clientes') }}</flux:heading>
                <flux:subheading>{{ __('Gerencie os clientes da loja') }}</flux:subheading>
            </div>
        </div>

        <livewire:admin.customers.customer-list />
    </div>
</x-admin-layout>
