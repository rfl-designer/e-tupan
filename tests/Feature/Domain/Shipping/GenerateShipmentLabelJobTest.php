<?php

declare(strict_types = 1);

use App\Domain\Checkout\Events\OrderCreated;
use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\DTOs\LabelResult;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Jobs\GenerateShipmentLabelJob;
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Notifications\ShipmentShippedNotification;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Event::fake([OrderCreated::class]);
    Notification::fake();
});

describe('GenerateShipmentLabelJob', function (): void {
    it('processes pending shipment through full workflow', function (): void {
        $order = Order::factory()->create([
            'guest_email' => 'customer@example.com',
        ]);
        $shipment = Shipment::factory()->create([
            'order_id' => $order->id,
            'status'   => ShipmentStatus::Pending,
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('addToCart')
            ->once()
            ->andReturn(new LabelResult(
                success: true,
                shipmentId: 'cart-123',
            ));
        $mockGenerator->shouldReceive('checkout')
            ->once()
            ->with('cart-123')
            ->andReturn(new LabelResult(
                success: true,
                shipmentId: 'shipment-456',
            ));
        $mockGenerator->shouldReceive('generateLabel')
            ->once()
            ->with('shipment-456')
            ->andReturn(LabelResult::success(
                labelUrl: 'https://example.com/label.pdf',
                trackingNumber: 'ABC123456789BR',
                shipmentId: 'shipment-456',
            ));

        app()->instance(LabelGeneratorInterface::class, $mockGenerator);

        $job = new GenerateShipmentLabelJob($shipment);
        $job->handle($mockGenerator);

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Generated);
        expect($shipment->cart_id)->toBe('cart-123');
        expect($shipment->shipment_id)->toBe('shipment-456');
        expect($shipment->label_url)->toBe('https://example.com/label.pdf');
        expect($shipment->tracking_number)->toBe('ABC123456789BR');

        Notification::assertSentTo(
            new \Illuminate\Notifications\AnonymousNotifiable(),
            ShipmentShippedNotification::class,
        );
    });

    it('continues from cart added status', function (): void {
        $order    = Order::factory()->create();
        $shipment = Shipment::factory()->cartAdded()->create([
            'order_id' => $order->id,
            'cart_id'  => 'existing-cart-123',
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldNotReceive('addToCart');
        $mockGenerator->shouldReceive('checkout')
            ->once()
            ->with('existing-cart-123')
            ->andReturn(new LabelResult(
                success: true,
                shipmentId: 'shipment-789',
            ));
        $mockGenerator->shouldReceive('generateLabel')
            ->once()
            ->andReturn(LabelResult::success(
                labelUrl: 'https://example.com/label2.pdf',
                trackingNumber: 'XYZ987654321BR',
                shipmentId: 'shipment-789',
            ));

        $job = new GenerateShipmentLabelJob($shipment, notifyCustomer: false);
        $job->handle($mockGenerator);

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Generated);
        expect($shipment->shipment_id)->toBe('shipment-789');

        Notification::assertNothingSent();
    });

    it('continues from purchased status', function (): void {
        $order    = Order::factory()->create();
        $shipment = Shipment::factory()->purchased()->create([
            'order_id'    => $order->id,
            'shipment_id' => 'existing-shipment-456',
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldNotReceive('addToCart');
        $mockGenerator->shouldNotReceive('checkout');
        $mockGenerator->shouldReceive('generateLabel')
            ->once()
            ->with('existing-shipment-456')
            ->andReturn(LabelResult::success(
                labelUrl: 'https://example.com/label3.pdf',
                trackingNumber: 'QWE123456789BR',
                shipmentId: 'existing-shipment-456',
            ));

        $job = new GenerateShipmentLabelJob($shipment, notifyCustomer: false);
        $job->handle($mockGenerator);

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Generated);
        expect($shipment->label_url)->toBe('https://example.com/label3.pdf');
    });

    it('throws exception when add to cart fails', function (): void {
        $shipment = Shipment::factory()->create([
            'status' => ShipmentStatus::Pending,
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('addToCart')
            ->once()
            ->andReturn(LabelResult::failure('Invalid package dimensions'));

        $job = new GenerateShipmentLabelJob($shipment);

        expect(fn () => $job->handle($mockGenerator))
            ->toThrow(RuntimeException::class, 'Invalid package dimensions');
    });

    it('throws exception when checkout fails', function (): void {
        $shipment = Shipment::factory()->cartAdded()->create([
            'cart_id' => 'cart-123',
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('checkout')
            ->once()
            ->andReturn(LabelResult::failure('Insufficient balance'));

        $job = new GenerateShipmentLabelJob($shipment);

        expect(fn () => $job->handle($mockGenerator))
            ->toThrow(RuntimeException::class, 'Insufficient balance');
    });

    it('throws exception when generate label fails', function (): void {
        $shipment = Shipment::factory()->purchased()->create([
            'shipment_id' => 'shipment-456',
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateLabel')
            ->once()
            ->andReturn(LabelResult::failure('API Error'));

        $job = new GenerateShipmentLabelJob($shipment);

        expect(fn () => $job->handle($mockGenerator))
            ->toThrow(RuntimeException::class, 'API Error');
    });

    it('skips notification when flag is false', function (): void {
        $order    = Order::factory()->create();
        $shipment = Shipment::factory()->purchased()->create([
            'order_id' => $order->id,
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateLabel')
            ->andReturn(LabelResult::success(
                labelUrl: 'https://example.com/label.pdf',
                trackingNumber: 'ABC123',
                shipmentId: 'ship-1',
            ));

        $job = new GenerateShipmentLabelJob($shipment, notifyCustomer: false);
        $job->handle($mockGenerator);

        Notification::assertNothingSent();
    });

    it('handles missing order email gracefully', function (): void {
        // Create guest order with no email (user_id null, guest_email null)
        $order = Order::factory()->create([
            'user_id'     => null,
            'guest_email' => null,
        ]);
        $shipment = Shipment::factory()->purchased()->create([
            'order_id' => $order->id,
        ]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('generateLabel')
            ->andReturn(LabelResult::success(
                labelUrl: 'https://example.com/label.pdf',
                trackingNumber: 'ABC123',
                shipmentId: 'ship-1',
            ));

        $job = new GenerateShipmentLabelJob($shipment, notifyCustomer: true);
        $job->handle($mockGenerator);

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Generated);
        Notification::assertNothingSent();
    });

    it('uses correct queue', function (): void {
        $shipment = Shipment::factory()->create();
        $job      = new GenerateShipmentLabelJob($shipment);

        expect($job->queue)->toBe('shipping');
    });

    it('has correct retry configuration', function (): void {
        $shipment = Shipment::factory()->create();
        $job      = new GenerateShipmentLabelJob($shipment);

        expect($job->tries)->toBe(3);
        expect($job->backoff)->toBe([60, 300, 900]);
    });
});

describe('ShipmentShippedNotification', function (): void {
    it('sends email with correct content', function (): void {
        $order = Order::factory()->create([
            'order_number' => 'ORD-12345',
            'guest_email'  => 'customer@test.com',
        ]);

        $shipment = Shipment::factory()->withLabel()->create([
            'order_id'             => $order->id,
            'tracking_number'      => 'ABC123456789BR',
            'carrier_name'         => 'Correios',
            'recipient_name'       => 'John Doe',
            'address_street'       => 'Av Paulista',
            'address_number'       => '1000',
            'address_neighborhood' => 'Bela Vista',
            'address_city'         => 'Sao Paulo',
            'address_state'        => 'SP',
            'address_zipcode'      => '01310-100',
        ]);

        $notification = new ShipmentShippedNotification($shipment);
        $mailMessage  = $notification->toMail(new \stdClass());

        expect($mailMessage->subject)->toContain('ORD-12345');
        expect($mailMessage->subject)->toContain('enviado');
    });

    it('includes tracking number in notification array', function (): void {
        $shipment = Shipment::factory()->withLabel()->create([
            'tracking_number' => 'ABC123456789BR',
            'carrier_name'    => 'Correios',
        ]);

        $notification = new ShipmentShippedNotification($shipment);
        $array        = $notification->toArray(new \stdClass());

        expect($array)->toHaveKey('shipment_id');
        expect($array)->toHaveKey('tracking_number');
        expect($array['tracking_number'])->toBe('ABC123456789BR');
    });
});
