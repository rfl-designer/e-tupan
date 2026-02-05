<?php

declare(strict_types = 1);

use App\Domain\Cart\Services\CartService;
use App\Domain\Catalog\Models\Product;
use App\Domain\Shipping\Contracts\ShippingProviderInterface;
use App\Domain\Shipping\Services\ShippingService;
use App\Models\User;

describe('ShippingService', function () {
    beforeEach(function () {
        $this->user            = User::factory()->create();
        $this->cartService     = new CartService();
        $this->shippingService = app(ShippingService::class);
    });

    describe('getOptionsForCart', function () {
        it('returns shipping options for cart with items', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'weight'         => 0.5,
                'length'         => 20,
                'width'          => 15,
                'height'         => 5,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 2);

            $options = $this->shippingService->getOptionsForCart($cart, '01310-100');

            expect($options)->not->toBeEmpty()
                ->and($options[0]->code)->toBe('pac')
                ->and($options[0]->price)->toBeInt();
        });

        it('returns empty options for invalid zipcode', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 1);

            $options = $this->shippingService->getOptionsForCart($cart, '123');

            expect($options)->toBeEmpty();
        });
    });

    describe('applyToCart', function () {
        it('applies shipping to cart', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 2);

            $this->shippingService->applyToCart($cart, 'pac', '01310-100');

            $cart->refresh();

            expect($cart->shipping_method)->toBe('pac')
                ->and($cart->shipping_zipcode)->toBe('01310-100')
                ->and($cart->shipping_cost)->toBeInt()
                ->and($cart->shipping_cost)->toBeGreaterThan(0)
                ->and($cart->shipping_days)->toBeInt();
        });

        it('updates cart total with shipping cost', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 1);

            $subtotalBefore = $cart->subtotal;

            $this->shippingService->applyToCart($cart, 'pac', '01310-100');

            $cart->refresh();

            expect($cart->total)->toBe($subtotalBefore + $cart->shipping_cost);
        });

        it('throws exception for invalid shipping option', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 1);

            $this->shippingService->applyToCart($cart, 'invalid_option', '01310-100');
        })->throws(InvalidArgumentException::class);
    });

    describe('removeFromCart', function () {
        it('removes shipping from cart', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 1);

            $this->shippingService->applyToCart($cart, 'pac', '01310-100');
            $this->shippingService->removeFromCart($cart);

            $cart->refresh();

            expect($cart->shipping_method)->toBeNull()
                ->and($cart->shipping_zipcode)->toBeNull()
                ->and($cart->shipping_cost)->toBeNull()
                ->and($cart->shipping_days)->toBeNull();
        });

        it('updates cart total after removing shipping', function () {
            $product = Product::factory()->active()->simple()->create([
                'price'          => 5000,
                'stock_quantity' => 10,
            ]);

            $cart = $this->cartService->getOrCreate(userId: $this->user->id);
            $this->cartService->addItem($cart, $product, 1);

            $subtotalBefore = $cart->subtotal;

            $this->shippingService->applyToCart($cart, 'pac', '01310-100');
            $this->shippingService->removeFromCart($cart);

            $cart->refresh();

            expect($cart->total)->toBe($subtotalBefore);
        });
    });

    describe('provider information', function () {
        it('checks if provider is available', function () {
            expect($this->shippingService->isProviderAvailable())->toBeTrue();
        });

        it('returns provider name', function () {
            expect($this->shippingService->getProviderName())->toBe('Mock Shipping Provider');
        });
    });

    describe('service container binding', function () {
        it('resolves ShippingProviderInterface from container', function () {
            $provider = app(ShippingProviderInterface::class);

            expect($provider)->toBeInstanceOf(ShippingProviderInterface::class);
        });

        it('resolves ShippingService from container', function () {
            $service = app(ShippingService::class);

            expect($service)->toBeInstanceOf(ShippingService::class);
        });
    });
});
