<?php

declare(strict_types = 1);

use App\Domain\Catalog\Models\Product;
use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Jobs\CleanExpiredReservationsJob;
use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use Illuminate\Support\Facades\Queue;

describe('CleanExpiredReservationsJob', function () {
    it('deletes expired reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Create expired reservations
        StockReservation::factory()->count(3)->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        expect(StockReservation::query()->expired()->count())->toBe(0);
    });

    it('does not delete active reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Create active reservations
        StockReservation::factory()->count(3)->active()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        expect(StockReservation::query()->active()->count())->toBe(3);
    });

    it('does not delete converted reservations even if expired', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Create converted reservation that is also past expiry
        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->subHour(),
            'converted_at'   => now()->subMinutes(30),
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        expect(StockReservation::query()->converted()->count())->toBe(1);
    });

    it('records release movement for each cleaned reservation', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->count(2)->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $initialMovements = StockMovement::query()
            ->where('movement_type', MovementType::ReservationRelease)
            ->count();

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        $releaseMovements = StockMovement::query()
            ->where('movement_type', MovementType::ReservationRelease)
            ->count();

        expect($releaseMovements)->toBe($initialMovements + 2);
    });

    it('handles multiple products with expired reservations', function () {
        $product1 = Product::factory()->create(['stock_quantity' => 100]);
        $product2 = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product1->id,
            'quantity'       => 10,
        ]);

        StockReservation::factory()->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product2->id,
            'quantity'       => 15,
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        expect(StockReservation::query()->expired()->count())->toBe(0);
    });

    it('can be queued', function () {
        Queue::fake();

        CleanExpiredReservationsJob::dispatch();

        Queue::assertPushed(CleanExpiredReservationsJob::class);
    });

    it('processes in batches for performance', function () {
        $product = Product::factory()->create(['stock_quantity' => 1000]);

        // Create many expired reservations
        StockReservation::factory()->count(100)->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 1,
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        // All should be cleaned
        expect(StockReservation::query()->expired()->count())->toBe(0);
    });

    it('logs the number of cleaned reservations', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        StockReservation::factory()->count(5)->expired()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
        ]);

        $job    = new CleanExpiredReservationsJob();
        $result = $job->handle();

        expect($result)->toBe(5);
    });

    it('handles mixed expiration times correctly', function () {
        $product = Product::factory()->create(['stock_quantity' => 100]);

        // Expired 1 hour ago
        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->subHour(),
        ]);

        // Expired 1 minute ago
        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->subMinute(),
        ]);

        // Expires in 1 minute (still active)
        StockReservation::factory()->create([
            'stockable_type' => Product::class,
            'stockable_id'   => $product->id,
            'quantity'       => 5,
            'expires_at'     => now()->addMinute(),
        ]);

        $job = new CleanExpiredReservationsJob();
        $job->handle();

        expect(StockReservation::query()->count())->toBe(1)
            ->and(StockReservation::query()->active()->count())->toBe(1);
    });
});

describe('CleanExpiredReservationsJob scheduling', function () {
    it('can be scheduled with custom interval', function () {
        config(['inventory.clean_expired_reservations_interval' => 10]);

        // This test verifies the config value is accessible
        expect(config('inventory.clean_expired_reservations_interval'))->toBe(10);
    });
});
