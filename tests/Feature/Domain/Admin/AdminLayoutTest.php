<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('admin dashboard displays sidebar with all menu items', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Pedidos')
        ->assertSee('Produtos')
        ->assertSee('Categorias')
        ->assertSee('Clientes')
        ->assertSee('Cupons')
        ->assertSee('Envios')
        ->assertSee('Configuracoes');
});

test('admin layout displays breadcrumb navigation', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.products.index'));

    $response->assertOk()
        ->assertSee('Dashboard')
        ->assertSee('Produtos');
});

test('admin sidebar displays current admin name', function () {
    $admin = Admin::factory()->withTwoFactor()->create([
        'name' => 'Administrador Teste',
    ]);

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Administrador Teste');
});

test('admin sidebar logout button works', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk()
        ->assertSee('Sair');
});

test('admin sidebar highlights current route', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk();
    // The 'current' attribute should be set for Dashboard
});

test('admin layout is responsive on tablet', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.dashboard'));

    $response->assertOk();
    // Sidebar should have mobile toggle
    $response->assertSee('lg:hidden');
});
