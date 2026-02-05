<x-admin-layout
    :title="'Pedido ' . $order->order_number"
    :breadcrumbs="[
        ['label' => 'Pedidos', 'url' => route('admin.orders.index')],
        ['label' => $order->order_number]
    ]"
>
    <livewire:admin.orders.order-details :order="$order" />
</x-admin-layout>
