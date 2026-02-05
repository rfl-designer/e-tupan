<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\{Order, OrderItem};
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\{Cache, DB};

class DashboardService
{
    private const CACHE_TTL = 300; // 5 minutes

    /**
     * Get sales overview for different periods.
     *
     * @return array{today: array, week: array, month: array, pending_orders: int, low_stock: int}
     */
    public function getSalesOverview(): array
    {
        return Cache::remember('dashboard:sales_overview', self::CACHE_TTL, function () {
            return [
                'today'          => $this->getSalesByPeriod('today'),
                'week'           => $this->getSalesByPeriod('week'),
                'month'          => $this->getSalesByPeriod('month'),
                'pending_orders' => $this->getPendingOrdersCount(),
                'low_stock'      => $this->getLowStockCount(),
            ];
        });
    }

    /**
     * Get sales data for a specific period.
     *
     * @return array{total: int, count: int, avg_ticket: int, comparison: float}
     */
    public function getSalesByPeriod(string $period): array
    {
        $dateRange     = $this->getDateRange($period);
        $previousRange = $this->getPreviousDateRange($period);

        $currentData  = $this->getSalesData($dateRange['start'], $dateRange['end']);
        $previousData = $this->getSalesData($previousRange['start'], $previousRange['end']);

        $comparison = 0.0;

        if ($previousData['total'] > 0) {
            $comparison = (($currentData['total'] - $previousData['total']) / $previousData['total']) * 100;
        }

        return [
            'total'      => $currentData['total'],
            'count'      => $currentData['count'],
            'avg_ticket' => $currentData['count'] > 0 ? (int) ($currentData['total'] / $currentData['count']) : 0,
            'comparison' => round($comparison, 1),
        ];
    }

    /**
     * Get sales chart data.
     *
     * @return array{labels: array<string>, current: array<int>, previous: array<int>}
     */
    public function getSalesChart(int $days = 7): array
    {
        $cacheKey = "dashboard:sales_chart:{$days}";

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($days) {
            $labels   = [];
            $current  = [];
            $previous = [];

            for ($i = $days - 1; $i >= 0; $i--) {
                $date         = now()->subDays($i);
                $previousDate = now()->subDays($i + $days);

                $labels[] = $date->format('d/m');

                $currentSales = Order::query()
                    ->whereDate('placed_at', $date)
                    ->where('payment_status', PaymentStatus::Approved)
                    ->sum('total');

                $previousSales = Order::query()
                    ->whereDate('placed_at', $previousDate)
                    ->where('payment_status', PaymentStatus::Approved)
                    ->sum('total');

                $current[]  = (int) $currentSales;
                $previous[] = (int) $previousSales;
            }

            return [
                'labels'   => $labels,
                'current'  => $current,
                'previous' => $previous,
            ];
        });
    }

    /**
     * Get recent orders.
     *
     * @return Collection<int, Order>
     */
    public function getRecentOrders(int $limit = 5): Collection
    {
        return Order::query()
            ->with(['user'])
            ->orderByDesc('placed_at')
            ->limit($limit)
            ->get();
    }

    /**
     * Get top selling products.
     *
     * @return Collection<int, array{product: Product, quantity: int, revenue: int}>
     */
    public function getTopProducts(int $limit = 5, string $period = 'month'): Collection
    {
        $cacheKey = "dashboard:top_products:{$period}:{$limit}";

        return Cache::remember($cacheKey, 3600, function () use ($limit, $period) {
            $dateRange = $this->getDateRange($period);

            return OrderItem::query()
                ->select([
                    'product_id',
                    DB::raw('SUM(quantity) as total_quantity'),
                    DB::raw('SUM(subtotal) as total_revenue'),
                ])
                ->whereHas('order', function ($query) use ($dateRange): void {
                    $query->whereBetween('placed_at', [$dateRange['start'], $dateRange['end']])
                        ->where('payment_status', PaymentStatus::Approved);
                })
                ->with('product:id,name,sku')
                ->groupBy('product_id')
                ->orderByDesc('total_quantity')
                ->limit($limit)
                ->get()
                ->map(fn ($item) => [
                    'product'  => $item->product,
                    'quantity' => (int) $item->total_quantity,
                    'revenue'  => (int) $item->total_revenue,
                ]);
        });
    }

    /**
     * Get pending orders count.
     */
    public function getPendingOrdersCount(): int
    {
        return Order::query()
            ->where('status', OrderStatus::Pending)
            ->count();
    }

    /**
     * Get low stock products count.
     */
    public function getLowStockCount(): int
    {
        return Product::query()
            ->where('manage_stock', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->count();
    }

    /**
     * Get date range for period.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getDateRange(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->startOfDay(),
                'end'   => now()->endOfDay(),
            ],
            'week' => [
                'start' => now()->startOfWeek(),
                'end'   => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end'   => now()->endOfMonth(),
            ],
            default => [
                'start' => now()->startOfDay(),
                'end'   => now()->endOfDay(),
            ],
        };
    }

    /**
     * Get previous date range for comparison.
     *
     * @return array{start: Carbon, end: Carbon}
     */
    private function getPreviousDateRange(string $period): array
    {
        return match ($period) {
            'today' => [
                'start' => now()->subDay()->startOfDay(),
                'end'   => now()->subDay()->endOfDay(),
            ],
            'week' => [
                'start' => now()->subWeek()->startOfWeek(),
                'end'   => now()->subWeek()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end'   => now()->subMonth()->endOfMonth(),
            ],
            default => [
                'start' => now()->subDay()->startOfDay(),
                'end'   => now()->subDay()->endOfDay(),
            ],
        };
    }

    /**
     * Get sales data for date range.
     *
     * @return array{total: int, count: int}
     */
    private function getSalesData(Carbon $start, Carbon $end): array
    {
        $result = Order::query()
            ->whereBetween('placed_at', [$start, $end])
            ->where('payment_status', PaymentStatus::Approved)
            ->selectRaw('COALESCE(SUM(total), 0) as total, COUNT(*) as count')
            ->first();

        return [
            'total' => (int) ($result->total ?? 0),
            'count' => (int) ($result->count ?? 0),
        ];
    }

    /**
     * Clear dashboard cache.
     */
    public function clearCache(): void
    {
        Cache::forget('dashboard:sales_overview');
        Cache::forget('dashboard:sales_chart:7');
        Cache::forget('dashboard:sales_chart:30');
        Cache::forget('dashboard:top_products:month:5');
        Cache::forget('dashboard:top_products:week:5');
    }
}
