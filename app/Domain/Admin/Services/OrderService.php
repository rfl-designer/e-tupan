<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class OrderService
{
    /**
     * Allowed status transitions.
     *
     * @var array<string, array<int, string>>
     */
    private const array STATUS_TRANSITIONS = [
        'pending'    => ['processing', 'cancelled'],
        'processing' => ['shipped', 'cancelled'],
        'shipped'    => ['completed'],
        'completed'  => [],
        'cancelled'  => [],
        'refunded'   => [],
    ];

    /**
     * Get order status counts for tabs.
     *
     * @return array<string, int>
     */
    public function getStatusCounts(): array
    {
        $counts = Order::query()
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $total = Order::query()->count();

        return [
            'all'        => $total,
            'pending'    => $counts[OrderStatus::Pending->value] ?? 0,
            'processing' => $counts[OrderStatus::Processing->value] ?? 0,
            'shipped'    => $counts[OrderStatus::Shipped->value] ?? 0,
            'completed'  => $counts[OrderStatus::Completed->value] ?? 0,
            'cancelled'  => $counts[OrderStatus::Cancelled->value] ?? 0,
            'refunded'   => $counts[OrderStatus::Refunded->value] ?? 0,
        ];
    }

    /**
     * Get payment status counts.
     *
     * @return array<string, int>
     */
    public function getPaymentStatusCounts(): array
    {
        $counts = Order::query()
            ->selectRaw('payment_status, COUNT(*) as count')
            ->groupBy('payment_status')
            ->pluck('count', 'payment_status')
            ->toArray();

        return [
            'pending'    => $counts[PaymentStatus::Pending->value] ?? 0,
            'processing' => $counts[PaymentStatus::Processing->value] ?? 0,
            'approved'   => $counts[PaymentStatus::Approved->value] ?? 0,
            'declined'   => $counts[PaymentStatus::Declined->value] ?? 0,
            'refunded'   => $counts[PaymentStatus::Refunded->value] ?? 0,
        ];
    }

    /**
     * Check if a status transition is allowed.
     */
    public function canTransitionTo(Order $order, OrderStatus $newStatus): bool
    {
        $currentStatus      = $order->status->value;
        $allowedTransitions = self::STATUS_TRANSITIONS[$currentStatus] ?? [];

        return in_array($newStatus->value, $allowedTransitions, true);
    }

    /**
     * Get available status transitions for an order.
     *
     * @return array<string, string>
     */
    public function getAvailableTransitions(Order $order): array
    {
        $currentStatus      = $order->status->value;
        $allowedTransitions = self::STATUS_TRANSITIONS[$currentStatus] ?? [];

        $result = [];

        foreach ($allowedTransitions as $status) {
            $orderStatus = OrderStatus::tryFrom($status);

            if ($orderStatus !== null) {
                $result[$status] = $orderStatus->label();
            }
        }

        return $result;
    }

    /**
     * Update order status with validation.
     */
    public function updateStatus(Order $order, OrderStatus $newStatus): bool
    {
        if (!$this->canTransitionTo($order, $newStatus)) {
            return false;
        }

        return DB::transaction(function () use ($order, $newStatus): bool {
            $order->status = $newStatus;

            match ($newStatus) {
                OrderStatus::Shipped   => $order->shipped_at   = $order->shipped_at ?? now(),
                OrderStatus::Completed => $order->delivered_at = $order->delivered_at ?? now(),
                OrderStatus::Cancelled => $order->cancelled_at = $order->cancelled_at ?? now(),
                default                => null,
            };

            return $order->save();
        });
    }

    /**
     * Mark order as shipped with tracking number.
     */
    public function markAsShipped(Order $order, string $trackingNumber): bool
    {
        if (!$this->canTransitionTo($order, OrderStatus::Shipped)) {
            return false;
        }

        return DB::transaction(function () use ($order, $trackingNumber): bool {
            $order->status          = OrderStatus::Shipped;
            $order->tracking_number = $trackingNumber;
            $order->shipped_at      = now();

            return $order->save();
        });
    }

    /**
     * Cancel order with stock release.
     */
    public function cancelOrder(Order $order, ?string $reason = null): bool
    {
        if (!$order->canBeCancelled()) {
            return false;
        }

        return DB::transaction(function () use ($order): bool {
            $order->status       = OrderStatus::Cancelled;
            $order->cancelled_at = now();

            return $order->save();
        });
    }

    /**
     * Process batch status update.
     *
     * @param  array<int, string>  $orderIds
     * @return array{success: int, failed: int}
     */
    public function batchUpdateStatus(array $orderIds, OrderStatus $newStatus): array
    {
        $success = 0;
        $failed  = 0;

        foreach ($orderIds as $orderId) {
            $order = Order::query()->find($orderId);

            if ($order === null) {
                $failed++;

                continue;
            }

            if ($this->updateStatus($order, $newStatus)) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed'  => $failed,
        ];
    }

    /**
     * Get orders query with filters applied.
     *
     * @return Builder<Order>
     */
    public function getOrdersQuery(
        ?string $search = null,
        ?OrderStatus $status = null,
        ?PaymentStatus $paymentStatus = null,
        ?string $dateFrom = null,
        ?string $dateTo = null,
    ): Builder {
        return Order::query()
            ->with(['user'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('order_number', 'like', "%{$search}%")
                        ->orWhere('guest_name', 'like', "%{$search}%")
                        ->orWhere('guest_email', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                            $userQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($status, function (Builder $query, OrderStatus $status): void {
                $query->where('status', $status);
            })
            ->when($paymentStatus, function (Builder $query, PaymentStatus $paymentStatus): void {
                $query->where('payment_status', $paymentStatus);
            })
            ->when($dateFrom, function (Builder $query, string $date): void {
                $query->whereDate('placed_at', '>=', $date);
            })
            ->when($dateTo, function (Builder $query, string $date): void {
                $query->whereDate('placed_at', '<=', $date);
            });
    }
}
