<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Livewire\{CheckoutIdentification, CheckoutPage};
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->active()->withStock(10)->create([
        'price' => 10000,
    ]);

    $this->cart = Cart::factory()->create([
        'user_id'    => null,
        'session_id' => session()->getId(),
    ]);

    $this->cart->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);
});

it('renders the identification form for guests', function () {
    Livewire::test(CheckoutIdentification::class)
        ->assertSee('E-mail')
        ->assertSee('Nome')
        ->assertSee('CPF');
});

it('validates required fields for guest checkout', function () {
    Livewire::test(CheckoutIdentification::class)
        ->call('continueAsGuest')
        ->assertHasErrors(['email', 'name', 'cpf']);
});

it('validates email format', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('email', 'invalid-email')
        ->call('continueAsGuest')
        ->assertHasErrors(['email']);
});

it('validates CPF format', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('email', 'test@example.com')
        ->set('name', 'Test User')
        ->set('cpf', '123.456.789-00') // Invalid CPF
        ->call('continueAsGuest')
        ->assertHasErrors(['cpf']);
});

it('accepts valid CPF', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('email', 'test@example.com')
        ->set('name', 'Test User')
        ->set('cpf', '529.982.247-25') // Valid CPF
        ->call('continueAsGuest')
        ->assertHasNoErrors(['cpf']);
});

it('formats CPF as user types', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('cpf', '52998224725')
        ->assertSet('cpf', '529.982.247-25');
});

it('formats phone as user types', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('phone', '11999998888')
        ->assertSet('phone', '(11) 99999-8888');
});

it('dispatches guest data on successful submission', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('email', 'test@example.com')
        ->set('name', 'Test User')
        ->set('cpf', '529.982.247-25')
        ->set('phone', '(11) 99999-8888')
        ->call('continueAsGuest')
        ->assertDispatched('guest-data-submitted');
});

it('checks if email already exists', function () {
    $user = User::factory()->create(['email' => 'existing@example.com']);

    Livewire::test(CheckoutIdentification::class)
        ->set('email', 'existing@example.com')
        ->assertSet('existingAccount', true);
});

it('shows login form when user has existing account', function () {
    Livewire::test(CheckoutIdentification::class)
        ->set('showLoginForm', true)
        ->assertSee('Senha');
});

it('can login during checkout', function () {
    $user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CheckoutIdentification::class)
        ->set('loginEmail', 'test@example.com')
        ->set('loginPassword', 'password')
        ->call('login')
        ->assertDispatched('checkout-user-logged-in');

    expect(auth()->check())->toBeTrue();
    expect(auth()->user()->email)->toBe('test@example.com');
});

it('shows error for invalid credentials', function () {
    $user = User::factory()->create([
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    Livewire::test(CheckoutIdentification::class)
        ->set('loginEmail', 'test@example.com')
        ->set('loginPassword', 'wrong-password')
        ->call('login')
        ->assertHasErrors(['loginPassword']);
});

it('handles guest data submission from parent component', function () {
    Livewire::test(CheckoutPage::class)
        ->dispatch('guest-data-submitted', [
            'email' => 'test@example.com',
            'name'  => 'Test User',
            'cpf'   => '529.982.247-25',
            'phone' => '(11) 99999-8888',
        ])
        ->assertSet('currentStep', 'address')
        ->assertSet('guestData.email', 'test@example.com')
        ->assertSet('guestData.name', 'Test User');
});

it('handles user login from parent component', function () {
    $user = User::factory()->create();
    $cart = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->dispatch('checkout-user-logged-in')
        ->assertSet('isAuthenticated', true);
});
