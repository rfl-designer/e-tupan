<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\Cart;
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\Livewire\{CheckoutAddress, CheckoutPage};
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->product = Product::factory()->active()->withStock(10)->create([
        'price' => 10000,
    ]);

    Http::fake([
        'viacep.com.br/*' => Http::response([
            'cep'        => '01310-100',
            'logradouro' => 'Avenida Paulista',
            'bairro'     => 'Bela Vista',
            'localidade' => 'Sao Paulo',
            'uf'         => 'SP',
        ], 200),
    ]);
});

it('renders the address form for guest users', function () {
    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->assertSee('CEP')
        ->assertSee('Endereco')
        ->assertSee('Numero');
});

it('shows saved addresses for authenticated users', function () {
    $user    = User::factory()->create();
    $address = $user->addresses()->create([
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

    Livewire::actingAs($user)
        ->test(CheckoutAddress::class, ['isAuthenticated' => true])
        ->assertSee('John Doe')
        ->assertSee('Av Paulista');
});

it('validates required fields', function () {
    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->call('continueToShipping')
        ->assertHasErrors([
            'form.shipping_recipient_name',
            'form.shipping_zipcode',
            'form.shipping_street',
            'form.shipping_number',
            'form.shipping_neighborhood',
            'form.shipping_city',
            'form.shipping_state',
        ]);
});

it('formats zipcode as user types', function () {
    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->set('form.shipping_zipcode', '01310100')
        ->assertSet('form.shipping_zipcode', '01310-100');
});

it('auto-fills address from ViaCEP', function () {
    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->set('form.shipping_zipcode', '01310100')
        ->assertSet('form.shipping_street', 'Avenida Paulista')
        ->assertSet('form.shipping_neighborhood', 'Bela Vista')
        ->assertSet('form.shipping_city', 'Sao Paulo')
        ->assertSet('form.shipping_state', 'SP');
});

it('handles CEP lookup error gracefully', function () {
    // Test that timeout/connection errors are handled
    Http::fake(function ($request) {
        throw new \Exception('Connection timeout');
    });

    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->set('form.shipping_zipcode', '99999-999')
        ->call('lookupCep')
        ->assertSet('cepError', 'Erro ao consultar CEP. Verifique sua conexao.');
});

it('selects saved address for authenticated users', function () {
    $user    = User::factory()->create();
    $address = $user->addresses()->create([
        'label'          => 'Work',
        'recipient_name' => 'Jane Doe',
        'zipcode'        => '04538-132',
        'street'         => 'Av Brigadeiro Faria Lima',
        'number'         => '2000',
        'neighborhood'   => 'Itaim Bibi',
        'city'           => 'Sao Paulo',
        'state'          => 'SP',
        'is_default'     => false,
    ]);

    Livewire::actingAs($user)
        ->test(CheckoutAddress::class, ['isAuthenticated' => true])
        ->call('selectAddress', (string) $address->id)
        ->assertSet('selectedAddressId', (string) $address->id)
        ->assertSet('form.shipping_recipient_name', 'Jane Doe')
        ->assertSet('form.shipping_street', 'Av Brigadeiro Faria Lima');
});

it('can add new address for authenticated users', function () {
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

    Livewire::actingAs($user)
        ->test(CheckoutAddress::class, ['isAuthenticated' => true])
        ->call('addNewAddress')
        ->assertSet('showNewAddressForm', true)
        ->assertSet('selectedAddressId', null)
        ->assertSet('form.shipping_recipient_name', '');
});

it('saves new address when checkbox is checked', function () {
    $user    = User::factory()->create();
    $cart    = Cart::factory()->for($user)->create();
    $product = Product::factory()->active()->withStock(10)->create();
    $cart->items()->create([
        'product_id' => $product->id,
        'quantity'   => 1,
        'unit_price' => 10000,
    ]);

    expect($user->addresses()->count())->toBe(0);

    Livewire::actingAs($user)
        ->test(CheckoutAddress::class, ['isAuthenticated' => true])
        ->set('showNewAddressForm', true)
        ->set('saveAddress', true)
        ->set('form.shipping_recipient_name', 'Test User')
        ->set('form.shipping_zipcode', '01310-100')
        ->set('form.shipping_street', 'Av Paulista')
        ->set('form.shipping_number', '1000')
        ->set('form.shipping_neighborhood', 'Bela Vista')
        ->set('form.shipping_city', 'Sao Paulo')
        ->set('form.shipping_state', 'SP')
        ->call('continueToShipping')
        ->assertDispatched('address-data-submitted');

    expect($user->fresh()->addresses()->count())->toBe(1);
    expect($user->fresh()->addresses()->first()->street)->toBe('Av Paulista');
});

it('dispatches address data on successful submission', function () {
    Livewire::test(CheckoutAddress::class, ['isAuthenticated' => false])
        ->set('form.shipping_recipient_name', 'Test User')
        ->set('form.shipping_zipcode', '01310-100')
        ->set('form.shipping_street', 'Av Paulista')
        ->set('form.shipping_number', '1000')
        ->set('form.shipping_neighborhood', 'Bela Vista')
        ->set('form.shipping_city', 'Sao Paulo')
        ->set('form.shipping_state', 'SP')
        ->call('continueToShipping')
        ->assertDispatched('address-data-submitted');
});

it('handles address data submission from parent component', function () {
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
        ->set('currentStep', 'address')
        ->dispatch('address-data-submitted', [
            'shipping_address_id'     => null,
            'shipping_recipient_name' => 'Test User',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_complement'     => '',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
        ])
        ->assertSet('currentStep', 'shipping')
        ->assertSet('addressData.shipping_street', 'Av Paulista');
});

it('prefills address data from mount parameter', function () {
    $addressData = [
        'shipping_recipient_name' => 'Prefilled Name',
        'shipping_zipcode'        => '01310-100',
        'shipping_street'         => 'Prefilled Street',
        'shipping_number'         => '123',
        'shipping_complement'     => 'Apt 1',
        'shipping_neighborhood'   => 'Prefilled Neighborhood',
        'shipping_city'           => 'Prefilled City',
        'shipping_state'          => 'SP',
    ];

    Livewire::test(CheckoutAddress::class, [
        'addressData'     => $addressData,
        'isAuthenticated' => false,
    ])
        ->assertSet('form.shipping_recipient_name', 'Prefilled Name')
        ->assertSet('form.shipping_street', 'Prefilled Street')
        ->assertSet('form.shipping_number', '123');
});
