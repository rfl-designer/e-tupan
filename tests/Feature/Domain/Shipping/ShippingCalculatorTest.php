<?php

declare(strict_types = 1);

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shipping\Livewire\ShippingCalculator;
use App\Models\User;
use Livewire\Livewire;

describe('ShippingCalculator Component', function () {
    beforeEach(function () {
        $this->user    = User::factory()->create();
        $this->product = Product::factory()->active()->simple()->create([
            'price'          => 5000,
            'stock_quantity' => 10,
            'weight'         => 0.5,
            'length'         => 20,
            'width'          => 15,
            'height'         => 5,
        ]);

        $cartService = new CartService();
        $this->cart  = $cartService->getOrCreate(userId: $this->user->id);
        $cartService->addItem($this->cart, $this->product, 2);
    });

    describe('rendering', function () {
        it('renders successfully', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->assertStatus(200);
        });

        it('shows zipcode input', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->assertSee('Calcular Frete')
                ->assertSee('Calcular');
        });
    });

    describe('zipcode validation', function () {
        it('requires zipcode to calculate', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->call('calculateShipping')
                ->assertHasErrors(['zipcode']);
        });

        it('validates zipcode format', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '123')
                ->call('calculateShipping')
                ->assertHasErrors(['zipcode']);
        });

        it('formats zipcode with mask', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310100')
                ->assertSet('zipcode', '01310-100');
        });

        it('handles partial zipcode', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '0131')
                ->assertSet('zipcode', '0131');
        });
    });

    describe('shipping calculation', function () {
        it('calculates shipping options for valid zipcode', function () {
            $component = Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSet('hasCalculated', true)
                ->assertSet('errorMessage', '');

            expect($component->get('options'))->not->toBeEmpty();
        });

        it('shows error for empty cart when calculating', function () {
            $emptyUser = User::factory()->create();

            Livewire::actingAs($emptyUser)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSet('errorMessage', 'Adicione itens ao carrinho para calcular o frete.');
        });

        it('displays PAC option', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSee('PAC');
        });

        it('displays SEDEX option', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSee('SEDEX');
        });
    });

    describe('option selection', function () {
        it('selects shipping option', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'pac')
                ->assertSet('selectedOption', 'pac')
                ->assertDispatched('cart-updated')
                ->assertDispatched('shipping-selected');
        });

        it('updates cart with selected shipping', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'sedex');

            $this->cart->refresh();

            expect($this->cart->shipping_method)->toBe('sedex')
                ->and($this->cart->shipping_zipcode)->toBe('01310-100')
                ->and($this->cart->shipping_cost)->toBeGreaterThan(0);
        });

        it('can change shipping option', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'pac')
                ->assertSet('selectedOption', 'pac')
                ->call('selectOption', 'sedex')
                ->assertSet('selectedOption', 'sedex');
        });
    });

    describe('clear shipping', function () {
        it('clears shipping selection', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'pac')
                ->call('clearShipping')
                ->assertSet('selectedOption', '')
                ->assertSet('hasCalculated', false)
                ->assertSet('zipcode', '')
                ->assertDispatched('cart-updated')
                ->assertDispatched('shipping-cleared');
        });

        it('removes shipping from cart', function () {
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'pac')
                ->call('clearShipping');

            $this->cart->refresh();

            expect($this->cart->shipping_method)->toBeNull()
                ->and($this->cart->shipping_zipcode)->toBeNull()
                ->and($this->cart->shipping_cost)->toBeNull();
        });
    });

    describe('existing shipping', function () {
        it('loads existing shipping on mount', function () {
            // First, apply shipping
            $component = Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->call('selectOption', 'pac');

            // Create new instance - should load existing shipping
            Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->assertSet('zipcode', '01310-100')
                ->assertSet('selectedOption', 'pac')
                ->assertSet('hasCalculated', true);
        });
    });

    describe('cart events', function () {
        it('refreshes on cart-updated event', function () {
            $component = Livewire::actingAs($this->user)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSet('hasCalculated', true);

            // Dispatch event
            $component->dispatch('cart-updated')
                ->assertSet('hasCalculated', true);
        });
    });

    describe('empty cart', function () {
        it('shows error when cart is empty', function () {
            $emptyUser = User::factory()->create();

            Livewire::actingAs($emptyUser)
                ->test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSet('errorMessage', 'Adicione itens ao carrinho para calcular o frete.');
        });
    });

    describe('guest users', function () {
        it('works for guest users with session cart', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            // Create a guest cart
            $component = Livewire::test(ShippingCalculator::class);
            $sessionId = session()->getId();

            $cartService = new CartService();
            $cart        = $cartService->getOrCreate(sessionId: $sessionId);
            $cartService->addItem($cart, $product, 1);

            // Test shipping calculation
            Livewire::test(ShippingCalculator::class)
                ->set('zipcode', '01310-100')
                ->call('calculateShipping')
                ->assertSet('hasCalculated', true);
        });
    });
});
