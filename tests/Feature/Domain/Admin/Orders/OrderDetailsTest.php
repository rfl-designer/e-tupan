<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Orders\{OrderDetails, OrderTimeline};
use App\Domain\Admin\Models\Admin;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Models\{Order, OrderItem, Payment};
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('order details page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create([
        'order_number' => 'ORD-DETAIL1',
    ]);

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.orders.show', $order));

    $response->assertOk()
        ->assertSee('ORD-DETAIL1');
});

test('OrderDetails component renders order information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Cliente Teste']);

    $order = Order::factory()->create([
        'user_id'        => $user->id,
        'order_number'   => 'ORD-TEST123',
        'total'          => 25000,
        'subtotal'       => 22000,
        'shipping_cost'  => 3000,
        'discount'       => 0,
        'status'         => OrderStatus::Pending,
        'payment_status' => PaymentStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('ORD-TEST123')
        ->assertSee('Cliente Teste')
        ->assertSee('R$ 250,00')
        ->assertSee('Pendente');
});

test('OrderDetails shows customer information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create([
        'name'  => 'Maria Silva',
        'email' => 'maria@example.com',
        'phone' => '11999998888',
    ]);

    $order = Order::factory()->create([
        'user_id' => $user->id,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Maria Silva')
        ->assertSee('maria@example.com');
});

test('OrderDetails shows guest customer information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'user_id'     => null,
        'guest_name'  => 'Guest User',
        'guest_email' => 'guest@example.com',
        'guest_phone' => '11888887777',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Guest User')
        ->assertSee('guest@example.com');
});

test('OrderDetails shows shipping address', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'shipping_street'       => 'Rua das Flores',
        'shipping_number'       => '123',
        'shipping_neighborhood' => 'Centro',
        'shipping_city'         => 'Sao Paulo',
        'shipping_state'        => 'SP',
        'shipping_zipcode'      => '01234-567',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Rua das Flores')
        ->assertSee('123')
        ->assertSee('Sao Paulo');
});

test('OrderDetails shows order items', function () {
    $admin   = Admin::factory()->withTwoFactor()->create();
    $product = Product::factory()->create(['name' => 'Produto Teste']);

    $order = Order::factory()->create();
    OrderItem::factory()->create([
        'order_id'     => $order->id,
        'product_id'   => $product->id,
        'product_name' => 'Produto Teste',
        'quantity'     => 2,
        'unit_price'   => 5000,
        'subtotal'     => 10000,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Produto Teste')
        ->assertSee('2');
});

test('OrderDetails shows payment information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'payment_status' => PaymentStatus::Approved,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'method'   => PaymentMethod::CreditCard,
        'status'   => PaymentStatus::Approved,
        'amount'   => 15000,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Aprovado');
});

test('OrderDetails shows shipping information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'shipping_method'  => 'SEDEX',
        'shipping_carrier' => 'Correios',
        'shipping_cost'    => 2500,
        'shipping_days'    => 3,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('SEDEX')
        ->assertSee('R$ 25,00');
});

test('OrderDetails shows tracking number when available', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'tracking_number' => 'BR123456789XX',
        'shipped_at'      => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('BR123456789XX');
});

test('OrderDetails shows order notes', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'notes' => 'Entregar no periodo da tarde',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('Entregar no periodo da tarde');
});

test('OrderTimeline shows order dates', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'placed_at'  => now()->subDays(3),
        'paid_at'    => now()->subDays(2),
        'shipped_at' => now()->subDay(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderTimeline::class, ['order' => $order])
        ->assertSee('Pedido Criado')
        ->assertSee('Pagamento Aprovado')
        ->assertSee('Pedido Enviado');
});

test('OrderTimeline shows cancelled status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'status'       => OrderStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderTimeline::class, ['order' => $order])
        ->assertSee('Pedido Cancelado');
});

test('OrderTimeline shows delivered status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'status'       => OrderStatus::Completed,
        'delivered_at' => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderTimeline::class, ['order' => $order])
        ->assertSee('Pedido Entregue');
});

test('OrderDetails has back button to list', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $order = Order::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSeeHtml(route('admin.orders.index'));
});

test('OrderDetails shows coupon information when applied', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $order = Order::factory()->create([
        'coupon_code' => 'DESCONTO10',
        'discount'    => 1000,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(OrderDetails::class, ['order' => $order])
        ->assertSee('DESCONTO10')
        ->assertSee('R$ 10,00');
});
