<?php declare(strict_types = 1);

use App\Domain\Customer\Livewire\CustomerDashboard;
use App\Domain\Customer\Models\Address;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create([
        'name'  => 'Joao Silva',
        'email' => 'joao@example.com',
        'cpf'   => '12345678901',
        'phone' => '(11) 99999-9999',
    ]);
});

test('guest cannot access dashboard', function () {
    $this->get(route('customer.dashboard'))
        ->assertRedirect(route('login'));
});

test('authenticated user can view dashboard', function () {
    $this->actingAs($this->user)
        ->get(route('customer.dashboard'))
        ->assertOk()
        ->assertSeeLivewire(CustomerDashboard::class);
});

test('dashboard shows user data', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Joao Silva')
        ->assertSee('joao@example.com')
        ->assertSee('(11) 99999-9999');
});

test('dashboard shows masked cpf', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('***.***')
        ->assertDontSee('12345678901');
});

test('dashboard shows address count', function () {
    Address::factory()->count(3)->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('3 enderecos cadastrados');
});

test('dashboard shows singular address count', function () {
    Address::factory()->create(['user_id' => $this->user->id]);

    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('1 endereco cadastrado');
});

test('dashboard shows zero addresses message', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('0 enderecos cadastrados');
});

test('dashboard shows default address', function () {
    Address::factory()->default()->create([
        'user_id'      => $this->user->id,
        'street'       => 'Avenida Paulista',
        'number'       => '1000',
        'neighborhood' => 'Bela Vista',
        'city'         => 'Sao Paulo',
        'state'        => 'SP',
    ]);

    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Endereco padrao:')
        ->assertSee('Avenida Paulista')
        ->assertSee('1000')
        ->assertSee('Bela Vista')
        ->assertSee('Sao Paulo/SP');
});

test('dashboard shows default address with complement', function () {
    Address::factory()->default()->create([
        'user_id'      => $this->user->id,
        'street'       => 'Rua Augusta',
        'number'       => '500',
        'complement'   => 'Apto 101',
        'neighborhood' => 'Consolacao',
        'city'         => 'Sao Paulo',
        'state'        => 'SP',
    ]);

    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Rua Augusta')
        ->assertSee('500')
        ->assertSee('Apto 101');
});

test('dashboard shows placeholder for orders', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Meus Pedidos')
        ->assertSee('Nenhum pedido realizado ainda.')
        ->assertSee('Ir as Compras');
});

test('dashboard shows security section', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Seguranca')
        ->assertSee('Alterar Senha')
        ->assertSee('2FA');
});

test('dashboard shows 2fa disabled status', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('2FA desativado');
});

test('dashboard shows 2fa enabled status', function () {
    $userWith2fa = User::factory()->withTwoFactor()->create();

    Livewire::actingAs($userWith2fa)
        ->test(CustomerDashboard::class)
        ->assertSee('2FA ativado');
});

test('dashboard shows edit profile link', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Editar Dados');
});

test('dashboard shows manage addresses link', function () {
    Livewire::actingAs($this->user)
        ->test(CustomerDashboard::class)
        ->assertSee('Gerenciar Enderecos');
});

test('dashboard does not show cpf when user has no cpf', function () {
    $userWithoutCpf = User::factory()->create(['cpf' => null]);

    Livewire::actingAs($userWithoutCpf)
        ->test(CustomerDashboard::class)
        ->assertDontSee('CPF:');
});

test('dashboard does not show phone when user has no phone', function () {
    $userWithoutPhone = User::factory()->create(['phone' => null]);

    Livewire::actingAs($userWithoutPhone)
        ->test(CustomerDashboard::class)
        ->assertDontSee('Telefone:');
});

test('dashboard page has correct title', function () {
    $this->actingAs($this->user)
        ->get(route('customer.dashboard'))
        ->assertOk()
        ->assertSee('Minha Conta');
});
