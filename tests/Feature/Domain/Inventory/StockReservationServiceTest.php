<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\{Product, ProductVariant};
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Exceptions\InsufficientStockException;
use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use App\Domain\Inventory\Services\StockReservationService;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(StockReservationService::class);
});

describe('StockReservationService::reserve', function () {
    it('creates a reservation for a product', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $cartId  = Str::uuid()->toString();

        $reservation = $this->service->reserve($product, 5, $cartId);

        expect($reservation)->toBeInstanceOf(StockReservation::class)
            ->and($reservation->stockable_type)->toBe(Product::class)
            ->and($reservation->stockable_id)->toBe($product->id)
            ->and($reservation->quantity)->toBe(5)
            ->and($reservation->cart_id)->toBe($cartId)
            ->and($reservation->expires_at)->not->toBeNull()
            ->and($reservation->converted_at)->toBeNull();
    });

    it('creates a reservation for a product variant', function () {
        $product = Product::factory()->variable()->create();
        $variant = ProductVariant::factory()->create([
            'product_id'     => $product->id,
            'stock_quantity' => 50,
        ]);
        $cartId = Str::uuid()->toString();

        $reservation = $this->service->reserve($variant, 3, $cartId);

        expect($reservation)->toBeInstanceOf(StockReservation::class)
            ->and($reservation->stockable_type)->toBe(ProductVariant::class)
            ->and($reservation->stockable_id)->toBe($variant->id)
            ->and($reservation->quantity)->toBe(3);
    });

    it('uses configured TTL for reservation expiration', function () {
        config(['inventory.reservation_ttl' => 60]);

        $product = Product::factory()->create(['stock_quantity' => 100]);
        $cartId  = Str::uuid()->toString();

        $reservation = $this->service->reserve($product, 5, $cartId);

        // Verify expiration is approximately 60 minutes from now
        $minutesUntilExpiry = now()->diffInMinutes($reservation->expires_at, false);
        expect($minutesUntilExpiry)->toBeBetween(59, 61);
    });

    it('throws exception when insufficient stock available', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);
        $cartId  = Str::uuid()->toString();

        // Create existing reservation
        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 3,
            'expires_at'     => now()->addMinutes(30),
        ]);

        // Try to reserve more than available (5 - 3 = 2 available)
        $this->service->reserve($product, 5, $cartId);
    })->throws(InsufficientStockException::class);

    it('records a reservation movement in stock history', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $cartId  = Str::uuid()->toString();

        $reservation = $this->service->reserve($product, 5, $cartId);

        $movement = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->where('movement_type', MovementType::Reservation)
            ->first();

        expect($movement)->not->toBeNull()
            ->and($movement->quantity)->toBe(-5)
            ->and($movement->reference_type)->toBe(StockReservation::class)
            ->and($movement->reference_id)->toBe($reservation->id);
    });

    it('allows reservation without cart_id', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        $reservation = $this->service->reserve($product, 5);

        expect($reservation)->toBeInstanceOf(StockReservation::class)
            ->and($reservation->cart_id)->toBeNull();
    });

    it('considers only active reservations when checking availability', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        // Create expired reservation (should be ignored)
        StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 8,
        ]);

        // Create converted reservation (should be ignored)
        StockReservation::factory()->converted()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        // Should be able to reserve full stock since others are inactive
        $reservation = $this->service->reserve($product, 10, Str::uuid()->toString());

        expect($reservation)->toBeInstanceOf(StockReservation::class)
            ->and($reservation->quantity)->toBe(10);
    });
});

describe('StockReservationService::release', function () {
    it('releases a reservation', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->release($reservation);

        expect($reservation->fresh())->toBeNull();
    });

    it('records a release movement in stock history', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->release($reservation);

        $movement = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->where('movement_type', MovementType::ReservationRelease)
            ->first();

        expect($movement)->not->toBeNull()
            ->and($movement->quantity)->toBe(5);
    });

    it('does not release already converted reservation', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->converted()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->release($reservation);

        // Reservation should still exist since it was converted
        expect($reservation->fresh())->not->toBeNull();
    });
});

describe('StockReservationService::releaseByCart', function () {
    it('releases all reservations for a cart', function () {
        $cartId   = Str::uuid()->toString();
        $product1 = Product::factory()->create(['stock_quantity' => 100]);
        $product2 = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->active()->forCart($cartId)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
            'quantity'       => 5,
        ]);

        StockReservation::factory()->active()->forCart($cartId)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product2->id,
            'quantity'       => 3,
        ]);

        $this->service->releaseByCart($cartId);

        expect(StockReservation::query()->forCart($cartId)->count())->toBe(0);
    });

    it('only releases active reservations for the cart', function () {
        $cartId  = Str::uuid()->toString();
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Active reservation - should be released
        StockReservation::factory()->active()->forCart($cartId)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        // Converted reservation - should NOT be released
        StockReservation::factory()->converted()->forCart($cartId)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 3,
        ]);

        $this->service->releaseByCart($cartId);

        $remaining = StockReservation::query()->forCart($cartId)->get();
        expect($remaining)->toHaveCount(1)
            ->and($remaining->first()->isConverted())->toBeTrue();
    });

    it('does not affect reservations from other carts', function () {
        $cartId1 = Str::uuid()->toString();
        $cartId2 = Str::uuid()->toString();
        $product = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->active()->forCart($cartId1)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        StockReservation::factory()->active()->forCart($cartId2)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 3,
        ]);

        $this->service->releaseByCart($cartId1);

        expect(StockReservation::query()->forCart($cartId1)->count())->toBe(0)
            ->and(StockReservation::query()->forCart($cartId2)->count())->toBe(1);
    });
});

describe('StockReservationService::convertToSale', function () {
    it('converts a reservation to a sale', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->convertToSale($reservation, orderId: 123);

        $reservation->refresh();
        expect($reservation->converted_at)->not->toBeNull()
            ->and($reservation->isConverted())->toBeTrue();
    });

    it('records a sale movement when converting', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->convertToSale($reservation, orderId: 456);

        $movement = StockMovement::query()
            ->where('stockable_type', Product::class)
            ->where('stockable_id', $product->id)
            ->where('movement_type', MovementType::Sale)
            ->first();

        expect($movement)->not->toBeNull()
            ->and($movement->quantity)->toBe(-5)
            ->and($movement->notes)->toContain('456');
    });

    it('deducts stock when converting reservation to sale', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
        ]);

        $this->service->convertToSale($reservation, orderId: 789);

        $product->refresh();
        expect($product->stock_quantity)->toBe(90);
    });

    it('does not convert already converted reservation', function () {
        $product             = Product::factory()->create(['stock_quantity' => 100]);
        $originalConvertedAt = now()->subHour();

        $reservation = StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'converted_at'   => $originalConvertedAt,
        ]);

        $this->service->convertToSale($reservation, orderId: 999);

        $reservation->refresh();
        expect($reservation->converted_at->timestamp)->toBe($originalConvertedAt->timestamp);
    });

    it('does not convert expired reservation', function () {
        $product     = Product::factory()->create(['stock_quantity' => 100]);
        $reservation = StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $this->service->convertToSale($reservation, orderId: 111);

        $reservation->refresh();
        expect($reservation->converted_at)->toBeNull();
    });
});

describe('StockReservationService::getAvailableQuantity', function () {
    it('returns stock minus active reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Active reservation
        StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 30,
        ]);

        $available = $this->service->getAvailableQuantity($product);

        expect($available)->toBe(70);
    });

    it('ignores expired reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 50,
        ]);

        $available = $this->service->getAvailableQuantity($product);

        expect($available)->toBe(100);
    });

    it('ignores converted reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->converted()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 50,
        ]);

        $available = $this->service->getAvailableQuantity($product);

        expect($available)->toBe(100);
    });

    it('returns zero when all stock is reserved', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);

        StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
        ]);

        $available = $this->service->getAvailableQuantity($product);

        expect($available)->toBe(0);
    });

    it('never returns negative values', function () {
        $product = Product::factory()->create(['stock_quantity' => 5]);

        // More reserved than actual stock (edge case)
        StockReservation::factory()->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 10,
        ]);

        $available = $this->service->getAvailableQuantity($product);

        expect($available)->toBe(0);
    });
});

describe('StockReservation edge cases', function () {
    it('handles concurrent reservations correctly', function () {
        $product = Product::factory()->create(['stock_quantity' => 10]);
        $cartId1 = Str::uuid()->toString();
        $cartId2 = Str::uuid()->toString();

        // First reservation
        $reservation1 = $this->service->reserve($product, 5, $cartId1);

        // Second reservation should succeed with remaining stock
        $reservation2 = $this->service->reserve($product, 5, $cartId2);

        expect($reservation1)->toBeInstanceOf(StockReservation::class)
            ->and($reservation2)->toBeInstanceOf(StockReservation::class)
            ->and($this->service->getAvailableQuantity($product))->toBe(0);
    });

    it('can extend reservation', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);
        $cartId  = Str::uuid()->toString();

        $reservation = StockReservation::factory()->active()->forCart($cartId)->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->addMinutes(5),
        ]);

        $newExpiry = now()->addMinutes(60);
        $this->service->extendReservation($reservation, $newExpiry);

        $reservation->refresh();
        expect($reservation->expires_at->timestamp)->toBe($newExpiry->timestamp);
    });
});
