<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Services;

use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Contracts\ShippingProviderInterface;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Support\Facades\Log;

class ShipmentService
{
    public function __construct(
        private ShippingProviderInterface $provider,
        private PackageCalculator $packageCalculator,
    ) {
    }

    /**
     * Create a shipment for an order.
     */
    public function createFromOrder(Order $order): Shipment
    {
        $order->loadMissing(['items.product', 'items.variant']);

        $package = $this->packageCalculator->calculateForOrder($order);

        $shipment = Shipment::query()->create([
            'order_id'              => $order->id,
            'quote_id'              => $order->metadata['shipping_quote_id'] ?? null,
            'carrier_code'          => $this->mapCarrierCode($order->shipping_method),
            'carrier_name'          => $order->shipping_carrier ?? 'Correios',
            'service_code'          => $order->shipping_method,
            'service_name'          => $this->getServiceName($order->shipping_method),
            'shipping_cost'         => $order->shipping_cost,
            'insurance_cost'        => 0,
            'delivery_days_min'     => $order->shipping_days,
            'delivery_days_max'     => $order->shipping_days,
            'estimated_delivery_at' => now()->addWeekdays($order->shipping_days),
            'recipient_name'        => $order->shipping_recipient_name ?? $order->customer_name,
            'recipient_phone'       => $order->customer_phone,
            'recipient_email'       => $order->customer_email,
            'recipient_document'    => $order->customer_cpf,
            'address_zipcode'       => $order->shipping_zipcode,
            'address_street'        => $order->shipping_street,
            'address_number'        => $order->shipping_number,
            'address_complement'    => $order->shipping_complement,
            'address_neighborhood'  => $order->shipping_neighborhood,
            'address_city'          => $order->shipping_city,
            'address_state'         => $order->shipping_state,
            'weight'                => $package['weight'],
            'height'                => $package['height'],
            'width'                 => $package['width'],
            'length'                => $package['length'],
            'status'                => ShipmentStatus::Pending,
        ]);

        Log::info('Shipment created', [
            'shipment_id' => $shipment->id,
            'order_id'    => $order->id,
            'carrier'     => $shipment->carrier_code,
        ]);

        return $shipment;
    }

    /**
     * Get shipment by order.
     */
    public function getByOrder(Order $order): ?Shipment
    {
        return Shipment::query()
            ->where('order_id', $order->id)
            ->first();
    }

    /**
     * Get shipment by tracking number.
     */
    public function getByTrackingNumber(string $trackingNumber): ?Shipment
    {
        return Shipment::query()
            ->where('tracking_number', $trackingNumber)
            ->first();
    }

    /**
     * Update shipment status.
     */
    public function updateStatus(Shipment $shipment, ShipmentStatus $status): Shipment
    {
        $shipment->status = $status;

        if ($status === ShipmentStatus::Posted) {
            $shipment->posted_at = now();
        }

        if ($status === ShipmentStatus::Delivered) {
            $shipment->delivered_at = now();
        }

        if ($status === ShipmentStatus::Cancelled) {
            $shipment->cancelled_at = now();
        }

        $shipment->save();

        Log::info('Shipment status updated', [
            'shipment_id' => $shipment->id,
            'status'      => $status->value,
        ]);

        return $shipment;
    }

    /**
     * Map shipping method to carrier code.
     */
    private function mapCarrierCode(?string $method): string
    {
        if ($method === null) {
            return 'correios_pac';
        }

        return match (strtolower($method)) {
            'pac', '1', 'correios_pac' => 'correios_pac',
            'sedex', '2', 'correios_sedex' => 'correios_sedex',
            'sedex_10' => 'correios_sedex_10',
            'jadlog_package', '3' => 'jadlog_package',
            'jadlog_com', '4' => 'jadlog_com',
            default => 'correios_pac',
        };
    }

    /**
     * Get human-readable service name.
     */
    private function getServiceName(?string $method): string
    {
        if ($method === null) {
            return 'PAC';
        }

        return match (strtolower($method)) {
            'pac', '1', 'correios_pac' => 'PAC',
            'sedex', '2', 'correios_sedex' => 'SEDEX',
            'sedex_10' => 'SEDEX 10',
            'jadlog_package', '3' => 'Jadlog Package',
            'jadlog_com', '4' => 'Jadlog .Com',
            default => ucfirst($method),
        };
    }

    /**
     * Get shipments awaiting processing.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shipment>
     */
    public function getAwaitingProcessing(): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::query()
            ->with('order')
            ->pending()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get shipments awaiting label.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shipment>
     */
    public function getAwaitingLabel(): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::query()
            ->with('order')
            ->awaitingLabel()
            ->orderBy('created_at')
            ->get();
    }

    /**
     * Get shipments in transit.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shipment>
     */
    public function getInTransit(): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::query()
            ->with('order')
            ->inTransit()
            ->orderBy('posted_at', 'desc')
            ->get();
    }

    /**
     * Get delayed shipments.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, Shipment>
     */
    public function getDelayed(): \Illuminate\Database\Eloquent\Collection
    {
        return Shipment::query()
            ->with('order')
            ->delayed()
            ->orderBy('estimated_delivery_at')
            ->get();
    }

    /**
     * Get dashboard statistics.
     *
     * @return array<string, int>
     */
    public function getDashboardStats(): array
    {
        return [
            'awaiting_shipment' => Shipment::query()->pending()->count(),
            'in_transit'        => Shipment::query()->inTransit()->count(),
            'delivered_today'   => Shipment::query()->deliveredToday()->count(),
            'delayed'           => Shipment::query()->delayed()->count(),
        ];
    }
}
