<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Jobs;

use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Services\TrackingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class ProcessTrackingWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [30, 60, 120];

    /**
     * Create a new job instance.
     *
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public array $payload,
    ) {
        $this->onQueue('shipping');
    }

    /**
     * Execute the job.
     */
    public function handle(TrackingService $trackingService): void
    {
        $shipmentId = $this->payload['shipment_id'] ?? null;

        if (!$shipmentId) {
            Log::warning('Tracking webhook missing shipment_id', [
                'payload' => $this->payload,
            ]);

            return;
        }

        $shipment = Shipment::query()
            ->where('shipment_id', $shipmentId)
            ->first();

        if (!$shipment) {
            Log::warning('Tracking webhook shipment not found', [
                'me_shipment_id' => $shipmentId,
            ]);

            return;
        }

        Log::info('Processing tracking webhook', [
            'shipment_id'    => $shipment->id,
            'me_shipment_id' => $shipmentId,
            'status'         => $this->payload['status'] ?? 'unknown',
        ]);

        // Add the tracking event from webhook
        $event = $this->payload['tracking'] ?? $this->payload;

        $trackingService->addManualEvent($shipment, [
            'event_code'  => $event['code'] ?? null,
            'description' => $event['description'] ?? $event['status'] ?? 'Status update',
            'status'      => $event['status'] ?? $this->payload['status'] ?? 'unknown',
            'city'        => $event['city'] ?? null,
            'state'       => $event['state'] ?? null,
            'country'     => $event['country'] ?? 'BR',
            'notes'       => $event['notes'] ?? null,
            'event_at'    => $event['date'] ?? now(),
        ]);

        // Sync full tracking to get complete history
        $trackingService->syncTracking($shipment);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Tracking webhook job failed', [
            'payload' => $this->payload,
            'error'   => $exception->getMessage(),
        ]);
    }
}
