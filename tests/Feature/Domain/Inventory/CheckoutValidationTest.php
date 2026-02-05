<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\DTOs\{StockValidationItem, StockValidationResult};
use App\Domain\Inventory\Models\StockReservation;
use App\Domain\Inventory\Services\StockService;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->stockService = app(StockService::class);
});

describe('StockService::validateForCheckout', function () {
    it('validates all items are available', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);
        $product2 = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $items = [
            ['stockable' => $product1, 'quantity' => 10],
            ['stockable' => $product2, 'quantity' => 5],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result)->toBeInstanceOf(StockValidationResult::class)
            ->and($result->isValid())->toBeTrue()
            ->and($result->getItems())->toHaveCount(2)
            ->and($result->getUnavailableItems())->toBeEmpty();
    });

    it('returns invalid when item has insufficient stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 5, 'manage_stock' => true]);

        $items = [
            ['stockable' => $product, 'quantity' => 10],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeFalse()
            ->and($result->getUnavailableItems())->toHaveCount(1)
            ->and($result->getUnavailableCount())->toBe(1);
    });

    it('considers active reservations when validating', function () {
        $product = Product::factory()->create(['stock_quantity' => 20, 'manage_stock' => true]);

        // Create active reservation for 15 units
        StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 15,
        ]);

        // Try to checkout 10 units (only 5 available: 20 - 15)
        $items = [
            ['stockable' => $product, 'quantity' => 10],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeFalse()
            ->and($result->getUnavailableItems())->toHaveCount(1);

        $unavailableItem = $result->getUnavailableItems()[0];
        expect($unavailableItem->availableQuantity)->toBe(5)
            ->and($unavailableItem->requestedQuantity)->toBe(10);
    });

    it('ignores expired reservations when validating', function () {
        $product = Product::factory()->create(['stock_quantity' => 20, 'manage_stock' => true]);

        // Create expired reservation (should be ignored)
        StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 15,
        ]);

        $items = [
            ['stockable' => $product, 'quantity' => 20],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeTrue();
    });

    it('validates mixed products and variants', function () {
        $simpleProduct   = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);
        $variableProduct = Product::factory()->variable()->create(['manage_stock' => true]);
        $variant         = ProductVariant::factory()->create([
            'product_id'     => $variableProduct->id,
            'stock_quantity' => 30,
        ]);

        $items = [
            ['stockable' => $simpleProduct, 'quantity' => 10],
            ['stockable' => $variant, 'quantity' => 5],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeTrue()
            ->and($result->getItems())->toHaveCount(2);
    });

    it('provides clear message when item becomes unavailable', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'manage_stock'   => true,
            'name'           => 'Test Product',
        ]);

        $items = [
            ['stockable' => $product, 'quantity' => 5],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeFalse();

        $unavailableItem = $result->getUnavailableItems()[0];
        expect($unavailableItem->message)->not->toBeEmpty()
            ->and($unavailableItem->isAvailable)->toBeFalse();
    });

    it('suggests available quantity for partial fulfillment', function () {
        $product = Product::factory()->create(['stock_quantity' => 7, 'manage_stock' => true]);

        $items = [
            ['stockable' => $product, 'quantity' => 10],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeFalse()
            ->and($result->hasPartiallyFulfillableItems())->toBeTrue();

        $item = $result->getUnavailableItems()[0];
        expect($item->canPartiallyFulfill())->toBeTrue()
            ->and($item->availableQuantity)->toBe(7)
            ->and($item->getFulfillableQuantity())->toBe(7)
            ->and($item->getShortage())->toBe(3);
    });

    it('handles multiple unavailable items', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 2, 'manage_stock' => true]);
        $product2 = Product::factory()->create(['stock_quantity' => 0, 'manage_stock' => true]);

        $items = [
            ['stockable' => $product1, 'quantity' => 10],
            ['stockable' => $product2, 'quantity' => 5],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeFalse()
            ->and($result->getUnavailableCount())->toBe(2)
            ->and($result->getErrorMessages())->toHaveCount(2);
    });

    it('returns empty result for empty items array', function () {
        $result = $this->stockService->validateForCheckout([]);

        expect($result->isValid())->toBeTrue()
            ->and($result->getItems())->toBeEmpty();
    });

    it('allows backorders when product permits', function () {
        $product = Product::factory()->create([
            'stock_quantity'   => 0,
            'manage_stock'     => true,
            'allow_backorders' => true,
        ]);

        $items = [
            ['stockable' => $product, 'quantity' => 10],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeTrue();
    });

    it('skips validation for products not managing stock', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'manage_stock'   => false,
        ]);

        $items = [
            ['stockable' => $product, 'quantity' => 100],
        ];

        $result = $this->stockService->validateForCheckout($items);

        expect($result->isValid())->toBeTrue();
    });
});

describe('StockService::checkAvailability', function () {
    it('returns true when stock is sufficient', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $isAvailable = $this->stockService->checkAvailability($product, 50);

        expect($isAvailable)->toBeTrue();
    });

    it('returns false when stock is insufficient', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'manage_stock' => true]);

        $isAvailable = $this->stockService->checkAvailability($product, 20);

        expect($isAvailable)->toBeFalse();
    });

    it('considers active reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 40,
        ]);

        // 50 - 40 = 10 available
        expect($this->stockService->checkAvailability($product, 10))->toBeTrue()
            ->and($this->stockService->checkAvailability($product, 15))->toBeFalse();
    });

    it('returns true when backorders allowed', function () {
        $product = Product::factory()->create([
            'stock_quantity'   => 0,
            'manage_stock'     => true,
            'allow_backorders' => true,
        ]);

        $isAvailable = $this->stockService->checkAvailability($product, 100);

        expect($isAvailable)->toBeTrue();
    });

    it('returns true when not managing stock', function () {
        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'manage_stock'   => false,
        ]);

        $isAvailable = $this->stockService->checkAvailability($product, 100);

        expect($isAvailable)->toBeTrue();
    });

    it('returns true for zero quantity request', function () {
        $product = Product::factory()->create(['stock_quantity' => 10, 'manage_stock' => true]);

        $isAvailable = $this->stockService->checkAvailability($product, 0);

        expect($isAvailable)->toBeTrue();
    });

    it('works with product variants', function () {
        $product = Product::factory()->variable()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 25,
        ]);

        expect($this->stockService->checkAvailability($variant, 25))->toBeTrue()
            ->and($this->stockService->checkAvailability($variant, 30))->toBeFalse();
    });
});

describe('StockValidationItem DTO', function () {
    it('calculates shortage correctly', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $item = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 5,
            isAvailable: false,
            message: 'Insufficient stock',
        );

        expect($item->getShortage())->toBe(5);
    });

    it('returns zero shortage when available', function () {
        $product = Product::factory()->create(['stock_quantity' => 20]);

        $item = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 20,
            isAvailable: true,
        );

        expect($item->getShortage())->toBe(0);
    });

    it('calculates fulfillable quantity', function () {
        $product = Product::factory()->create(['stock_quantity' => 3]);

        $item = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 3,
            isAvailable: false,
        );

        expect($item->getFulfillableQuantity())->toBe(3);
    });

    it('detects partial fulfillment possibility', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $partialItem = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 5,
            isAvailable: false,
        );

        $noStockItem = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 0,
            isAvailable: false,
        );

        expect($partialItem->canPartiallyFulfill())->toBeTrue()
            ->and($noStockItem->canPartiallyFulfill())->toBeFalse();
    });

    it('converts to array correctly', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        $item = new StockValidationItem(
            stockable: $product,
            requestedQuantity: 10,
            availableQuantity: 5,
            isAvailable: false,
            message: 'Not enough stock',
        );

        $array = $item->toArray();

        expect($array)->toHaveKeys([
            'stockable_type',
            'stockable_id',
            'requested_quantity',
            'available_quantity',
            'is_available',
            'message',
            'shortage',
            'can_partially_fulfill',
        ])
            ->and($array['stockable_id'])->toBe($product->id)
            ->and($array['requested_quantity'])->toBe(10)
            ->and($array['available_quantity'])->toBe(5)
            ->and($array['is_available'])->toBeFalse()
            ->and($array['shortage'])->toBe(5)
            ->and($array['can_partially_fulfill'])->toBeTrue();
    });
});

describe('StockValidationResult DTO', function () {
    it('creates success result', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $items   = [
            new StockValidationItem(
                stockable: $product,
                requestedQuantity: 10,
                availableQuantity: 100,
                isAvailable: true,
            ),
        ];

        $result = StockValidationResult::success($items);

        expect($result->isValid())->toBeTrue()
            ->and($result->getItems())->toHaveCount(1);
    });

    it('creates failure result', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $items   = [
            new StockValidationItem(
                stockable: $product,
                requestedQuantity: 10,
                availableQuantity: 5,
                isAvailable: false,
                message: 'Insufficient stock',
            ),
        ];

        $result = StockValidationResult::failure($items);

        expect($result->isValid())->toBeFalse()
            ->and($result->getUnavailableItems())->toHaveCount(1);
    });

    it('filters available and unavailable items', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 100]);
        $product2 = Product::factory()->create(['stock_quantity' => 5]);

        $items = [
            new StockValidationItem(
                stockable: $product1,
                requestedQuantity: 10,
                availableQuantity: 100,
                isAvailable: true,
            ),
            new StockValidationItem(
                stockable: $product2,
                requestedQuantity: 20,
                availableQuantity: 5,
                isAvailable: false,
                message: 'Insufficient',
            ),
        ];

        $result = new StockValidationResult(valid: false, items: $items);

        expect($result->getAvailableItems())->toHaveCount(1)
            ->and($result->getUnavailableItems())->toHaveCount(1);
    });

    it('collects error messages', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 0]);
        $product2 = Product::factory()->create(['stock_quantity' => 2]);

        $items = [
            new StockValidationItem(
                stockable: $product1,
                requestedQuantity: 10,
                availableQuantity: 0,
                isAvailable: false,
                message: 'Product 1 out of stock',
            ),
            new StockValidationItem(
                stockable: $product2,
                requestedQuantity: 10,
                availableQuantity: 2,
                isAvailable: false,
                message: 'Product 2 low stock',
            ),
        ];

        $result = new StockValidationResult(valid: false, items: $items);

        expect($result->getErrorMessages())->toContain('Product 1 out of stock')
            ->and($result->getErrorMessages())->toContain('Product 2 low stock');
    });

    it('converts to array correctly', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $items   = [
            new StockValidationItem(
                stockable: $product,
                requestedQuantity: 10,
                availableQuantity: 5,
                isAvailable: false,
                message: 'Low stock',
            ),
        ];

        $result = StockValidationResult::failure($items);
        $array  = $result->toArray();

        expect($array)->toHaveKeys(['valid', 'items', 'unavailable_count', 'error_messages'])
            ->and($array['valid'])->toBeFalse()
            ->and($array['items'])->toHaveCount(1)
            ->and($array['unavailable_count'])->toBe(1)
            ->and($array['error_messages'])->toContain('Low stock');
    });
});

describe('Checkout validation logging', function () {
    it('logs failed checkout attempts', function () {
        Log::spy();

        $product = Product::factory()->create([
            'stock_quantity' => 0,
            'manage_stock'   => true,
            'name'           => 'Unavailable Product',
        ]);

        $items = [
            ['stockable' => $product, 'quantity' => 5],
        ];

        $this->stockService->validateForCheckout($items);

        Log::shouldHaveReceived('info')
            ->withArgs(fn ($message) => str_contains($message, 'Checkout validation failed'));
    });

    it('does not log successful validation', function () {
        Log::spy();

        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $items = [
            ['stockable' => $product, 'quantity' => 5],
        ];

        $this->stockService->validateForCheckout($items);

        Log::shouldNotHaveReceived('info');
    });
});
