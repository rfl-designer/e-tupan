<x-admin-layout
    :title="$customer->name"
    :breadcrumbs="[
        ['label' => 'Clientes', 'url' => route('admin.customers.index')],
        ['label' => $customer->name]
    ]"
>
    <livewire:admin.customers.customer-details :customer="$customer" />
</x-admin-layout>
