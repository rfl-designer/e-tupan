<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\DTOs;

use App\Domain\Shipping\Models\Shipment;

readonly class ShipmentRequest
{
    /**
     * @param  array<string, mixed>  $products  List of products with quantity, name, value
     */
    public function __construct(
        public string $serviceCode,
        public string $quoteId,
        public int $insuranceValue,
        public bool $receipt,
        public bool $ownHand,
        public RecipientData $recipient,
        public AddressData $address,
        public PackageData $package,
        public array $products,
        public ?string $invoiceNumber = null,
        public ?string $invoiceKey = null,
    ) {
    }

    /**
     * Create from Shipment model.
     */
    public static function fromShipment(Shipment $shipment): self
    {
        $shipment->loadMissing('order.items');

        $products = [];

        foreach ($shipment->order->items as $item) {
            $products[] = [
                'name'          => $item->product_name,
                'quantity'      => $item->quantity,
                'unitary_value' => $item->sale_price ?? $item->unit_price,
            ];
        }

        return new self(
            serviceCode: $shipment->service_code,
            quoteId: $shipment->quote_id ?? '',
            insuranceValue: $shipment->order->total,
            receipt: false,
            ownHand: false,
            recipient: new RecipientData(
                name: $shipment->recipient_name,
                phone: $shipment->recipient_phone,
                email: $shipment->recipient_email,
                document: $shipment->recipient_document,
            ),
            address: new AddressData(
                postalCode: $shipment->address_zipcode,
                street: $shipment->address_street,
                number: $shipment->address_number,
                complement: $shipment->address_complement,
                neighborhood: $shipment->address_neighborhood,
                city: $shipment->address_city,
                stateAbbr: $shipment->address_state,
            ),
            package: new PackageData(
                weight: (float) $shipment->weight,
                height: (int) $shipment->height,
                width: (int) $shipment->width,
                length: (int) $shipment->length,
            ),
            products: $products,
        );
    }
}
