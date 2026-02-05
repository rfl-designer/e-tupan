<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Admin\Enums\NotificationType;
use App\Domain\Admin\Models\{Admin, AdminNotification};
use Illuminate\Support\Collection;

class NotificationService
{
    public function create(
        Admin $admin,
        NotificationType $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
    ): AdminNotification {
        return AdminNotification::query()->create([
            'admin_id' => $admin->id,
            'type'     => $type,
            'title'    => $title,
            'message'  => $message,
            'icon'     => $type->icon(),
            'link'     => $link,
            'data'     => $data,
        ]);
    }

    public function notifyAllAdmins(
        NotificationType $type,
        string $title,
        string $message,
        ?string $link = null,
        ?array $data = null,
    ): Collection {
        return Admin::query()
            ->where('is_active', true)
            ->get()
            ->map(fn (Admin $admin) => $this->create($admin, $type, $title, $message, $link, $data));
    }

    public function notifyNewOrder(string $orderNumber, string $orderId): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::NewOrder,
            'Novo Pedido',
            "Pedido #{$orderNumber} recebido",
            route('admin.orders.show', $orderId),
            ['order_id' => $orderId, 'order_number' => $orderNumber],
        );
    }

    public function notifyPaymentConfirmed(string $orderNumber, string $orderId): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::PaymentConfirmed,
            'Pagamento Confirmado',
            "Pagamento do pedido #{$orderNumber} confirmado",
            route('admin.orders.show', $orderId),
            ['order_id' => $orderId, 'order_number' => $orderNumber],
        );
    }

    public function notifyPaymentFailed(string $orderNumber, string $orderId, string $reason): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::PaymentFailed,
            'Pagamento Falhou',
            "Pagamento do pedido #{$orderNumber} falhou: {$reason}",
            route('admin.orders.show', $orderId),
            ['order_id' => $orderId, 'order_number' => $orderNumber, 'reason' => $reason],
        );
    }

    public function notifyLowStock(string $productName, int $productId, int $currentStock): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::LowStock,
            'Estoque Baixo',
            "Produto \"{$productName}\" com estoque baixo ({$currentStock} unidades)",
            route('admin.products.edit', $productId),
            ['product_id' => $productId, 'product_name' => $productName, 'stock' => $currentStock],
        );
    }

    public function notifyOrderCancelled(string $orderNumber, string $orderId, string $reason): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::OrderCancelled,
            'Pedido Cancelado',
            "Pedido #{$orderNumber} cancelado: {$reason}",
            route('admin.orders.show', $orderId),
            ['order_id' => $orderId, 'order_number' => $orderNumber, 'reason' => $reason],
        );
    }

    public function notifyIntegrationError(string $integration, string $error): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::IntegrationError,
            'Erro de Integracao',
            "Erro na integracao {$integration}: {$error}",
            route('admin.activity-logs.index'),
            ['integration' => $integration, 'error' => $error],
        );
    }

    public function notifyShipmentDelivered(string $orderNumber, string $orderId): Collection
    {
        return $this->notifyAllAdmins(
            NotificationType::ShipmentDelivered,
            'Entrega Realizada',
            "Pedido #{$orderNumber} entregue ao destinatario",
            route('admin.orders.show', $orderId),
            ['order_id' => $orderId, 'order_number' => $orderNumber],
        );
    }

    public function getUnreadCount(Admin $admin): int
    {
        return AdminNotification::query()
            ->forAdmin($admin)
            ->unread()
            ->count();
    }

    /**
     * @return Collection<int, AdminNotification>
     */
    public function getRecent(Admin $admin, int $limit = 10): Collection
    {
        return AdminNotification::query()
            ->forAdmin($admin)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function markAsRead(AdminNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(Admin $admin): int
    {
        return AdminNotification::query()
            ->forAdmin($admin)
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function deleteOld(int $days = 30): int
    {
        return AdminNotification::query()
            ->where('created_at', '<', now()->subDays($days))
            ->delete();
    }
}
