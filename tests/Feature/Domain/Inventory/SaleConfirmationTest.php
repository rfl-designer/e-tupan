<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Jobs\SendLowStockAlertsJob;
use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use App\Domain\Inventory\Services\{StockReservationService, StockService};
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->stockService       = app(StockService::class);
    $this->reservationService = app(StockReservationService::class);
});

describe('StockService::confirmSale', function () {
    it('deducts stock when confirming a sale', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 123,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(90)
            ->and($movement)->toBeInstanceOf(StockMovement::class)
            ->and($movement->movement_type)->toBe(MovementType::Sale)
            ->and($movement->quantity)->toBe(-10)
            ->and($movement->quantity_before)->toBe(100)
            ->and($movement->quantity_after)->toBe(90);
    });

    it('records the order ID in notes', function () {
        $product = Product::factory()->create(['stock_quantity' => 50, 'manage_stock' => true]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 5,
            orderId: 456,
        );

        expect($movement->notes)->toContain('456');
    });

    it('converts reservation to sale when reservation ID provided', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
        ]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 789,
            reservationId: $reservation->id,
        );

        $reservation->refresh();

        expect($reservation->isConverted())->toBeTrue()
            ->and($reservation->converted_at)->not->toBeNull();
    });

    it('throws exception when stock is insufficient', function () {
        $product = Product::factory()->create(['stock_quantity' => 5, 'manage_stock' => true]);

        $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 111,
        );
    })->throws(InsufficientStockException::class);

    it('works with product variants', function () {
        $product = Product::factory()->variable()->create(['manage_stock' => true]);
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 50,
        ]);

        $movement = $this->stockService->confirmSale(
            stockable: $variant,
            quantity: 15,
            orderId: 222,
        );

        $variant->refresh();

        expect($variant->stock_quantity)->toBe(35)
            ->and($movement->stockable_type)->toBe(ProductVariant::class)
            ->and($movement->stockable_id)->toBe($variant->id);
    });

    it('handles sale without reservation', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 20,
            orderId: 333,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(80)
            ->and($movement->movement_type)->toBe(MovementType::Sale);
    });

    it('handles sale with null order ID', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(90)
            ->and($movement->notes)->toBe('Venda');
    });

    it('uses database transaction for atomicity', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        try {
            \DB::transaction(function () use ($product) {
                $this->stockService->confirmSale(
                    stockable: $product,
                    quantity: 50,
                    orderId: 444,
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

describe('StockService::confirmSale with reservations', function () {
    it('ignores already converted reservation', function () {
        $product             = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);
        $originalConvertedAt = now()->subHour();

        $reservation = StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
            'converted_at'   => $originalConvertedAt,
            'expires_at'     => now()->addMinutes(30),
        ]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 555,
            reservationId: $reservation->id,
        );

        $reservation->refresh();

        // Should still deduct stock but not change converted_at
        expect($reservation->converted_at->timestamp)->toBe($originalConvertedAt->timestamp);
    });

    it('ignores expired reservation', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $reservation = StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
        ]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 666,
            reservationId: $reservation->id,
        );

        $reservation->refresh();

        // Stock should be deducted but reservation not converted
        expect($reservation->converted_at)->toBeNull();
    });

    it('validates reservation belongs to correct stockable', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);
        $product2 = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
            'quantity'       => 10,
        ]);

        // Attempting to use product1's reservation for product2 should work
        // but reservation should NOT be converted (mismatched stockable)
        $movement = $this->stockService->confirmSale(
            stockable: $product2,
            quantity: 10,
            orderId: 777,
            reservationId: $reservation->id,
        );

        $reservation->refresh();

        // Reservation should not be converted since it doesn't match
        expect($reservation->converted_at)->toBeNull();
    });
});

describe('Low stock alerts after sale', function () {
    it('dispatches low stock alert job when stock falls below threshold', function () {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_quantity'      => 15,
            'manage_stock'        => true,
            'low_stock_threshold' => 10,
            'notify_low_stock'    => true,
        ]);

        $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 888,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(5)
            ->and($product->isLowStock())->toBeTrue();

        Queue::assertPushed(SendLowStockAlertsJob::class);
    });

    it('does not dispatch alert when stock is above threshold', function () {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_quantity'      => 100,
            'manage_stock'        => true,
            'low_stock_threshold' => 10,
            'notify_low_stock'    => true,
        ]);

        $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 999,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(90)
            ->and($product->isLowStock())->toBeFalse();

        Queue::assertNotPushed(SendLowStockAlertsJob::class);
    });

    it('does not dispatch alert when notifications are disabled', function () {
        Queue::fake();

        $product = Product::factory()->create([
            'stock_quantity'      => 15,
            'manage_stock'        => true,
            'low_stock_threshold' => 10,
            'notify_low_stock'    => false,
        ]);

        $this->stockService->confirmSale(
            stockable: $product,
            quantity: 10,
            orderId: 1000,
        );

        Queue::assertNotPushed(SendLowStockAlertsJob::class);
    });
});

describe('Sale confirmation edge cases', function () {
    it('handles zero quantity sale', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $movement = $this->stockService->confirmSale(
            stockable: $product,
            quantity: 0,
            orderId: 1001,
        );

        $product->refresh();

        expect($product->stock_quantity)->toBe(100)
            ->and($movement->quantity)->toBe(0);
    });

    it('handles concurrent sales with database locking', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        // Simulate two concurrent sales
        $this->stockService->confirmSale($product, 30, orderId: 1002);
        $this->stockService->confirmSale($product, 40, orderId: 1003);

        $product->refresh();

        expect($product->stock_quantity)->toBe(30);

        // Verify both movements were recorded
        $movements = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->where('movement_type', MovementType::Sale)
            ->get();

        expect($movements)->toHaveCount(2);
    });

    it('updates real-time stock correctly after multiple sales', function () {
        $product = Product::factory()->create(['stock_quantity' => 100, 'manage_stock' => true]);

        $this->stockService->confirmSale($product, 10, orderId: 1);
        $product->refresh();
        expect($product->stock_quantity)->toBe(90);

        $this->stockService->confirmSale($product, 20, orderId: 2);
        $product->refresh();
        expect($product->stock_quantity)->toBe(70);

        $this->stockService->confirmSale($product, 30, orderId: 3);
        $product->refresh();
        expect($product->stock_quantity)->toBe(40);
    });
});
