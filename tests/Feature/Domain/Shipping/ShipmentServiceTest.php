<?php

declare(strict_types = 1);

use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Services\ShipmentService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->service = app(ShipmentService::class);
});

describe('ShipmentService', function (): void {
    it('creates shipment from order', function (): void {
        $order = Order::factory()->create([
            'shipping_method'         => 'pac',
            'shipping_carrier'        => 'Correios',
            'shipping_cost'           => 1500,
            'shipping_days'           => 7,
            'shipping_zipcode'        => '01310-100',
            'shipping_street'         => 'Av Paulista',
            'shipping_number'         => '1000',
            'shipping_neighborhood'   => 'Bela Vista',
            'shipping_city'           => 'Sao Paulo',
            'shipping_state'          => 'SP',
            'shipping_recipient_name' => 'Test User',
        ]);

        $shipment = $this->service->createFromOrder($order);

        expect($shipment)->toBeInstanceOf(Shipment::class);
        expect($shipment->order_id)->toBe($order->id);
        expect($shipment->carrier_code)->toBe('correios_pac');
        expect($shipment->service_name)->toBe('PAC');
        expect($shipment->shipping_cost)->toBe(1500);
        expect($shipment->status)->toBe(ShipmentStatus::Pending);
    });

    it('gets shipment by order', function (): void {
        $order    = Order::factory()->create();
        $shipment = Shipment::factory()->create(['order_id' => $order->id]);

        $found = $this->service->getByOrder($order);

        expect($found->id)->toBe($shipment->id);
    });

    it('returns null for order without shipment', function (): void {
        $order = Order::factory()->create();

        $found = $this->service->getByOrder($order);

        expect($found)->toBeNull();
    });

    it('gets shipment by tracking number', function (): void {
        $shipment = Shipment::factory()->withLabel()->create([
            'tracking_number' => 'ABC123456789BR',
        ]);

        $found = $this->service->getByTrackingNumber('ABC123456789BR');

        expect($found->id)->toBe($shipment->id);
    });

    it('updates shipment status', function (): void {
        $shipment = Shipment::factory()->create([
            'status' => ShipmentStatus::Pending,
        ]);

        $this->service->updateStatus($shipment, ShipmentStatus::Posted);

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Posted);
        expect($shipment->posted_at)->not->toBeNull();
    });

    it('gets awaiting processing shipments', function (): void {
        Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        Shipment::factory()->posted()->create();

        $shipments = $this->service->getAwaitingProcessing();

        expect($shipments)->toHaveCount(2);
    });

    it('gets in transit shipments', function (): void {
        Shipment::factory()->posted()->create();
        Shipment::factory()->inTransit()->create();
        Shipment::factory()->delivered()->create();

        $shipments = $this->service->getInTransit();

        expect($shipments)->toHaveCount(2);
    });

    it('gets delayed shipments', function (): void {
        Shipment::factory()->delayed()->create();
        Shipment::factory()->inTransit()->create();

        $shipments = $this->service->getDelayed();

        expect($shipments)->toHaveCount(1);
    });

    it('returns dashboard statistics', function (): void {
        Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        Shipment::factory()->inTransit()->create();
        Shipment::factory()->delivered()->create(['delivered_at' => now()]);

        $stats = $this->service->getDashboardStats();

        expect($stats['awaiting_shipment'])->toBe(2);
        expect($stats['in_transit'])->toBe(1);
        expect($stats['delivered_today'])->toBe(1);
    });
});

describe('Shipment Model', function (): void {
    it('marks shipment as cart added', function (): void {
        $shipment = Shipment::factory()->create();

        $shipment->markAsCartAdded('cart-123');

        expect($shipment->cart_id)->toBe('cart-123');
        expect($shipment->status)->toBe(ShipmentStatus::CartAdded);
    });

    it('marks shipment as purchased', function (): void {
        $shipment = Shipment::factory()->cartAdded()->create();

        $shipment->markAsPurchased('shipment-456');

        expect($shipment->shipment_id)->toBe('shipment-456');
        expect($shipment->status)->toBe(ShipmentStatus::Purchased);
    });

    it('marks shipment as label generated', function (): void {
        $shipment = Shipment::factory()->purchased()->create();

        $shipment->markAsLabelGenerated('https://example.com/label.pdf', 'ABC123');

        expect($shipment->label_url)->toBe('https://example.com/label.pdf');
        expect($shipment->tracking_number)->toBe('ABC123');
        expect($shipment->status)->toBe(ShipmentStatus::Generated);
    });

    it('marks shipment as delivered', function (): void {
        $shipment = Shipment::factory()->inTransit()->create();

        $shipment->markAsDelivered();

        expect($shipment->status)->toBe(ShipmentStatus::Delivered);
        expect($shipment->delivered_at)->not->toBeNull();
    });

    it('checks if shipment can be cancelled', function (): void {
        $pending = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        $posted  = Shipment::factory()->posted()->create();

        expect($pending->canBeCancelled())->toBeTrue();
        expect($posted->canBeCancelled())->toBeFalse();
    });

    it('checks if shipment can generate label', function (): void {
        $pending   = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        $delivered = Shipment::factory()->delivered()->create();

        expect($pending->canGenerateLabel())->toBeTrue();
        expect($delivered->canGenerateLabel())->toBeFalse();
    });
});
