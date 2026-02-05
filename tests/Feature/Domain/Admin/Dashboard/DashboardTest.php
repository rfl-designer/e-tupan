<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Dashboard\{QuickActions, RecentOrders, SalesChart, SalesOverview, TopProducts};
use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Enums\OrderStatus;
use App\Domain\Checkout\Models\Order;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('dashboard page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Bem-vindo');
});

test('dashboard displays admin name', function () {
    $admin = Admin::factory()->withTwoFactor()->create([
        'name' => 'Admin Teste',
    ]);

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Admin Teste');
});

test('SalesOverview component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(SalesOverview::class)
        ->assertSee('Vendas Hoje')
        ->assertSee('Vendas Semana')
        ->assertSee('Vendas Mes')
        ->assertSee('Pedidos Pendentes')
        ->assertSee('Estoque Baixo');
});

test('SalesOverview shows correct pending orders count', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->count(5)->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(SalesOverview::class)
        ->assertSee('5');
});

test('SalesChart component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(SalesChart::class)
        ->assertSee('Grafico de Vendas')
        ->assertSee('7 dias')
        ->assertSee('30 dias');
});

test('SalesChart can change period', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(SalesChart::class)
        ->assertSet('days', 7)
        ->call('setDays', 30)
        ->assertSet('days', 30);
});

test('RecentOrders component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(RecentOrders::class)
        ->assertSee('Ultimos Pedidos')
        ->assertSee('Ver todos');
});

test('RecentOrders shows orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->create([
        'order_number' => 'ORD-TEST01',
        'total'        => 15000,
        'placed_at'    => now(),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(RecentOrders::class)
        ->assertSee('ORD-TEST01')
        ->assertSee('R$ 150,00');
});

test('RecentOrders shows empty state when no orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(RecentOrders::class)
        ->assertSee('Nenhum pedido ainda');
});

test('TopProducts component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(TopProducts::class)
        ->assertSee('Produtos Mais Vendidos')
        ->assertSee('Semana')
        ->assertSee('Mes');
});

test('TopProducts can change period', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(TopProducts::class)
        ->assertSet('period', 'month')
        ->call('setPeriod', 'week')
        ->assertSet('period', 'week');
});

test('QuickActions component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(QuickActions::class)
        ->assertSee('Acoes Rapidas')
        ->assertSee('Novo Produto')
        ->assertSee('Pedidos Pendentes')
        ->assertSee('Gerar Etiquetas');
});

test('QuickActions shows pending orders count', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    Order::factory()->count(3)->create([
        'status' => OrderStatus::Pending,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(QuickActions::class)
        ->assertSee('3 pedidos aguardando');
});

test('QuickActions shows no pending orders message when empty', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(QuickActions::class)
        ->assertSee('Nenhum pedido pendente');
});
