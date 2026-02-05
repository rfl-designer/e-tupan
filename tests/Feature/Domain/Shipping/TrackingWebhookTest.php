<?php

declare(strict_types = 1);

use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\Jobs\ProcessTrackingWebhookJob;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Support\Facades\Queue;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Queue::fake();
});

describe('TrackingWebhookController', function (): void {
    it('accepts valid webhook and dispatches job', function (): void {
        $payload = [
            'event'       => 'tracking_update',
            'shipment_id' => 'me-shipment-123',
            'status'      => 'in_transit',
            'tracking'    => [
                'code'        => 'RO',
                'description' => 'Objeto em transito',
                'status'      => 'in_transit',
                'city'        => 'Curitiba',
                'state'       => 'PR',
                'date'        => now()->toDateTimeString(),
            ],
        ];

        $this->postJson(route('api.webhooks.melhor-envio.tracking'), $payload)
            ->assertOk()
            ->assertJson(['status' => 'received']);

        Queue::assertPushed(ProcessTrackingWebhookJob::class, function ($job) {
            return $job->payload['shipment_id'] === 'me-shipment-123';
        });
    });

    it('handles empty payload', function (): void {
        $this->postJson(route('api.webhooks.melhor-envio.tracking'), [])
            ->assertOk()
            ->assertJson(['status' => 'received']);

        Queue::assertPushed(ProcessTrackingWebhookJob::class);
    });
});

describe('ProcessTrackingWebhookJob', function (): void {
    it('processes tracking update for existing shipment', function (): void {
        Queue::fake([]);

        $shipment = Shipment::factory()->posted()->create([
            'shipment_id' => 'me-shipment-456',
        ]);

        $mockProvider = Mockery::mock(LabelGeneratorInterface::class);
        $mockProvider->shouldReceive('getTracking')->andReturn([
            'status'   => 'in_transit',
            'tracking' => [],
        ]);
        app()->instance(LabelGeneratorInterface::class, $mockProvider);

        $payload = [
            'shipment_id' => 'me-shipment-456',
            'status'      => 'in_transit',
            'tracking'    => [
                'code'        => 'RO',
                'description' => 'Objeto em transito',
                'status'      => 'in_transit',
            ],
        ];

        $job = new ProcessTrackingWebhookJob($payload);
        $job->handle(app(\App\Domain\Shipping\Services\TrackingService::class));

        expect($shipment->trackings()->count())->toBe(1);
        expect($shipment->trackings()->first()->event_description)->toBe('Objeto em transito');
    });

    it('ignores unknown shipment', function (): void {
        Queue::fake([]);

        $mockProvider = Mockery::mock(LabelGeneratorInterface::class);
        app()->instance(LabelGeneratorInterface::class, $mockProvider);

        $payload = [
            'shipment_id' => 'unknown-shipment-id',
            'status'      => 'in_transit',
        ];

        $job = new ProcessTrackingWebhookJob($payload);
        $job->handle(app(\App\Domain\Shipping\Services\TrackingService::class));

        // Should not throw, just log warning
        expect(true)->toBeTrue();
    });

    it('ignores payload without shipment_id', function (): void {
        Queue::fake([]);

        $mockProvider = Mockery::mock(LabelGeneratorInterface::class);
        app()->instance(LabelGeneratorInterface::class, $mockProvider);

        $payload = [
            'status' => 'in_transit',
        ];

        $job = new ProcessTrackingWebhookJob($payload);
        $job->handle(app(\App\Domain\Shipping\Services\TrackingService::class));

        // Should not throw, just log warning
        expect(true)->toBeTrue();
    });

    it('uses correct queue', function (): void {
        $job = new ProcessTrackingWebhookJob(['test' => 'data']);

        expect($job->queue)->toBe('shipping');
    });

    it('has correct retry configuration', function (): void {
        $job = new ProcessTrackingWebhookJob(['test' => 'data']);

        expect($job->tries)->toBe(3);
        expect($job->backoff)->toBe([30, 60, 120]);
    });
});
