<?php

declare(strict_types = 1);

use App\Domain\Cart\Enums\CartStatus;
use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Domain\Checkout\DTOs\CardData;
use App\Domain\Checkout\Enums\{OrderStatus, PaymentMethod, PaymentStatus};
use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Models\Order;
use App\Domain\Checkout\Services\CheckoutService;
use App\Models\User;
use Illuminate\Support\Facades\Event;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function () {
    $this->checkoutService = app(CheckoutService::class);

    // Create user with cart
    $this->user = User::factory()->create([
        'name'  => 'John Doe',
        'email' => 'john@example.com',
    ]);

    // Create product with stock
    $this->product = Product::factory()->withStock(10)->create([
        'name'  => 'Test Product',
        'price' => 5000, // R$ 50.00
    ]);

    // Create cart with item
    $this->cart = Cart::factory()->for($this->user)->create();
    CartItem::factory()->for($this->cart)->create([
        'product_id' => $this->product->id,
        'quantity'   => 2,
        'unit_price' => $this->product->price,
    ]);
    $this->cart->recalculateTotals();
});

describe('Order Creation', function () {
    it('creates order from cart for authenticated user', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_complement'     => 'Sala 101',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500, // R$ 25.00
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        expect($order)->toBeInstanceOf(Order::class);
        expect($order->user_id)->toBe($this->user->id);
        expect($order->status)->toBe(OrderStatus::Pending);
        expect($order->subtotal)->toBe(10000); // 2 x R$ 50
        expect($order->shipping_cost)->toBe(2500);
        expect($order->total)->toBe(12500);
        expect($order->items)->toHaveCount(1);
    });

    it('creates order from cart for guest user', function () {
        // Create guest cart (no user)
        $guestCart = Cart::factory()->create(['user_id' => null]);
        CartItem::factory()->for($guestCart)->create([
            'product_id' => $this->product->id,
            'quantity'   => 1,
            'unit_price' => $this->product->price,
        ]);
        $guestCart->recalculateTotals();

        $checkoutData = [
            'guest_name'              => 'Jane Guest',
            'guest_email'             => 'jane@guest.com',
            'guest_cpf'               => '123.456.789-00',
            'guest_phone'             => '(11) 99999-9999',
            'shipping_recipient_name' => 'Jane Guest',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '500',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'pac',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 7,
            'shipping_cost'           => 1500,
        ];

        $order = $this->checkoutService->createOrder($guestCart, $checkoutData);

        expect($order->user_id)->toBeNull();
        expect($order->guest_email)->toBe('jane@guest.com');
        expect($order->guest_name)->toBe('Jane Guest');
        expect($order->guest_cpf)->toBe('123.456.789-00');
        expect($order->customerEmail)->toBe('jane@guest.com');
    });

    it('generates unique order number', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        expect($order->order_number)->toStartWith('ORD-');
        expect(strlen($order->order_number))->toBeGreaterThan(6);
    });

    it('snapshots product data in order items', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order     = $this->checkoutService->createOrder($this->cart, $checkoutData);
        $orderItem = $order->items->first();

        expect($orderItem->product_name)->toBe('Test Product');
        expect($orderItem->unit_price)->toBe(5000);
        expect($orderItem->quantity)->toBe(2);
        expect($orderItem->subtotal)->toBe(10000);
    });

    it('dispatches order created event', function () {
        Event::fake([OrderCreated::class]);

        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        Event::assertDispatched(OrderCreated::class, function (OrderCreated $event) use ($order) {
            return $event->order->id === $order->id;
        });
    });

    it('applies discount when coupon is used', function () {
        // This would be tested with coupon integration
        // For now just verify the discount field exists
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
            'discount'                => 1000, // R$ 10 discount
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        expect($order->discount)->toBe(1000);
        expect($order->total)->toBe(11500); // 10000 + 2500 - 1000
    });
});

describe('Payment Processing', function () {
    it('processes credit card payment successfully', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        $cardData = new CardData(
            token: 'approved_token',
            holderName: 'John Doe',
            installments: 1,
            cardBrand: 'visa',
            lastFourDigits: '1234',
        );

        $payment = $this->checkoutService->processPayment(
            $order,
            PaymentMethod::CreditCard,
            $cardData,
        );

        expect($payment->method)->toBe(PaymentMethod::CreditCard);
        expect($payment->status)->toBe(PaymentStatus::Approved);
        expect($payment->amount)->toBe($order->total);
    });

    it('handles declined credit card payment', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        $cardData = new CardData(
            token: 'test_declined_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $payment = $this->checkoutService->processPayment(
            $order,
            PaymentMethod::CreditCard,
            $cardData,
        );

        expect($payment->status)->toBe(PaymentStatus::Declined);
    });

    it('generates pix payment data', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        $payment = $this->checkoutService->processPayment(
            $order,
            PaymentMethod::Pix,
        );

        expect($payment->method)->toBe(PaymentMethod::Pix);
        expect($payment->status)->toBe(PaymentStatus::Pending);
        expect($payment->pix_qr_code)->not->toBeNull();
        expect($payment->expires_at)->not->toBeNull();
    });

    it('generates bank slip payment data', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        $payment = $this->checkoutService->processPayment(
            $order,
            PaymentMethod::BankSlip,
        );

        expect($payment->method)->toBe(PaymentMethod::BankSlip);
        expect($payment->status)->toBe(PaymentStatus::Pending);
        expect($payment->bank_slip_barcode)->not->toBeNull();
        expect($payment->bank_slip_url)->not->toBeNull();
        expect($payment->expires_at)->not->toBeNull();
    });
});

describe('Order Completion', function () {
    it('marks cart as converted after checkout', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $cardData = new CardData(
            token: 'approved_token',
            holderName: 'John Doe',
            installments: 1,
        );

        $result = $this->checkoutService->completeCheckout(
            $this->cart,
            $checkoutData,
            PaymentMethod::CreditCard,
            $cardData,
        );

        $this->cart->refresh();
        expect($this->cart->status)->toBe(CartStatus::Converted);
    });

    it('stores cart_id reference in order', function () {
        $checkoutData = [
            'shipping_recipient_name' => 'John Doe',
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_method'         => 'sedex',
            'shipping_carrier'        => 'Correios',
            'shipping_days'           => 3,
            'shipping_cost'           => 2500,
        ];

        $order = $this->checkoutService->createOrder($this->cart, $checkoutData);

        expect($order->cart_id)->toBe($this->cart->id);
    });
});
