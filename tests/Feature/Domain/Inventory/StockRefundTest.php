<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\StockMovement;
use App\Domain\Inventory\Services\StockService;

beforeEach(function () {
    $this->stockService = app(StockService::class);
});

describe('StockService::refundStock', function () {
    it('adds stock back when refunding', function () {
        $product = Product::factory()->create(['stock_quantity' => 90, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 10,
            orderId: 123,
            reason: 'Order cancelled',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(100)
            ->and($movement)->toBeInstanceOf(StockMovement::class)
            ->and($movement->movement_type)->toBe(MovementType::Refund)
            ->and($movement->quantity)->toBe(10)
            ->and($movement->quantity_before)->toBe(90)
            ->and($movement->quantity_after)->toBe(100);
    });

    it('records the order ID and reason in notes', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 456,
            reason: 'Customer requested cancellation',
        );

        expect($movement->notes)->toContain('456')
            ->and($movement->notes)->toContain('Customer requested cancellation');
    });

    it('handles partial refund', function () {
        $product = Product::factory()->create(['stock_quantity' => 80, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 789,
            reason: 'Partial cancellation - 5 of 20 items',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(85)
            ->and($movement->quantity)->toBe(5);
    });

    it('works with product variants', function () {
        $product = Product::factory()->variable()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 35,
        ]);

        $movement = $this->stockService->refundStock(
            stockable: $variant,
            quantity: 15,
            orderId: 222,
            reason: 'Order cancelled',
        );

        $variant->refresh();

        expect($variant->stock_quantity)->toBe(50)
            ->and($movement->stockable_type)->toBe(ProductVariant::class)
            ->and($movement->stockable_id)->toBe($variant->id);
    });

    it('handles refund without order ID', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 10,
            reason: 'Inventory correction',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(110)
            ->and($movement->notes)->toContain('Inventory correction');
    });

    it('handles refund without reason', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 10,
            orderId: 333,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(110)
            ->and($movement->notes)->toContain('Estorno');
    });

    it('does not record movement when recordMovement is false', function () {
        $product = Product::factory()->create(['stock_quantity' => 90, 'manage_stock' => true]);

        $initialMovementCount = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->count();

        $result = $this->stockService->refundStock(
            stockable: $product,
            quantity: 10,
            orderId: 444,
            reason: 'Damaged goods - do not return to stock',
            recordMovement: false,
        );

        $product->refresh();

        // Stock should NOT be updated when recordMovement is false
        expect($result)->toBeNull()
            ->and($product->stock_quantity)->toBe(90);

        $currentMovementCount = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->count();

        expect($currentMovementCount)->toBe($initialMovementCount);
    });

    it('uses database transaction for atomicity', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        try {
            \DB::transaction(function () use ($product) {
                $this->stockService->refundStock(
                    stockable: $product,
                    quantity: 50,
                    orderId: 555,
                    reason: 'Test refund',
                );

                throw new \Exception('Simulated failure');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $product->refresh();
        expect($product->stock_quantity)->toBe(100);
    });
});

describe('Refund with damaged product flag', function () {
    it('does not add stock back when product is damaged', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $result = $this->stockService->refundStock(
            stockable: $product,
            quantity: 10,
            orderId: 666,
            reason: 'Product damaged during shipping',
            recordMovement: false,
        );

        $product->refresh();

        expect($result)->toBeNull()
            ->and($product->stock_quantity)->toBe(50);
    });

    it('allows manual decision to not refund stock', function () {
        $product = Product::factory()->create(['stock_quantity' => 80, 'manage_stock' => true]);

        // First, do a normal refund
        $movement1 = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 777,
            reason: 'Items returned in good condition',
        );

        $product->refresh();
        expect($product->stock_quantity)->toBe(85);

        // Then, skip refund for damaged item
        $result = $this->stockService->refundStock(
            stockable: $product,
            quantity: 3,
            orderId: 777,
            reason: 'Items damaged - not returned to stock',
            recordMovement: false,
        );

        $product->refresh();
        expect($product->stock_quantity)->toBe(85)
            ->and($result)->toBeNull();
    });
});

describe('Refund edge cases', function () {
    it('handles zero quantity refund', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 0,
            orderId: 888,
            reason: 'Zero refund test',
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(100)
            ->and($movement->quantity)->toBe(0);
    });

    it('handles negative quantity input (converts to positive)', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: -10,
            orderId: 999,
            reason: 'Negative input test',
        );

        $product->refresh();

        // Should convert negative to positive for refund
        expect($product->stock_quantity)->toBe(110)
            ->and($movement->quantity)->toBe(10);
    });

    it('handles multiple sequential refunds', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $this->stockService->refundStock($product, 10, orderId: 1, reason: 'Refund 1');
        $product->refresh();
        expect($product->stock_quantity)->toBe(60);

        $this->stockService->refundStock($product, 15, orderId: 2, reason: 'Refund 2');
        $product->refresh();
        expect($product->stock_quantity)->toBe(75);

        $this->stockService->refundStock($product, 5, orderId: 3, reason: 'Refund 3');
        $product->refresh();
        expect($product->stock_quantity)->toBe(80);
    });

    it('records all refund movements in history', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $this->stockService->refundStock($product, 10, orderId: 100, reason: 'First refund');
        $this->stockService->refundStock($product, 20, orderId: 101, reason: 'Second refund');

        $movements = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->where('movement_type', MovementType::Refund)
            ->orderBy('created_at')
            ->get();

        expect($movements)->toHaveCount(2)
            ->and($movements[0]->quantity)->toBe(10)
            ->and($movements[1]->quantity)->toBe(20);
    });
});

describe('Refund reason formatting', function () {
    it('formats notes with order ID and reason', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 1234,
            reason: 'Customer changed mind',
        );

        expect($movement->notes)->toContain('1234')
            ->and($movement->notes)->toContain('Customer changed mind');
    });

    it('handles special characters in reason', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 5678,
            reason: "Customer's request - item didn't fit & was returned",
        );

        expect($movement->notes)->toContain("Customer's request");
    });

    it('handles very long reason', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $longReason = str_repeat('This is a long reason. ', 50);

        $movement = $this->stockService->refundStock(
            stockable: $product,
            quantity: 5,
            orderId: 9999,
            reason: $longReason,
        );

        expect($movement->notes)->not->toBeEmpty();
    });
});
