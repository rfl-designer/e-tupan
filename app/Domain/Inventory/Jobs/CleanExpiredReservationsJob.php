<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Jobs;

use App\Domain\Inventory\Enums\MovementType;
use App\Domain\Inventory\Models\StockReservation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{DB, Log};

class CleanExpiredReservationsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of reservations to process per batch.
     */
    protected int $batchSize = 100;

    /**
     * Execute the job.
     *
     * @return int The number of reservations cleaned
     */
    public function handle(): int
    {
        $totalCleaned = 0;

        do {
            $reservations = StockReservation::query()
                ->expired()
                ->with('stockable')
                ->limit($this->batchSize)
                ->get();

            foreach ($reservations as $reservation) {
                $this->releaseReservation($reservation);
                $totalCleaned++;
            }
        } while ($reservations->count() === $this->batchSize);

        if ($totalCleaned > 0) {
            Log::info("Cleaned {$totalCleaned} expired stock reservations.");
        }

        return $totalCleaned;
    }

    /**
     * Release a single reservation and record the movement.
     */
    protected function releaseReservation(StockReservation $reservation): void
    {
        DB::transaction(function () use ($reservation) {
            $stockable = $reservation->stockable;

            if ($stockable === null) {
                // Stockable was deleted, just remove the reservation
                $reservation->delete();

                return;
            }

            // Record the release movement
            $stockable->stockMovements()->create([
                'movement_type'   => MovementType::ReservationRelease,
                'quantity'        => $reservation->quantity,
                'quantity_before' => $stockable->getStockQuantity(),
                'quantity_after'  => $stockable->getStockQuantity(),
                'reference_type'  => StockReservation::class,
                'reference_id'    => $reservation->id,
                'notes'           => 'Liberacao automatica - reserva expirada',
            ]);

            $reservation->delete();
        });
    }
}
