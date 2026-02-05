<?php

declare(strict_types = 1);

use App\Domain\Admin\Services\DashboardService;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use Illuminate\Support\Facades\Cache;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('getSalesOverview returns correct structure', function () {
    $service = new DashboardService();

    $overview = $service->getSalesOverview();

    expect($overview)
        ->toBeArray()
        ->toHaveKeys(['today', 'week', 'month', 'pending_orders', 'low_stock']);

    expect($overview['today'])
        ->toHaveKeys(['total', 'count', 'avg_ticket', 'comparison']);
});

test('getSalesByPeriod returns zero for empty period', function () {
    $service = new DashboardService();

    $data = $service->getSalesByPeriod('today');

    expect($data['total'])->toBe(0)
        ->and($data['count'])->toBe(0)
        ->and($data['avg_ticket'])->toBe(0);
});

test('getSalesByPeriod calculates correct totals for today', function () {
    // Create orders for today
    Order::factory()->count(3)->create([
        'placed_at'      => now(),
        'payment_status' => PaymentStatus::Approved,
        'total'          => 10000, // R$ 100,00
    ]);

    // Create order for yesterday (should not be counted)
    Order::factory()->create([
        'placed_at'      => now()->subDay(),
        'payment_status' => PaymentStatus::Approved,
        'total'          => 50000,
    ]);

    $service = new DashboardService();
    $data    = $service->getSalesByPeriod('today');

    expect($data['total'])->toBe(30000)
        ->and($data['count'])->toBe(3)
        ->and($data['avg_ticket'])->toBe(10000);
});

test('getSalesByPeriod only counts approved payments', function () {
    Order::factory()->create([
        'placed_at'      => now(),
        'payment_status' => PaymentStatus::Approved,
        'total'          => 10000,
    ]);

    Order::factory()->create([
        'placed_at'      => now(),
        'payment_status' => PaymentStatus::Pending,
        'total'          => 20000,
    ]);

    $service = new DashboardService();
    $data    = $service->getSalesByPeriod('today');

    expect($data['total'])->toBe(10000)
        ->and($data['count'])->toBe(1);
});

test('getSalesChart returns correct structure', function () {
    $service = new DashboardService();

    $data = $service->getSalesChart(7);

    expect($data)
        ->toBeArray()
        ->toHaveKeys(['labels', 'current', 'previous'])
        ->and($data['labels'])->toHaveCount(7)
        ->and($data['current'])->toHaveCount(7)
        ->and($data['previous'])->toHaveCount(7);
});

test('getRecentOrders returns orders ordered by date', function () {
    $olderOrder = Order::factory()->create([
        'placed_at' => now()->subDays(2),
    ]);

    $newerOrder = Order::factory()->create([
        'placed_at' => now(),
    ]);

    $service = new DashboardService();
    $orders  = $service->getRecentOrders(5);

    expect($orders->first()->id)->toBe($newerOrder->id);
});

test('getRecentOrders limits results', function () {
    Order::factory()->count(10)->create();

    $service = new DashboardService();
    $orders  = $service->getRecentOrders(5);

    expect($orders)->toHaveCount(5);
});

test('getPendingOrdersCount returns correct count', function () {
    Order::factory()->count(3)->create([
        'status' => OrderStatus::Pending,
    ]);

    Order::factory()->count(2)->create([
        'status' => OrderStatus::Processing,
    ]);

    $service = new DashboardService();

    expect($service->getPendingOrdersCount())->toBe(3);
});

test('getSalesOverview is cached', function () {
    $service = new DashboardService();

    // First call
    $service->getSalesOverview();

    // Create more orders
    Order::factory()->count(3)->create([
        'placed_at'      => now(),
        'payment_status' => PaymentStatus::Approved,
        'total'          => 10000,
    ]);

    // Second call should return cached data (totals should still be 0)
    $overview = $service->getSalesOverview();

    expect($overview['today']['total'])->toBe(0);
});

test('clearCache removes cached data', function () {
    $service = new DashboardService();

    // First call to populate cache
    $service->getSalesOverview();

    // Create orders
    Order::factory()->count(3)->create([
        'placed_at'      => now(),
        'payment_status' => PaymentStatus::Approved,
        'total'          => 10000,
    ]);

    // Clear cache
    $service->clearCache();

    // Now should return fresh data
    $overview = $service->getSalesOverview();

    expect($overview['today']['total'])->toBe(30000);
});
