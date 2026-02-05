<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Orders\OrderList;
use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('order list page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.orders.index'));

    $response->assertOk()
        ->assertSee('Pedidos');
});

test('OrderList component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('Buscar pedidos')
        ->assertSee('Status')
        ->assertSee('Pagamento');
});

test('OrderList displays orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Cliente Teste']);

    Order::factory()->create([
        'user_id'      => $user->id,
        'order_number' => 'ORD-TEST01',
        'total'        => 15000,
        'status'       => OrderStatus::Pending,
        'placed_at'    => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('ORD-TEST01')
        ->assertSee('Cliente Teste')
        ->assertSee('R$ 150,00');
});

test('OrderList can filter by status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-PEND01',
        'status'       => OrderStatus::Pending,
    ]);

    Order::factory()->create([
        'order_number' => 'ORD-COMP01',
        'status'       => OrderStatus::Completed,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('statusFilter', 'pending')
        ->assertSee('ORD-PEND01')
        ->assertDontSee('ORD-COMP01');
});

test('OrderList can filter by payment status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number'   => 'ORD-PAID01',
        'payment_status' => PaymentStatus::Approved,
    ]);

    Order::factory()->create([
        'order_number'   => 'ORD-PEND01',
        'payment_status' => PaymentStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('paymentStatusFilter', 'approved')
        ->assertSee('ORD-PAID01')
        ->assertDontSee('ORD-PEND01');
});

test('OrderList can search by order number', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-SEARCH1',
    ]);

    Order::factory()->create([
        'order_number' => 'ORD-OTHER01',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('search', 'SEARCH')
        ->assertSee('ORD-SEARCH1')
        ->assertDontSee('ORD-OTHER01');
});

test('OrderList can search by customer name', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Maria Silva']);

    Order::factory()->create([
        'user_id'      => $user->id,
        'order_number' => 'ORD-MARIA01',
    ]);

    Order::factory()->create([
        'guest_name'   => 'Joao Santos',
        'order_number' => 'ORD-JOAO01',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('search', 'Maria')
        ->assertSee('ORD-MARIA01')
        ->assertDontSee('ORD-JOAO01');
});

test('OrderList can search by customer email', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['email' => 'test@example.com']);

    Order::factory()->create([
        'user_id'      => $user->id,
        'order_number' => 'ORD-EMAIL01',
    ]);

    Order::factory()->create([
        'guest_email'  => 'other@test.com',
        'order_number' => 'ORD-OTHER01',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('search', 'test@example')
        ->assertSee('ORD-EMAIL01')
        ->assertDontSee('ORD-OTHER01');
});

test('OrderList can filter by date range', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-TODAY01',
        'placed_at'    => now(),
    ]);

    Order::factory()->create([
        'order_number' => 'ORD-OLD0001',
        'placed_at'    => now()->subMonth(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('dateFrom', now()->startOfDay()->format('Y-m-d'))
        ->set('dateTo', now()->endOfDay()->format('Y-m-d'))
        ->assertSee('ORD-TODAY01')
        ->assertDontSee('ORD-OLD0001');
});

test('OrderList orders by most recent first by default', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-OLD0001',
        'placed_at'    => now()->subDay(),
    ]);

    Order::factory()->create([
        'order_number' => 'ORD-NEW0001',
        'placed_at'    => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(OrderList::class);

    $orders = $component->get('orders');
    expect($orders->first()->order_number)->toBe('ORD-NEW0001');
});

test('OrderList can sort by total', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-CHEAP01',
        'total'        => 5000,
    ]);

    Order::factory()->create([
        'order_number' => 'ORD-EXPEN01',
        'total'        => 50000,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->call('sortBy', 'total')
        ->assertSet('sortField', 'total')
        ->assertSet('sortDirection', 'asc');
});

test('OrderList displays pagination', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->count(25)->create();

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(OrderList::class);

    $orders = $component->get('orders');
    expect($orders->count())->toBe(15);
    expect($orders->hasMorePages())->toBeTrue();
});

test('OrderList shows empty state when no orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('Nenhum pedido encontrado');
});

test('OrderList can clear all filters', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->set('search', 'test')
        ->set('statusFilter', 'pending')
        ->set('paymentStatusFilter', 'approved')
        ->set('dateFrom', '2024-01-01')
        ->set('dateTo', '2024-12-31')
        ->call('clearFilters')
        ->assertSet('search', '')
        ->assertSet('statusFilter', '')
        ->assertSet('paymentStatusFilter', '')
        ->assertSet('dateFrom', '')
        ->assertSet('dateTo', '');
});

test('OrderList displays status badge with correct color', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-PEND01',
        'status'       => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('Pendente');
});

test('OrderList displays payment status badge', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number'   => 'ORD-APPR01',
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('Aprovado');
});

test('OrderList has link to order details', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'order_number' => 'ORD-LINK01',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSeeHtml(route('admin.orders.show', $order));
});

test('OrderList displays guest orders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'user_id'      => null,
        'guest_name'   => 'Guest Customer',
        'guest_email'  => 'guest@test.com',
        'order_number' => 'ORD-GUEST1',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderList::class)
        ->assertSee('Guest Customer')
        ->assertSee('ORD-GUEST1');
});
