<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Orders\OrderActions;
use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentStatus};
use App\Domain\Checkout\Models\Order;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('OrderActions component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->assertSee('Acoes')
        ->assertSee('Atualizar Status');
});

test('OrderActions can update order status to processing', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('updateStatus', 'processing')
        ->assertDispatched('order-updated');

    expect($order->fresh()->status)->toBe(OrderStatus::Processing);
});

test('OrderActions can update order status from processing to shipped', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->set('trackingNumber', 'BR123456789XX')
        ->call('markAsShipped')
        ->assertDispatched('order-updated');

    $order->refresh();
    expect($order->status)->toBe(OrderStatus::Shipped);
    expect($order->shipped_at)->not->toBeNull();
});

test('OrderActions can update order status from shipped to completed', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'     => OrderStatus::Shipped,
        'shipped_at' => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('updateStatus', 'completed')
        ->assertDispatched('order-updated');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
    expect($order->fresh()->delivered_at)->not->toBeNull();
});

test('OrderActions can cancel order', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertDispatched('order-updated');

    expect($order->fresh()->status)->toBe(OrderStatus::Cancelled);
    expect($order->fresh()->cancelled_at)->not->toBeNull();
});

test('OrderActions cannot cancel completed order', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Completed,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('cancelOrder')
        ->assertDispatched('notify');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('OrderActions validates tracking number is required for marking as shipped', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->set('trackingNumber', '')
        ->call('markAsShipped')
        ->assertHasErrors(['trackingNumber' => 'required']);
});

test('OrderActions shows cancel button for pending orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->assertSee('Cancelar Pedido');
});

test('OrderActions hides cancel button for cancelled orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Cancelled,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->assertDontSee('Cancelar Pedido');
});

test('OrderActions shows status options based on current status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->assertSee('Processando');
});

test('OrderActions can mark order as shipped', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->set('trackingNumber', 'BR987654321XX')
        ->call('markAsShipped')
        ->assertDispatched('order-updated');

    $order->refresh();
    expect($order->tracking_number)->toBe('BR987654321XX');
    expect($order->shipped_at)->not->toBeNull();
    expect($order->status)->toBe(OrderStatus::Shipped);
});

test('OrderActions shows tracking number input when order is processing', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Processing,
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->assertSee('Codigo de Rastreio');
});

test('OrderActions can refund completed paid order', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Completed,
        'payment_status' => PaymentStatus::Approved,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('refundOrder')
        ->assertDispatched('order-updated');

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});

test('OrderActions cannot refund unpaid order', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Completed,
        'payment_status' => PaymentStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('refundOrder')
        ->assertDispatched('notify');

    expect($order->fresh()->status)->toBe(OrderStatus::Completed);
});

test('OrderActions can refund shipped paid order', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'status'         => OrderStatus::Shipped,
        'payment_status' => PaymentStatus::Approved,
        'shipped_at'     => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderActions::class, ['order' => $order])
        ->call('refundOrder')
        ->assertDispatched('order-updated');

    expect($order->fresh()->status)->toBe(OrderStatus::Refunded);
});
