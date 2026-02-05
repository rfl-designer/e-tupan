<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Jobs;

use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\DTOs\ShipmentRequest;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Notifications\ShipmentShippedNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\{Log, Notification};

class GenerateShipmentLabelJob implements ShouldQueue
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
    public array $backoff = [60, 300, 900];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Shipment $shipment,
        public bool $notifyCustomer = true,
    ) {
        $this->onQueue('shipping');
    }

    /**
     * Execute the job.
     */
    public function handle(LabelGeneratorInterface $labelGenerator): void
    {
        $this->shipment->loadMissing('order');

        Log::info('Starting label generation', [
            'shipment_id'    => $this->shipment->id,
            'current_status' => $this->shipment->status->value,
        ]);

        // Step 1: Add to cart if not already
        if ($this->shipment->status === ShipmentStatus::Pending) {
            $this->addToCart($labelGenerator);
        }

        // Step 2: Checkout if in cart
        if ($this->shipment->status === ShipmentStatus::CartAdded) {
            $this->checkout($labelGenerator);
        }

        // Step 3: Generate label if purchased
        if ($this->shipment->status === ShipmentStatus::Purchased) {
            $this->generateLabel($labelGenerator);
        }

        // Step 4: Notify customer if label generated
        if ($this->notifyCustomer && $this->shipment->status === ShipmentStatus::Generated) {
            $this->notifyCustomer();
        }

        Log::info('Label generation completed', [
            'shipment_id'     => $this->shipment->id,
            'final_status'    => $this->shipment->status->value,
            'tracking_number' => $this->shipment->tracking_number,
        ]);
    }

    /**
     * Add shipment to ME cart.
     */
    private function addToCart(LabelGeneratorInterface $labelGenerator): void
    {
        $request = ShipmentRequest::fromShipment($this->shipment);
        $result  = $labelGenerator->addToCart($request);

        if (!$result->success) {
            Log::error('Failed to add shipment to cart', [
                'shipment_id' => $this->shipment->id,
                'error'       => $result->errorMessage,
            ]);

            throw new \RuntimeException($result->errorMessage ?? 'Failed to add to cart');
        }

        $this->shipment->markAsCartAdded($result->shipmentId);
    }

    /**
     * Checkout the shipment.
     */
    private function checkout(LabelGeneratorInterface $labelGenerator): void
    {
        $result = $labelGenerator->checkout($this->shipment->cart_id);

        if (!$result->success) {
            Log::error('Failed to checkout shipment', [
                'shipment_id' => $this->shipment->id,
                'cart_id'     => $this->shipment->cart_id,
                'error'       => $result->errorMessage,
            ]);

            throw new \RuntimeException($result->errorMessage ?? 'Failed to checkout');
        }

        $this->shipment->markAsPurchased($result->shipmentId);
    }

    /**
     * Generate the label.
     */
    private function generateLabel(LabelGeneratorInterface $labelGenerator): void
    {
        $result = $labelGenerator->generateLabel($this->shipment->shipment_id);

        if (!$result->success) {
            Log::error('Failed to generate label', [
                'shipment_id'    => $this->shipment->id,
                'me_shipment_id' => $this->shipment->shipment_id,
                'error'          => $result->errorMessage,
            ]);

            throw new \RuntimeException($result->errorMessage ?? 'Failed to generate label');
        }

        $this->shipment->markAsLabelGenerated($result->labelUrl, $result->trackingNumber);
    }

    /**
     * Notify the customer about shipping.
     */
    private function notifyCustomer(): void
    {
        $order = $this->shipment->order;

        if (!$order) {
            return;
        }

        $email = $order->customer_email;

        if ($email) {
            Notification::route('mail', $email)
                ->notify(new ShipmentShippedNotification($this->shipment));
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Label generation job failed', [
            'shipment_id' => $this->shipment->id,
            'error'       => $exception->getMessage(),
        ]);
    }
}
