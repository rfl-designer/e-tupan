<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Checkout\Models\Order;
use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Jobs\{BatchGenerateLabelsJob, GenerateShipmentLabelJob};
use App\Domain\Shipping\Livewire\Admin\ShipmentManager;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = Admin::factory()->master()->withTwoFactor()->create();
    actingAsAdminWith2FA($this, $this->admin);
    Queue::fake();
});

describe('ShipmentManager Livewire Component', function (): void {
    it('renders the component', function (): void {
        Livewire::test(ShipmentManager::class)
            ->assertStatus(200)
            ->assertSee('Envios');
    });

    it('displays shipments list', function (): void {
        $order = Order::factory()->create(['order_number' => 'ORD-TEST-123']);
        Shipment::factory()->create([
            'order_id'       => $order->id,
            'recipient_name' => 'John Doe',
            'status'         => ShipmentStatus::Pending,
        ]);

        Livewire::test(ShipmentManager::class)
            ->assertSee('ORD-TEST-123')
            ->assertSee('John Doe')
            ->assertSee('Aguardando');
    });

    it('filters shipments by search', function (): void {
        $order1 = Order::factory()->create(['order_number' => 'ORD-FINDME-123']);
        $order2 = Order::factory()->create(['order_number' => 'ORD-ANOTHER-456']);

        Shipment::factory()->create(['order_id' => $order1->id, 'recipient_name' => 'John Doe']);
        Shipment::factory()->create(['order_id' => $order2->id, 'recipient_name' => 'Jane Smith']);

        Livewire::test(ShipmentManager::class)
            ->set('search', 'FINDME')
            ->assertSee('John Doe')
            ->assertDontSee('Jane Smith');
    });

    it('filters shipments by status', function (): void {
        Shipment::factory()->create([
            'status'         => ShipmentStatus::Pending,
            'recipient_name' => 'Pending User',
        ]);
        Shipment::factory()->delivered()->create([
            'recipient_name' => 'Delivered User',
        ]);

        Livewire::test(ShipmentManager::class)
            ->set('status', ShipmentStatus::Pending->value)
            ->assertSee('Pending User')
            ->assertDontSee('Delivered User');
    });

    it('dispatches generate label job for single shipment', function (): void {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);

        Livewire::test(ShipmentManager::class)
            ->call('generateLabel', $shipment->id)
            ->assertDispatched('notify');

        Queue::assertPushed(GenerateShipmentLabelJob::class, function ($job) use ($shipment) {
            return $job->shipment->id === $shipment->id;
        });
    });

    it('does not generate label for delivered shipment', function (): void {
        $shipment = Shipment::factory()->delivered()->create();

        Livewire::test(ShipmentManager::class)
            ->call('generateLabel', $shipment->id)
            ->assertDispatched('notify', type: 'error');

        Queue::assertNotPushed(GenerateShipmentLabelJob::class);
    });

    it('dispatches batch generate labels job for selected shipments', function (): void {
        $shipments = Shipment::factory()->count(3)->create(['status' => ShipmentStatus::Pending]);

        Livewire::test(ShipmentManager::class)
            ->set('selected', $shipments->pluck('id')->toArray())
            ->call('generateSelectedLabels')
            ->assertDispatched('notify', type: 'success');

        Queue::assertPushed(BatchGenerateLabelsJob::class);
    });

    it('marks shipment as posted', function (): void {
        $shipment = Shipment::factory()->withLabel()->create();

        Livewire::test(ShipmentManager::class)
            ->call('markAsPosted', $shipment->id)
            ->assertDispatched('notify', type: 'success');

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Posted);
        expect($shipment->posted_at)->not->toBeNull();
    });

    it('cancels shipment with confirmation', function (): void {
        $shipment = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);

        $mockGenerator = Mockery::mock(LabelGeneratorInterface::class);
        $mockGenerator->shouldReceive('cancelShipment')->andReturn(true);
        app()->instance(LabelGeneratorInterface::class, $mockGenerator);

        Livewire::test(ShipmentManager::class)
            ->call('confirmCancel', $shipment->id)
            ->assertSet('confirmCancelModal', true)
            ->assertSet('cancellingShipmentId', $shipment->id)
            ->call('cancelShipment')
            ->assertSet('confirmCancelModal', false)
            ->assertDispatched('notify', type: 'success');

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Cancelled);
    });

    it('does not cancel posted shipment', function (): void {
        $shipment = Shipment::factory()->posted()->create();

        Livewire::test(ShipmentManager::class)
            ->call('confirmCancel', $shipment->id)
            ->call('cancelShipment')
            ->assertDispatched('notify', type: 'error');

        $shipment->refresh();
        expect($shipment->status)->toBe(ShipmentStatus::Posted);
    });

    it('selects all shipments on current page', function (): void {
        Shipment::factory()->count(5)->create();

        Livewire::test(ShipmentManager::class)
            ->set('selectAll', true)
            ->assertSet('selected', fn ($selected) => count($selected) === 5);
    });
});

describe('BatchGenerateLabelsJob', function (): void {
    it('dispatches individual jobs for each shipment', function (): void {
        $shipments = Shipment::factory()->count(3)->create(['status' => ShipmentStatus::Pending]);

        $job = new BatchGenerateLabelsJob($shipments->pluck('id'));
        $job->handle();

        Queue::assertPushed(GenerateShipmentLabelJob::class, 3);
    });

    it('skips shipments that cannot generate labels', function (): void {
        $pending   = Shipment::factory()->create(['status' => ShipmentStatus::Pending]);
        $delivered = Shipment::factory()->delivered()->create();

        $job = new BatchGenerateLabelsJob([$pending->id, $delivered->id]);
        $job->handle();

        Queue::assertPushed(GenerateShipmentLabelJob::class, 1);
    });

    it('uses correct queue', function (): void {
        $job = new BatchGenerateLabelsJob(['id-1']);

        expect($job->queue)->toBe('shipping');
    });

    it('accepts collection of ids', function (): void {
        $shipments = Shipment::factory()->count(2)->create(['status' => ShipmentStatus::Pending]);

        $job = new BatchGenerateLabelsJob(collect($shipments->pluck('id')));
        $job->handle();

        Queue::assertPushed(GenerateShipmentLabelJob::class, 2);
    });
});
