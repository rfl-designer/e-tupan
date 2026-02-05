<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\{Shipment, ShipmentTracking};
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class TrackingService
{
    public function __construct(
        protected LabelGeneratorInterface $provider,
    ) {
    }

    /**
     * Get tracking info from carrier and update local records.
     */
    public function syncTracking(Shipment $shipment): bool
    {
        if (!$shipment->isTrackable()) {
            return false;
        }

        $trackingData = $this->provider->getTracking($shipment->shipment_id);

        if ($trackingData === null) {
            Log::warning('Failed to get tracking data', [
                'shipment_id'    => $shipment->id,
                'me_shipment_id' => $shipment->shipment_id,
            ]);

            return false;
        }

        $this->processTrackingEvents($shipment, $trackingData);

        return true;
    }

    /**
     * Process tracking events from carrier data.
     *
     * @param  array<string, mixed>  $trackingData
     */
    protected function processTrackingEvents(Shipment $shipment, array $trackingData): void
    {
        $events = $trackingData['tracking'] ?? [];

        foreach ($events as $event) {
            $this->createOrUpdateTrackingEvent($shipment, $event);
        }

        // Update shipment status based on latest event
        $this->updateShipmentStatus($shipment, $trackingData);
    }

    /**
     * Create or update a tracking event.
     *
     * @param  array<string, mixed>  $event
     */
    protected function createOrUpdateTrackingEvent(Shipment $shipment, array $event): ShipmentTracking
    {
        $eventAt   = $event['date'] ?? now();
        $eventCode = $event['code'] ?? null;

        // Check if event already exists
        $existing = ShipmentTracking::query()
            ->where('shipment_id', $shipment->id)
            ->where('event_at', $eventAt)
            ->where('event_code', $eventCode)
            ->first();

        if ($existing) {
            return $existing;
        }

        return ShipmentTracking::create([
            'shipment_id'       => $shipment->id,
            'event_code'        => $eventCode,
            'event_description' => $event['description'] ?? $event['status'] ?? 'Unknown',
            'status'            => $event['status'] ?? 'unknown',
            'city'              => $event['city'] ?? null,
            'state'             => $event['state'] ?? null,
            'country'           => $event['country'] ?? 'BR',
            'notes'             => $event['notes'] ?? null,
            'raw_data'          => $event,
            'event_at'          => $eventAt,
        ]);
    }

    /**
     * Update shipment status based on tracking data.
     *
     * @param  array<string, mixed>  $trackingData
     */
    protected function updateShipmentStatus(Shipment $shipment, array $trackingData): void
    {
        $status = $trackingData['status'] ?? null;

        if (!$status) {
            return;
        }

        $newStatus = ShipmentStatus::fromMelhorEnvioStatus($status);

        if ($newStatus === null || $newStatus === $shipment->status) {
            return;
        }

        $shipment->status = $newStatus;

        if ($newStatus === ShipmentStatus::Delivered) {
            $shipment->delivered_at = $trackingData['delivered_at'] ?? now();
        }

        $shipment->save();
    }

    /**
     * Get tracking history for a shipment.
     *
     * @return Collection<int, ShipmentTracking>
     */
    public function getTrackingHistory(Shipment $shipment): Collection
    {
        return $shipment->trackings()
            ->orderBy('event_at', 'desc')
            ->get();
    }

    /**
     * Find shipment by tracking number.
     */
    public function findByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return Shipment::query()
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    /**
     * Get public tracking information.
     *
     * @return array<string, mixed>|null
     */
    public function getPublicTrackingInfo(string $trackingNumber): ?array
    {
        $shipment = $this->findByTrackingNumber($trackingNumber);

        if (!$shipment) {
            return null;
        }

        $trackings = $this->getTrackingHistory($shipment);

        return [
            'tracking_number'    => $shipment->tracking_number,
            'carrier'            => $shipment->carrier_name,
            'service'            => $shipment->service_name,
            'status'             => $shipment->status,
            'status_label'       => $shipment->status->label(),
            'status_color'       => $shipment->status->color(),
            'recipient_city'     => $shipment->address_city,
            'recipient_state'    => $shipment->address_state,
            'estimated_delivery' => $shipment->estimated_delivery_at?->format('d/m/Y'),
            'delivered_at'       => $shipment->delivered_at?->format('d/m/Y H:i'),
            'events'             => $trackings->map(fn (ShipmentTracking $event) => [
                'date'        => $event->formatted_event_date,
                'description' => $event->event_description,
                'location'    => $event->formatted_location,
                'status'      => $event->status,
                'is_delivery' => $event->isDeliveryEvent(),
                'is_problem'  => $event->isProblemEvent(),
            ])->toArray(),
        ];
    }

    /**
     * Add manual tracking event.
     *
     * @param  array<string, mixed>  $data
     */
    public function addManualEvent(Shipment $shipment, array $data): ShipmentTracking
    {
        return ShipmentTracking::create([
            'shipment_id'       => $shipment->id,
            'event_code'        => $data['event_code'] ?? null,
            'event_description' => $data['description'],
            'status'            => $data['status'],
            'city'              => $data['city'] ?? null,
            'state'             => $data['state'] ?? null,
            'country'           => $data['country'] ?? 'BR',
            'notes'             => $data['notes'] ?? null,
            'event_at'          => $data['event_at'] ?? now(),
        ]);
    }
}
