<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Livewire\{CheckoutPage, CheckoutShipping};
use App\Domain\Shipping\DTOs\ShippingOption;
use App\Domain\Shipping\Services\ShippingService;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->active()->withStock(10)->create([
        'price'  => 10000,
        'weight' => 500, // 500g
    ]);

    $this->cart = Cart::factory()->create([
        'user_id'    => null,
        'session_id' => session()->getId(),
        'subtotal'   => 10000,
    ]);

    $this->cart->items()->create([
        'product_id' => $this->product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);

    // Mock ShippingService
    $this->mock(ShippingService::class, function ($mock) {
        $mock->shouldReceive('calculateShipping')
            ->andReturn([
                new ShippingOption(
                    code: 'pac',
                    name: 'PAC',
                    price: 1500,
                    deliveryDaysMin: 5,
                    deliveryDaysMax: 8,
                    carrier: 'Correios',
                ),
                new ShippingOption(
                    code: 'sedex',
                    name: 'SEDEX',
                    price: 3000,
                    deliveryDaysMin: 2,
                    deliveryDaysMax: 3,
                    carrier: 'Correios',
                ),
            ]);
    });
});

it('renders shipping options', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->assertSee('PAC')
        ->assertSee('SEDEX');
});

it('validates CEP before calculating', function () {
    Livewire::test(CheckoutShipping::class)
        ->set('zipcode', '123')
        ->call('calculateShipping')
        ->assertSet('error', 'CEP invalido.');
});

it('calculates shipping for valid CEP', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->assertSet('error', null)
        ->assertCount('shippingOptions', 2);
});

it('auto-selects cheapest option', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->assertSet('selectedMethod', 'pac');
});

it('can select a shipping method', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->call('selectShipping', 'sedex')
        ->assertSet('selectedMethod', 'sedex')
        ->assertSet('shippingData.shipping_method', 'sedex')
        ->assertSet('shippingData.shipping_cost', 3000);
});

it('requires shipping method selection to continue', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->set('selectedMethod', null)
        ->set('shippingOptions', [])
        ->call('continueToPayment')
        ->assertSet('error', 'Selecione uma opcao de entrega.');
});

it('dispatches shipping method on continue', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->call('selectShipping', 'sedex')
        ->call('continueToPayment')
        ->assertDispatched('shipping-method-selected');
});

it('handles shipping method selection from parent component', function () {
    $product = Product::factory()->active()->withStock(10)->create();
    $cart    = Cart::factory()->create([
        'user_id'    => null,
        'session_id' => session()->getId(),
    ]);
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);

    Livewire::test(CheckoutPage::class)
        ->set('guestData', [
            'email' => 'test@example.com',
            'name'  => 'Test User',
            'cpf'   => '529.982.247-25',
            'phone' => '',
        ])
        ->set('addressData', [
            'shipping_zipcode' => '01310-100',
            'shipping_street'  => 'Av Paulista',
            'shipping_number'  => '1000',
            'shipping_city'    => 'Sao Paulo',
            'shipping_state'   => 'SP',
        ])
        ->set('currentStep', 'shipping')
        ->dispatch('shipping-method-selected', [
            'shipping_method'  => 'pac',
            'shipping_carrier' => 'Correios',
            'shipping_cost'    => 1500,
            'shipping_days'    => 7,
        ])
        ->assertSet('currentStep', 'payment')
        ->assertSet('shippingData.shipping_method', 'pac')
        ->assertSet('shippingData.shipping_cost', 1500);
});

it('shows loading state during calculation', function () {
    Livewire::test(CheckoutShipping::class)
        ->assertSet('isLoading', false);
});

it('pre-fills shipping data from mount', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode'      => '01310-100',
        'shippingData' => [
            'shipping_method'  => 'sedex',
            'shipping_carrier' => 'Correios',
            'shipping_cost'    => 3000,
            'shipping_days'    => 3,
        ],
    ])
        ->assertSet('selectedMethod', 'sedex');
});

it('marks fastest and cheapest options', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->assertSet('shippingOptions.0.is_cheapest', true)
        ->assertSet('shippingOptions.1.is_fastest', true);
});

it('saves shipping_quote_id when selecting method', function () {
    Livewire::test(CheckoutShipping::class, [
        'zipcode' => '01310-100',
    ])
        ->call('calculateShipping')
        ->call('selectShipping', 'sedex')
        ->assertSet('shippingData.shipping_quote_id', 'sedex');
});
