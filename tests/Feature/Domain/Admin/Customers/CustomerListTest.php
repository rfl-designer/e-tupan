<?php

declare(strict_types = 1);

use App\Domain\Admin\Livewire\Customers\CustomerList;
use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Models\Order;
use App\Models\User;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

test('customer list page loads successfully', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    $response = actingAsAdminWith2FA($this, $admin)
        ->get(route('admin.customers.index'));

    $response->assertOk()
        ->assertSee('Clientes');
});

test('CustomerList component renders correctly', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('Buscar clientes');
});

test('CustomerList displays customers', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    User::factory()->create([
        'name'  => 'Maria Silva',
        'email' => 'maria@example.com',
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('Maria Silva')
        ->assertSee('maria@example.com');
});

test('CustomerList can search by name', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    User::factory()->create(['name' => 'Maria Silva', 'email' => 'maria@test.com']);
    User::factory()->create(['name' => 'Joao Santos', 'email' => 'joao@test.com']);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->set('search', 'Maria')
        ->assertSee('Maria Silva')
        ->assertDontSee('Joao Santos');
});

test('CustomerList can search by email', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    User::factory()->create(['name' => 'Maria Silva', 'email' => 'maria@example.com']);
    User::factory()->create(['name' => 'Joao Santos', 'email' => 'joao@test.com']);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->set('search', 'maria@example')
        ->assertSee('Maria Silva')
        ->assertDontSee('Joao Santos');
});

test('CustomerList displays order count', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Cliente Pedidos']);

    Order::factory()->count(3)->create(['user_id' => $user->id]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('Cliente Pedidos')
        ->assertSee('3');
});

test('CustomerList displays total spent', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create(['name' => 'Cliente Valor']);

    Order::factory()->create(['user_id' => $user->id, 'total' => 10000]);
    Order::factory()->create(['user_id' => $user->id, 'total' => 15000]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('R$ 250,00');
});

test('CustomerList orders by most recent first by default', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    User::factory()->create(['name' => 'Cliente Antigo', 'created_at' => now()->subMonth()]);
    User::factory()->create(['name' => 'Cliente Novo', 'created_at' => now()]);

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(CustomerList::class);
    $customers = $component->get('customers');

    expect($customers->first()->name)->toBe('Cliente Novo');
});

test('CustomerList can sort by name', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->call('sortBy', 'name')
        ->assertSet('sortField', 'name')
        ->assertSet('sortDirection', 'asc');
});

test('CustomerList displays pagination', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    User::factory()->count(20)->create();

    actingAsAdminWith2FA($this, $admin);

    $component = Livewire::test(CustomerList::class);
    $customers = $component->get('customers');

    expect($customers->count())->toBe(15);
    expect($customers->hasMorePages())->toBeTrue();
});

test('CustomerList shows empty state when no customers', function () {
    $admin = Admin::factory()->withTwoFactor()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('Nenhum cliente encontrado');
});

test('CustomerList has link to customer details', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create();

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSeeHtml(route('admin.customers.show', $user));
});

test('CustomerList displays customer since date', function () {
    $admin = Admin::factory()->withTwoFactor()->create();
    $user  = User::factory()->create([
        'name'       => 'Cliente Data',
        'created_at' => now()->subDays(30),
    ]);

    actingAsAdminWith2FA($this, $admin);

    Livewire::test(CustomerList::class)
        ->assertSee('Cliente Data')
        ->assertSee(now()->subDays(30)->format('d/m/Y'));
});
