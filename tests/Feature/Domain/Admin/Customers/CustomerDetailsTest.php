<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Customers\CustomerDetails;
use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Models\Order;
use App\Domain\Customer\Models\Address;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customer details page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Cliente Teste']);

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.customers.show', $user));

    $response->assertOk()
        ->assertSee('Cliente Teste');
});

test('CustomerDetails component renders customer information', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create([
        'name'  => 'Maria Silva',
        'email' => 'maria@example.com',
        'phone' => '11999998888',
        'cpf'   => '12345678900',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('Maria Silva')
        ->assertSee('maria@example.com')
        ->assertSee('11999998888');
});

test('CustomerDetails shows customer since date', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create([
        'created_at' => now()->subMonths(6),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee(now()->subMonths(6)->format('d/m/Y'));
});

test('CustomerDetails shows customer addresses', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();
    Address::factory()->create([
        'user_id' => $user->id,
        'street'  => 'Rua das Flores',
        'number'  => '123',
        'city'    => 'Sao Paulo',
        'state'   => 'SP',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('Rua das Flores')
        ->assertSee('Sao Paulo');
});

test('CustomerDetails shows order history', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    Order::factory()->create([
        'user_id'      => $user->id,
        'order_number' => 'ORD-HIST01',
        'total'        => 15000,
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('ORD-HIST01')
        ->assertSee('R$ 150,00');
});

test('CustomerDetails shows order statistics', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    Order::factory()->create(['user_id' => $user->id, 'total' => 10000]);
    Order::factory()->create(['user_id' => $user->id, 'total' => 20000]);
    Order::factory()->create(['user_id' => $user->id, 'total' => 15000]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('3')
        ->assertSee('R$ 450,00');
});

test('CustomerDetails shows no orders message when customer has no orders', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('Nenhum pedido ainda');
});

test('CustomerDetails shows no addresses message when customer has no addresses', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('Nenhum endereco cadastrado');
});

test('CustomerDetails has back button to list', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSeeHtml(route('admin.customers.index'));
});

test('CustomerDetails shows 2FA status', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create([
        'two_factor_secret' => encrypt('test-secret'),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('2FA Ativo');
});

test('CustomerDetails shows default address badge', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();
    Address::factory()->create([
        'user_id'    => $user->id,
        'is_default' => true,
        'label'      => 'Casa',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerDetails::class, ['customer' => $user])
        ->assertSee('Padrao');
});
