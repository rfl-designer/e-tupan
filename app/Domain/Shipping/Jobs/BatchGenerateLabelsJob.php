<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Jobs;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BatchGenerateLabelsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 1;

    /**
     * Create a new job instance.
     *
     * @param  Collection<int, string>|array<string>  $shipmentIds
     */
    public function __construct(
        public Collection|array $shipmentIds,
        public bool $notifyCustomers = true,
    ) {
        $this->onQueue('shipping');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $ids = $this->shipmentIds instanceof Collection
            ? $this->shipmentIds->toArray()
            : $this->shipmentIds;

        Log::info('Starting batch label generation', [
            'shipment_count' => count($ids),
        ]);

        $shipments = Shipment::query()
            ->whereIn('id', $ids)
            ->whereIn('status', [
                ShipmentStatus::Pending,
                ShipmentStatus::CartAdded,
                ShipmentStatus::Purchased,
            ])
            ->get();

        foreach ($shipments as $shipment) {
            GenerateShipmentLabelJob::dispatch($shipment, $this->notifyCustomers);
        }

        Log::info('Batch label generation jobs dispatched', [
            'dispatched_count' => $shipments->count(),
            'skipped_count'    => count($ids) - $shipments->count(),
        ]);
    }
}
