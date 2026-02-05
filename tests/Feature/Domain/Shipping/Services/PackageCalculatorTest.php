<?php

declare(strict_types = 1);

use App\Domain\Cart\Models\{Cart, CartItem};
use App\Domain\Catalog\Models\Product;
use App\Domain\Shipping\Services\PackageCalculator;

beforeEach(function () {
    $this->calculator = new PackageCalculator();
});

describe('calculateVolumetricWeight', function () {
    it('calculates volumetric weight using formula L x W x H / 6000', function () {
        // 30cm x 20cm x 10cm = 6000 / 6000 = 1.0 kg
        $volumetricWeight = $this->calculator->calculateVolumetricWeight(30, 20, 10);

        expect($volumetricWeight)->toBe(1.0);
    });

    it('returns weight in kilograms', function () {
        // 60cm x 40cm x 25cm = 60000 / 6000 = 10.0 kg
        $volumetricWeight = $this->calculator->calculateVolumetricWeight(60, 40, 25);

        expect($volumetricWeight)->toBe(10.0);
    });

    it('handles small dimensions', function () {
        // 10cm x 10cm x 10cm = 1000 / 6000 = 0.167 kg (rounded to 3 decimals)
        $volumetricWeight = $this->calculator->calculateVolumetricWeight(10, 10, 10);

        expect($volumetricWeight)->toBeGreaterThan(0.16);
        expect($volumetricWeight)->toBeLessThan(0.17);
    });
});

describe('getBillableWeight', function () {
    it('uses real weight when greater than volumetric weight', function () {
        $realWeight = 5.0; // 5 kg
        $length     = 30;
        $width      = 20;
        $height     = 10; // Volumetric = 1.0 kg

        $billableWeight = $this->calculator->getBillableWeight($realWeight, $length, $width, $height);

        expect($billableWeight)->toBe(5.0);
    });

    it('uses volumetric weight when greater than real weight', function () {
        $realWeight = 0.5; // 0.5 kg
        $length     = 60;
        $width      = 40;
        $height     = 25; // Volumetric = 10.0 kg

        $billableWeight = $this->calculator->getBillableWeight($realWeight, $length, $width, $height);

        expect($billableWeight)->toBe(10.0);
    });

    it('returns real weight when equal to volumetric weight', function () {
        $realWeight = 1.0; // 1 kg
        $length     = 30;
        $width      = 20;
        $height     = 10; // Volumetric = 1.0 kg

        $billableWeight = $this->calculator->getBillableWeight($realWeight, $length, $width, $height);

        expect($billableWeight)->toBe(1.0);
    });
});

describe('calculateFromProduct', function () {
    it('calculates package for a single product with dimensions', function () {
        $product = Product::factory()->create([
            'weight' => 0.5,   // 0.5 kg
            'length' => 30,    // 30 cm
            'width'  => 20,     // 20 cm
            'height' => 10,    // 10 cm
        ]);

        $package = $this->calculator->calculateFromProduct($product, 1);

        expect($package['weight'])->toBe(500);       // grams
        expect($package['length'])->toBe(30);        // cm
        expect($package['width'])->toBe(20);         // cm
        expect($package['height'])->toBe(10);        // cm
        expect($package['billable_weight'])->toBe(1000); // 1kg volumetric > 0.5kg real
    });

    it('uses default dimensions when product has none', function () {
        $product = Product::factory()->create([
            'weight' => null,
            'length' => null,
            'width'  => null,
            'height' => null,
        ]);

        $package = $this->calculator->calculateFromProduct($product, 1);

        // Should use config defaults
        expect($package['weight'])->toBe(300);     // default 0.3kg = 300g
        expect($package['length'])->toBe(16);      // default
        expect($package['width'])->toBe(11);       // default
        expect($package['height'])->toBe(2);       // default
    });

    it('multiplies weight and height by quantity', function () {
        $product = Product::factory()->create([
            'weight' => 0.5,
            'length' => 30,
            'width'  => 20,
            'height' => 10,
        ]);

        $package = $this->calculator->calculateFromProduct($product, 3);

        expect($package['weight'])->toBe(1500);     // 500g x 3
        expect($package['height'])->toBe(30);       // 10cm x 3 (stacked)
        expect($package['length'])->toBe(30);       // Max dimension unchanged
        expect($package['width'])->toBe(20);        // Max dimension unchanged
    });
});

describe('calculateFromCartItems', function () {
    it('calculates package for multiple cart items', function () {
        $product1 = Product::factory()->create([
            'weight' => 0.5,
            'length' => 30,
            'width'  => 20,
            'height' => 10,
        ]);

        $product2 = Product::factory()->create([
            'weight' => 0.3,
            'length' => 25,
            'width'  => 15,
            'height' => 5,
        ]);

        $items = collect([
            ['product' => $product1, 'quantity' => 2],
            ['product' => $product2, 'quantity' => 1],
        ]);

        $package = $this->calculator->calculateFromCartItems($items);

        // Weight: (500g x 2) + (300g x 1) = 1300g
        expect($package['weight'])->toBe(1300);

        // Length: max(30, 25) = 30
        expect($package['length'])->toBe(30);

        // Width: max(20, 15) = 20
        expect($package['width'])->toBe(20);

        // Height: (10 x 2) + (5 x 1) = 25
        expect($package['height'])->toBe(25);
    });

    it('enforces minimum dimensions', function () {
        $product = Product::factory()->create([
            'weight' => 0.01,
            'length' => 5,
            'width'  => 5,
            'height' => 1,
        ]);

        $items = collect([
            ['product' => $product, 'quantity' => 1],
        ]);

        $package = $this->calculator->calculateFromCartItems($items);

        // Should enforce minimums from config
        expect($package['weight'])->toBeGreaterThanOrEqual(300);   // min 0.3kg
        expect($package['length'])->toBeGreaterThanOrEqual(11);    // min 11cm
        expect($package['width'])->toBeGreaterThanOrEqual(2);      // min 2cm
        expect($package['height'])->toBeGreaterThanOrEqual(2);     // min 2cm
    });
});

describe('calculateFromCart', function () {
    it('calculates package from Cart model', function () {
        $cart    = Cart::factory()->create();
        $product = Product::factory()->create([
            'weight' => 1.0,
            'length' => 40,
            'width'  => 30,
            'height' => 20,
        ]);

        CartItem::factory()->create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 2,
        ]);

        $cart->load('items.product');

        $package = $this->calculator->calculateFromCart($cart);

        // Weight: 1000g x 2 = 2000g
        expect($package['weight'])->toBe(2000);

        // Dimensions
        expect($package['length'])->toBe(40);
        expect($package['width'])->toBe(30);
        expect($package['height'])->toBe(40); // 20 x 2
    });

    it('handles empty cart', function () {
        $cart = Cart::factory()->create();
        $cart->load('items');

        $package = $this->calculator->calculateFromCart($cart);

        // Should return minimum dimensions
        expect($package['weight'])->toBe(300);
        expect($package['length'])->toBe(11);
        expect($package['width'])->toBe(2);
        expect($package['height'])->toBe(2);
    });
});

describe('validateDimensions', function () {
    it('returns true for valid dimensions within limits', function () {
        $result = $this->calculator->validateDimensions(
            weight: 5000,    // 5kg
            length: 50,
            width: 40,
            height: 30,
        );

        expect($result['valid'])->toBeTrue();
        expect($result['errors'])->toBeEmpty();
    });

    it('returns error for weight exceeding limit', function () {
        $result = $this->calculator->validateDimensions(
            weight: 35000,   // 35kg > 30kg max
            length: 50,
            width: 40,
            height: 30,
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('weight');
    });

    it('returns error for dimensions exceeding limits', function () {
        $result = $this->calculator->validateDimensions(
            weight: 5000,
            length: 150,     // > 100cm max
            width: 40,
            height: 30,
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('length');
    });

    it('returns multiple errors when multiple limits exceeded', function () {
        $result = $this->calculator->validateDimensions(
            weight: 35000,   // > 30kg
            length: 150,     // > 100cm
            width: 120,      // > 100cm
            height: 110,      // > 100cm
        );

        expect($result['valid'])->toBeFalse();
        expect($result['errors'])->toContain('weight');
        expect($result['errors'])->toContain('length');
        expect($result['errors'])->toContain('width');
        expect($result['errors'])->toContain('height');
    });
});

describe('createShippingQuoteRequest', function () {
    it('creates ShippingQuoteRequest from cart', function () {
        $cart    = Cart::factory()->create();
        $product = Product::factory()->create([
            'weight' => 0.5,
            'length' => 30,
            'width'  => 20,
            'height' => 10,
            'price'  => 5000,
        ]);

        CartItem::factory()->create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => 5000,
        ]);

        $cart->load('items.product');
        $cart->recalculateTotals();

        $request = $this->calculator->createShippingQuoteRequest($cart, '01310100');

        expect($request->destinationZipcode)->toBe('01310100');
        expect($request->totalWeight)->toBe(1000);      // Billable weight in grams
        expect($request->totalLength)->toBe(30);
        expect($request->totalWidth)->toBe(20);
        expect($request->totalHeight)->toBe(10);
        expect($request->totalValue)->toBe(5000);
    });

    it('sanitizes zipcode in request', function () {
        $cart    = Cart::factory()->create();
        $product = Product::factory()->create([
            'weight' => 0.5,
            'length' => 30,
            'width'  => 20,
            'height' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id'    => $cart->id,
            'product_id' => $product->id,
            'quantity'   => 1,
            'unit_price' => 5000,
        ]);

        $cart->load('items.product');

        $request = $this->calculator->createShippingQuoteRequest($cart, '01310-100');

        expect($request->cleanZipcode())->toBe('01310100');
    });
});
