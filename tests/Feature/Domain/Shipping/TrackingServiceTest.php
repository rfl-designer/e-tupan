<?php

declare(strict_types = 1);

use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\{Shipment, ShipmentTracking};
use App\Domain\Shipping\Services\TrackingService;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->mockProvider = Mockery::mock(LabelGeneratorInterface::class);
    app()->instance(LabelGeneratorInterface::class, $this->mockProvider);
    $this->service = app(TrackingService::class);
});

describe('TrackingService', function (): void {
    it('finds shipment by tracking number', function (): void {
        $shipment = Shipment::factory()->withLabel()->create([
            'tracking_number' => 'ABC123456789BR',
        ]);

        $found = $this->service->findByTrackingNumber('ABC123456789BR');

        expect($found->id)->toBe($shipment->id);
    });

    it('returns null for unknown tracking number', function (): void {
        $found = $this->service->findByTrackingNumber('UNKNOWN123');

        expect($found)->toBeNull();
    });

    it('gets tracking history ordered by date desc', function (): void {
        $shipment = Shipment::factory()->posted()->create();

        $event1 = ShipmentTracking::factory()->create([
            'shipment_id' => $shipment->id,
            'event_at'    => now()->subDays(2),
        ]);
        $event2 = ShipmentTracking::factory()->create([
            'shipment_id' => $shipment->id,
            'event_at'    => now()->subDay(),
        ]);
        $event3 = ShipmentTracking::factory()->create([
            'shipment_id' => $shipment->id,
            'event_at'    => now(),
        ]);

        $history = $this->service->getTrackingHistory($shipment);

        expect($history)->toHaveCount(3);
        expect($history->first()->id)->toBe($event3->id);
        expect($history->last()->id)->toBe($event1->id);
    });

    it('adds manual tracking event', function (): void {
        $shipment = Shipment::factory()->posted()->create();

        $event = $this->service->addManualEvent($shipment, [
            'description' => 'Objeto extraviado',
            'status'      => 'exception',
            'city'        => 'Sao Paulo',
            'state'       => 'SP',
        ]);

        expect($event)->toBeInstanceOf(ShipmentTracking::class);
        expect($event->event_description)->toBe('Objeto extraviado');
        expect($event->status)->toBe('exception');
        expect($event->city)->toBe('Sao Paulo');
    });

    it('syncs tracking from provider', function (): void {
        $shipment = Shipment::factory()->posted()->create([
            'shipment_id' => 'me-123',
        ]);

        $this->mockProvider->shouldReceive('getTracking')
            ->once()
            ->with('me-123')
            ->andReturn([
                'status'   => 'in_transit',
                'tracking' => [
                    [
                        'code'        => 'BDE',
                        'description' => 'Objeto postado',
                        'status'      => 'posted',
                        'city'        => 'Sao Paulo',
                        'state'       => 'SP',
                        'date'        => now()->subDays(2)->toDateTimeString(),
                    ],
                    [
                        'code'        => 'RO',
                        'description' => 'Objeto em transito',
                        'status'      => 'in_transit',
                        'city'        => 'Curitiba',
                        'state'       => 'PR',
                        'date'        => now()->subDay()->toDateTimeString(),
                    ],
                ],
            ]);

        $result = $this->service->syncTracking($shipment);

        expect($result)->toBeTrue();

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::InTransit);
        expect($shipment->trackings)->toHaveCount(2);
    });

    it('returns false when shipment is not trackable', function (): void {
        $shipment = Shipment::factory()->create([
            'status' => ShipmentStatus::Pending,
        ]);

        $result = $this->service->syncTracking($shipment);

        expect($result)->toBeFalse();
    });

    it('returns false when provider returns null', function (): void {
        $shipment = Shipment::factory()->posted()->create([
            'shipment_id' => 'me-123',
        ]);

        $this->mockProvider->shouldReceive('getTracking')
            ->once()
            ->andReturn(null);

        $result = $this->service->syncTracking($shipment);

        expect($result)->toBeFalse();
    });

    it('does not duplicate tracking events', function (): void {
        $shipment = Shipment::factory()->posted()->create([
            'shipment_id' => 'me-123',
        ]);

        $eventDate = now()->subDay()->toDateTimeString();

        $this->mockProvider->shouldReceive('getTracking')
            ->twice()
            ->andReturn([
                'status'   => 'in_transit',
                'tracking' => [
                    [
                        'code'        => 'RO',
                        'description' => 'Objeto em transito',
                        'status'      => 'in_transit',
                        'date'        => $eventDate,
                    ],
                ],
            ]);

        $this->service->syncTracking($shipment);
        $this->service->syncTracking($shipment);

        expect($shipment->trackings()->count())->toBe(1);
    });

    it('gets public tracking info', function (): void {
        $shipment = Shipment::factory()->inTransit()->create([
            'tracking_number' => 'TEST123BR',
            'carrier_name'    => 'Correios',
            'service_name'    => 'SEDEX',
            'address_city'    => 'Curitiba',
            'address_state'   => 'PR',
        ]);

        ShipmentTracking::factory()->posted()->create([
            'shipment_id' => $shipment->id,
            'event_at'    => now()->subDays(2),
        ]);

        $info = $this->service->getPublicTrackingInfo('TEST123BR');

        expect($info)->not->toBeNull();
        expect($info['tracking_number'])->toBe('TEST123BR');
        expect($info['carrier'])->toBe('Correios');
        expect($info['service'])->toBe('SEDEX');
        expect($info['status_label'])->toBe('Em Transito');
        expect($info['events'])->toHaveCount(1);
    });

    it('returns null for unknown tracking in public info', function (): void {
        $info = $this->service->getPublicTrackingInfo('UNKNOWN123');

        expect($info)->toBeNull();
    });
});

describe('ShipmentTracking Model', function (): void {
    it('formats location correctly', function (): void {
        $tracking = ShipmentTracking::factory()->create([
            'city'  => 'Sao Paulo',
            'state' => 'SP',
        ]);

        expect($tracking->formatted_location)->toBe('Sao Paulo/SP');
    });

    it('handles null location', function (): void {
        $tracking = ShipmentTracking::factory()->create([
            'city'  => null,
            'state' => null,
        ]);

        expect($tracking->formatted_location)->toBeNull();
    });

    it('detects delivery events', function (): void {
        $delivered = ShipmentTracking::factory()->delivered()->create();
        $transit   = ShipmentTracking::factory()->inTransit()->create();

        expect($delivered->isDeliveryEvent())->toBeTrue();
        expect($transit->isDeliveryEvent())->toBeFalse();
    });

    it('detects problem events', function (): void {
        $returned = ShipmentTracking::factory()->returned()->create();
        $transit  = ShipmentTracking::factory()->inTransit()->create();

        expect($returned->isProblemEvent())->toBeTrue();
        expect($transit->isProblemEvent())->toBeFalse();
    });
});

describe('Tracking Controller', function (): void {
    it('shows tracking search page', function (): void {
        $this->get(route('tracking.index'))
            ->assertOk()
            ->assertSee('Rastrear Pedido');
    });

    it('shows tracking details for valid code', function (): void {
        $shipment = Shipment::factory()->posted()->create([
            'tracking_number' => 'VALID123BR',
        ]);

        $this->get(route('tracking.show', ['code' => 'VALID123BR']))
            ->assertOk()
            ->assertSee('VALID123BR');
    });

    it('shows not found for invalid code', function (): void {
        $this->get(route('tracking.show', ['code' => 'INVALID123']))
            ->assertOk()
            ->assertSee('Rastreamento nao encontrado');
    });

    it('redirects to show page on search', function (): void {
        $this->post(route('tracking.search'), ['code' => 'TEST123BR'])
            ->assertRedirect(route('tracking.show', ['code' => 'TEST123BR']));
    });

    it('validates code on search', function (): void {
        $this->post(route('tracking.search'), ['code' => ''])
            ->assertSessionHasErrors('code');
    });
});
