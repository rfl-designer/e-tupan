<?php

declare(strict_types = 1);

use App\Domain\Admin\Models\Admin;
use App\Domain\Shipping\Livewire\Admin\ShippingDashboardWidget;
use App\Domain\Shipping\Models\Shipment;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    $this->admin = Admin::factory()->create([
        'two_factor_confirmed_at' => now(),
    ]);
});

describe('ShippingDashboardWidget', function (): void {
    it('can render the component', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(ShippingDashboardWidget::class)
            ->assertOk()
            ->assertViewIs('livewire.admin.shipping-dashboard-widget');
    });

    it('displays pending shipments count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->pending()->count(3)->create();
        Shipment::factory()->cartAdded()->count(2)->create();
        Shipment::factory()->posted()->count(1)->create();

        Livewire::test(ShippingDashboardWidget::class)
            ->assertSee('5'); // 3 pending + 2 cart_added = 5 pending labels
    });

    it('displays in transit shipments count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->posted()->count(2)->create();
        Shipment::factory()->inTransit()->count(3)->create();
        Shipment::factory()->outForDelivery()->count(1)->create();

        Livewire::test(ShippingDashboardWidget::class)
            ->assertSee('6'); // 2 posted + 3 in_transit + 1 out_for_delivery
    });

    it('displays delivered today count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        // Delivered today
        Shipment::factory()->delivered()->count(2)->create([
            'updated_at' => now(),
        ]);

        // Delivered yesterday (should not count)
        Shipment::factory()->delivered()->count(3)->create([
            'updated_at' => now()->subDay(),
        ]);

        $component = Livewire::test(ShippingDashboardWidget::class);

        expect($component->viewData('stats')['delivered_today'])->toBe(2);
    });

    it('displays delivered this week count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        // Delivered this week
        Shipment::factory()->delivered()->count(5)->create([
            'updated_at' => now()->startOfWeek()->addDay(),
        ]);

        // Delivered last week (should not count)
        Shipment::factory()->delivered()->count(2)->create([
            'updated_at' => now()->subWeek(),
        ]);

        $component = Livewire::test(ShippingDashboardWidget::class);

        expect($component->viewData('stats')['delivered_this_week'])->toBe(5);
    });

    it('displays delivered this month count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        // Delivered this month
        Shipment::factory()->delivered()->count(10)->create([
            'updated_at' => now()->startOfMonth()->addDays(5),
        ]);

        // Delivered last month (should not count)
        Shipment::factory()->delivered()->count(3)->create([
            'updated_at' => now()->subMonth(),
        ]);

        $component = Livewire::test(ShippingDashboardWidget::class);

        expect($component->viewData('stats')['delivered_this_month'])->toBe(10);
    });

    it('displays problems count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->returned()->count(4)->create();
        Shipment::factory()->delivered()->count(5)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        expect($component->viewData('stats')['problems'])->toBe(4);
    });

    it('lists pending shipments', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        $shipments = Shipment::factory()->pending()->count(3)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        $pendingShipments = $component->viewData('pendingShipments');
        expect($pendingShipments)->toHaveCount(3);
    });

    it('limits pending shipments to 5', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->pending()->count(10)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        $pendingShipments = $component->viewData('pendingShipments');
        expect($pendingShipments)->toHaveCount(5);
    });

    it('orders pending shipments by oldest first', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        $oldest = Shipment::factory()->pending()->create([
            'created_at' => now()->subDays(5),
        ]);

        $newest = Shipment::factory()->pending()->create([
            'created_at' => now(),
        ]);

        $component = Livewire::test(ShippingDashboardWidget::class);

        $pendingShipments = $component->viewData('pendingShipments');
        expect($pendingShipments->first()->id)->toBe($oldest->id);
    });

    it('lists in transit shipments', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->inTransit()->count(3)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        $inTransitShipments = $component->viewData('inTransitShipments');
        expect($inTransitShipments)->toHaveCount(3);
    });

    it('limits in transit shipments to 5', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->inTransit()->count(10)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        $inTransitShipments = $component->viewData('inTransitShipments');
        expect($inTransitShipments)->toHaveCount(5);
    });

    it('orders in transit shipments by most recently updated', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        $oldest = Shipment::factory()->inTransit()->create([
            'updated_at' => now()->subDays(5),
        ]);

        $newest = Shipment::factory()->inTransit()->create([
            'updated_at' => now(),
        ]);

        $component = Livewire::test(ShippingDashboardWidget::class);

        $inTransitShipments = $component->viewData('inTransitShipments');
        expect($inTransitShipments->first()->id)->toBe($newest->id);
    });

    it('shows empty state when no pending shipments', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(ShippingDashboardWidget::class)
            ->assertSee(__('Nenhuma etiqueta pendente'));
    });

    it('shows empty state when no in transit shipments', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Livewire::test(ShippingDashboardWidget::class)
            ->assertSee(__('Nenhum envio em transito'));
    });

    it('includes shipped shipments in in_transit count', function (): void {
        actingAsAdminWith2FA($this, $this->admin);

        Shipment::factory()->posted()->count(2)->create();
        Shipment::factory()->inTransit()->count(1)->create();

        $component = Livewire::test(ShippingDashboardWidget::class);

        expect($component->viewData('stats')['in_transit'])->toBe(3);
    });
});
