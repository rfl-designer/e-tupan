<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Livewire\CheckoutPage;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

it('redirects to cart if cart is empty', function () {
    $this->get(route('checkout.index'))
        ->assertRedirect(route('cart.index'));
});

it('validates cart before showing checkout', function () {
    // Test that validation occurs on mount - this validates stock
    // The session-based cart functionality is the same as user-based
    // This test validates the CartValidationService is called
    $user    = User::factory()->create();
    $product = Product::factory()->active()->withStock(10)->create();

    $cart = Cart::factory()->for($user)->create();

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => $product->getCurrentPrice(),
    ]);

    // No validation alerts for valid cart
    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->assertSet('validationAlerts', []);
});

it('allows access to checkout with items in user cart', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->active()->withStock(10)->create();

    $cart = Cart::factory()->for($user)->create();

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => $product->getCurrentPrice(),
    ]);

    $this->actingAs($user)
        ->get(route('checkout.index'))
        ->assertOk()
        ->assertSeeLivewire(CheckoutPage::class);
});

it('shows checkout steps to guest users', function () {
    $product = Product::factory()->active()->withStock(10)->create();

    $cart = Cart::factory()->create([
        'user_id'    => null,
        'session_id' => session()->getId(),
    ]);

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => $product->getCurrentPrice(),
    ]);

    Livewire::test(CheckoutPage::class)
        ->assertSet('currentStep', 'identification')
        ->assertSet('isAuthenticated', false);
});

it('skips identification step for authenticated users with address', function () {
    $user = User::factory()->create();
    $user->addresses()->create([
        'label'          => 'Home',
        'recipient_name' => 'John Doe',
        'zipcode'        => '01310-100',
        'street'         => 'Av Paulista',
        'number'         => '1000',
        'neighborhood'   => 'Bela Vista',
        'city'           => 'Sao Paulo',
        'state'          => 'SP',
        'is_default'     => true,
    ]);

    $product = Product::factory()->active()->withStock(10)->create();
    $cart    = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => $product->getCurrentPrice(),
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->assertSet('currentStep', 'shipping')
        ->assertSet('isAuthenticated', true);
});

it('shows address step for authenticated users without default address', function () {
    $user    = User::factory()->create();
    $product = Product::factory()->active()->withStock(10)->create();
    $cart    = Cart::factory()->for($user)->create();
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => $product->getCurrentPrice(),
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutPage::class)
        ->assertSet('currentStep', 'address')
        ->assertSet('isAuthenticated', true);
});

it('calculates cart totals correctly', function () {
    $product = Product::factory()->active()->withStock(10)->create([
        'price' => 10000, // R$ 100.00 in cents
    ]);

    $cart = Cart::factory()->create([
        'user_id'    => null,
        'session_id' => session()->getId(),
    ]);

    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 2,
        'unit_price' => 10000,
    ]);

    $cart->recalculateTotals();

    Livewire::test(CheckoutPage::class)
        ->assertSet('subtotal', 20000);
});
