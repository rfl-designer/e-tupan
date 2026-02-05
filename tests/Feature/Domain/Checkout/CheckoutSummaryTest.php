<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Livewire\CheckoutSummary;
use App\Models\User;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    $this->product = Product::factory()->active()->withStock(10)->create([
        'name'  => 'Test Product',
        'price' => 10000,
    ]);

    $this->cart = Cart::factory()->for($this->user)->create();

    $this->cart->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 2,
        'unit_price' => 10000,
    ]);

    $this->cart->recalculateTotals();
});

it('renders cart items in summary', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class)
        ->assertSee('Test Product')
        ->assertSee('2');
});

it('shows subtotal correctly', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class)
        ->assertSet('subtotal', 20000);
});

it('shows shipping cost when provided', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class, [
            'shippingCost'   => 1500,
            'shippingMethod' => 'PAC - Correios',
        ])
        ->assertSet('shippingCost', 1500);
});

it('calculates total with shipping', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class, [
            'shippingCost' => 1500,
        ])
        ->assertSet('total', 21500);
});

it('shows discount when applied', function () {
    $this->cart->update(['discount' => 5000]);

    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class)
        ->assertSet('discount', 5000);
});

it('calculates total with discount', function () {
    $this->cart->update(['discount' => 5000]);

    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class, [
            'shippingCost' => 1500,
        ])
        ->assertSet('total', 16500); // 20000 - 5000 + 1500
});

it('shows shipping address when provided', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class, [
            'addressData' => [
                'shipping_recipient_name' => 'John Doe',
                'shipping_street'         => 'Av Paulista',
                'shipping_number'         => '1000',
                'shipping_neighborhood'   => 'Bela Vista',
                'shipping_city'           => 'Sao Paulo',
                'shipping_state'          => 'SP',
                'shipping_zipcode'        => '01310-100',
            ],
        ])
        ->assertSee('Av Paulista')
        ->assertSee('Sao Paulo');
});

it('shows delivery estimate when provided', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class, [
            'shippingMethod' => 'PAC - Correios',
            'deliveryDays'   => 7,
        ])
        ->assertSee('PAC - Correios');
});

it('shows item count correctly', function () {
    Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class)
        ->assertSet('itemCount', 2);
});

it('refreshes when cart is updated', function () {
    $component = Livewire::actingAs($this->user)
        ->test(CheckoutSummary::class)
        ->assertSet('subtotal', 20000);

    // Add another item
    $this->cart->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);
    $this->cart->recalculateTotals();

    $component->dispatch('cart-updated')
        ->assertSet('subtotal', 30000);
});
