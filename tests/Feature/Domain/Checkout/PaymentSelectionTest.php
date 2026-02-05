<?php

declare(strict_types = 1);

use App\Domain\Checkout\Enums\PaymentMethod;
use App\Domain\Checkout\Livewire\CheckoutPayment;
use Livewire\Livewire;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

describe('Payment Method Selection', function () {
    it('renders available payment methods', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->assertSee('Cartao de Credito')
            ->assertSee('Pix')
            ->assertSee('Boleto Bancario');
    });

    it('shows payment method icons', function () {
        $component = Livewire::test(CheckoutPayment::class, ['total' => 10000]);

        foreach (PaymentMethod::cases() as $method) {
            $component->assertSee($method->label());
        }
    });

    it('can select credit card payment method', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->assertSet('paymentMethod', 'credit_card');
    });

    it('can select pix payment method', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'pix')
            ->assertSet('paymentMethod', 'pix');
    });

    it('can select bank slip payment method', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'bank_slip')
            ->assertSet('paymentMethod', 'bank_slip');
    });

    it('validates payment method selection before continuing', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('continueToReview')
            ->assertHasErrors(['paymentMethod']);
    });

    it('resets card data when changing payment method', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->set('cardData.number', '4111 1111 1111 1111')
            ->set('cardData.name', 'John Doe')
            ->call('selectMethod', 'pix')
            ->assertSet('cardData.number', '')
            ->assertSet('cardData.name', '');
    });
});

describe('Credit Card Form', function () {
    it('shows credit card form when selected', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->assertSee('Numero do cartao')
            ->assertSee('Nome impresso no cartao')
            ->assertSee('Validade')
            ->assertSee('CVV');
    });

    it('formats card number as user types', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->set('cardData.number', '4111111111111111')
            ->assertSet('cardData.number', '4111 1111 1111 1111');
    });

    it('formats expiry date as user types', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->set('cardData.expiry', '1225')
            ->assertSet('cardData.expiry', '12/25');
    });

    it('limits cvv to 4 digits', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->set('cardData.cvv', '12345')
            ->assertSet('cardData.cvv', '1234');
    });

    it('validates card data before continuing', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->call('continueToReview')
            ->assertHasErrors(['cardData.number', 'cardData.name', 'cardData.expiry', 'cardData.cvv']);
    });

    it('validates card number format', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->set('cardData.number', '4111')
            ->set('cardData.name', 'John Doe')
            ->set('cardData.expiry', '12/25')
            ->set('cardData.cvv', '123')
            ->call('continueToReview')
            ->assertHasErrors(['cardData.number']);
    });

    it('dispatches event when credit card data is valid', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'credit_card')
            ->set('cardData.number', '4111 1111 1111 1111')
            ->set('cardData.name', 'John Doe')
            ->set('cardData.expiry', '12/25')
            ->set('cardData.cvv', '123')
            ->call('continueToReview')
            ->assertDispatched('payment-method-selected', 'credit_card');
    });
});

describe('Installment Options', function () {
    it('shows installment options for credit card', function () {
        $component = Livewire::test(CheckoutPayment::class, ['total' => 100000]); // R$ 1000

        $options = $component->get('installmentOptions');

        // R$1000 permite ate 12 parcelas (min R$10 = 100 parcelas teoricas, mas max e 12)
        expect($options)->toHaveCount(12);
        expect($options[0]['value'])->toBe(1);
        expect($options[0]['label'])->toContain('A vista');
    });

    it('limits installments to minimum value', function () {
        $component = Livewire::test(CheckoutPayment::class, ['total' => 5000]); // R$ 50

        $options = $component->get('installmentOptions');

        // Com R$50, maximo de 5 parcelas (R$10 minimo)
        expect($options)->toHaveCount(5);
    });

    it('calculates installment values correctly', function () {
        $component = Livewire::test(CheckoutPayment::class, ['total' => 12000]); // R$ 120

        $options = $component->get('installmentOptions');

        expect($options[1]['value'])->toBe(2);
        expect($options[1]['amount'])->toBe(6000); // R$ 60 each
    });

    it('shows interest-free label', function () {
        $component = Livewire::test(CheckoutPayment::class, ['total' => 24000]); // R$ 240

        $options = $component->get('installmentOptions');

        expect($options[1]['label'])->toContain('sem juros');
    });
});

describe('Pix Payment', function () {
    it('shows pix benefits when selected', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'pix')
            ->assertSee('Aprovacao imediata');
    });

    it('dispatches event for pix selection', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'pix')
            ->call('continueToReview')
            ->assertDispatched('payment-method-selected', 'pix');
    });
});

describe('Bank Slip Payment', function () {
    it('shows bank slip info when selected', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'bank_slip')
            ->assertSee('dias');
    });

    it('dispatches event for bank slip selection', function () {
        Livewire::test(CheckoutPayment::class, ['total' => 10000])
            ->call('selectMethod', 'bank_slip')
            ->call('continueToReview')
            ->assertDispatched('payment-method-selected', 'bank_slip');
    });
});
