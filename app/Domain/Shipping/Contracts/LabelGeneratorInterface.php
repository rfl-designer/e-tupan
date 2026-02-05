<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Contracts;

use App\Domain\Shipping\DTOs\{LabelResult, ShipmentRequest};

interface LabelGeneratorInterface
{
    /**
     * Add shipment to cart for checkout.
     */
    public function addToCart(ShipmentRequest $request): LabelResult;

    /**
     * Checkout (purchase) the shipment.
     */
    public function checkout(string $cartId): LabelResult;

    /**
     * Generate label for a purchased shipment.
     */
    public function generateLabel(string $shipmentId): LabelResult;

    /**
     * Print label (get printable URL).
     */
    public function printLabel(string $shipmentId): LabelResult;

    /**
     * Cancel a shipment.
     */
    public function cancelShipment(string $shipmentId): bool;

    /**
     * Get shipment tracking info.
     *
     * @return array<string, mixed>|null
     */
    public function getTracking(string $shipmentId): ?array;
}
