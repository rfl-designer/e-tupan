<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Services;

use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Models\User;
use Illuminate\Support\Collection;

class GlobalSearchService
{
    private const MAX_RESULTS_PER_TYPE = 5;

    /**
     * Search across all entities.
     *
     * @return array{orders: Collection, products: Collection, customers: Collection}
     */
    public function search(string $query): array
    {
        if (strlen($query) < 2) {
            return [
                'orders'    => collect(),
                'products'  => collect(),
                'customers' => collect(),
            ];
        }

        return [
            'orders'    => $this->searchOrders($query),
            'products'  => $this->searchProducts($query),
            'customers' => $this->searchCustomers($query),
        ];
    }

    /**
     * @return Collection<int, array{id: string, title: string, subtitle: string, url: string, icon: string}>
     */
    public function searchOrders(string $query): Collection
    {
        return Order::query()
            ->where(function ($q) use ($query): void {
                $q->where('order_number', 'like', "%{$query}%")
                    ->orWhere('guest_email', 'like', "%{$query}%")
                    ->orWhereHas('user', function ($userQuery) use ($query): void {
                        $userQuery->where('email', 'like', "%{$query}%")
                            ->orWhere('name', 'like', "%{$query}%");
                    });
            })
            ->with('user:id,name,email')
            ->orderByDesc('placed_at')
            ->limit(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (Order $order) => [
                'id'       => $order->id,
                'title'    => "Pedido #{$order->order_number}",
                'subtitle' => $order->user?->name ?? $order->guest_name ?? $order->guest_email ?? 'Visitante',
                'url'      => route('admin.orders.show', $order->id),
                'icon'     => 'shopping-bag',
            ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, subtitle: string, url: string, icon: string}>
     */
    public function searchProducts(string $query): Collection
    {
        return Product::query()
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('sku', 'like', "%{$query}%");
            })
            ->orderByDesc('updated_at')
            ->limit(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (Product $product) => [
                'id'       => $product->id,
                'title'    => $product->name,
                'subtitle' => "SKU: {$product->sku}",
                'url'      => route('admin.products.edit', $product->id),
                'icon'     => 'cube',
            ]);
    }

    /**
     * @return Collection<int, array{id: int, title: string, subtitle: string, url: string, icon: string}>
     */
    public function searchCustomers(string $query): Collection
    {
        return User::query()
            ->where(function ($q) use ($query): void {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('email', 'like', "%{$query}%")
                    ->orWhere('cpf', 'like', "%{$query}%");
            })
            ->orderByDesc('created_at')
            ->limit(self::MAX_RESULTS_PER_TYPE)
            ->get()
            ->map(fn (User $user) => [
                'id'       => $user->id,
                'title'    => $user->name,
                'subtitle' => $user->email,
                'url'      => route('admin.customers.show', $user->id),
                'icon'     => 'user',
            ]);
    }

    /**
     * Check if there are any results.
     */
    public function hasResults(array $results): bool
    {
        return $results['orders']->isNotEmpty()
            || $results['products']->isNotEmpty()
            || $results['customers']->isNotEmpty();
    }

    /**
     * Get total count of results.
     */
    public function totalCount(array $results): int
    {
        return $results['orders']->count()
            + $results['products']->count()
            + $results['customers']->count();
    }
}
