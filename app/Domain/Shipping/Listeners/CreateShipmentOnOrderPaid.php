<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Listeners;

use App\Domain\Checkout\Events\OrderPaid;
use App\Domain\Shipping\Services\ShipmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class CreateShipmentOnOrderPaid implements ShouldQueue
{
    /**
     * The queue connection that should handle the job.
     */
    public string $queue = 'shipping';

    public function __construct(
        private ShipmentService $shipmentService,
    ) {
    }

    /**
     * Handle the event.
     */
    public function handle(OrderPaid $event): void
    {
        $order = $event->order;

        // Check if shipment already exists
        $existingShipment = $this->shipmentService->getByOrder($order);

        if ($existingShipment !== null) {
            Log::info('Shipment already exists for order', [
                'order_id'    => $order->id,
                'shipment_id' => $existingShipment->id,
            ]);

            return;
        }

        // Create the shipment
        $shipment = $this->shipmentService->createFromOrder($order);

        Log::info('Shipment created on order paid', [
            'order_id'    => $order->id,
            'shipment_id' => $shipment->id,
            'carrier'     => $shipment->carrier_code,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(OrderPaid $event, \Throwable $exception): void
    {
        Log::error('Failed to create shipment on order paid', [
            'order_id' => $event->order->id,
            'error'    => $exception->getMessage(),
        ]);
    }
}
