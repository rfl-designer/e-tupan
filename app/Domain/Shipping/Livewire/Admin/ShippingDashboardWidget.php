<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Livewire\Admin;

use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Models\Shipment;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ShippingDashboardWidget extends Component
{
    /**
     * Get shipments requiring attention (pending label generation).
     *
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function pendingShipments(): Collection
    {
        return Shipment::query()
            ->with(['order'])
            ->whereIn('status', [
                ShipmentStatus::Pending,
                ShipmentStatus::CartAdded,
                ShipmentStatus::Purchased,
            ])
            ->orderBy('created_at', 'asc')
            ->limit(5)
            ->get();
    }

    /**
     * Get recent shipments in transit.
     *
     * @return Collection<int, Shipment>
     */
    #[Computed]
    public function inTransitShipments(): Collection
    {
        return Shipment::query()
            ->with(['order'])
            ->whereIn('status', [
                ShipmentStatus::Posted,
                ShipmentStatus::InTransit,
                ShipmentStatus::OutForDelivery,
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();
    }

    /**
     * Get shipment statistics.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function stats(): array
    {
        $today     = now()->startOfDay();
        $thisWeek  = now()->startOfWeek();
        $thisMonth = now()->startOfMonth();

        return [
            'pending_labels' => Shipment::query()
                ->whereIn('status', [
                    ShipmentStatus::Pending,
                    ShipmentStatus::CartAdded,
                    ShipmentStatus::Purchased,
                ])
                ->count(),
            'in_transit' => Shipment::query()
                ->whereIn('status', [
                    ShipmentStatus::Posted,
                    ShipmentStatus::InTransit,
                    ShipmentStatus::OutForDelivery,
                ])
                ->count(),
            'delivered_today' => Shipment::query()
                ->where('status', ShipmentStatus::Delivered)
                ->where('updated_at', '>=', $today)
                ->count(),
            'delivered_this_week' => Shipment::query()
                ->where('status', ShipmentStatus::Delivered)
                ->where('updated_at', '>=', $thisWeek)
                ->count(),
            'delivered_this_month' => Shipment::query()
                ->where('status', ShipmentStatus::Delivered)
                ->where('updated_at', '>=', $thisMonth)
                ->count(),
            'problems' => Shipment::query()
                ->where('status', ShipmentStatus::Returned)
                ->count(),
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.shipping-dashboard-widget', [
            'pendingShipments'   => $this->pendingShipments,
            'inTransitShipments' => $this->inTransitShipments,
            'stats'              => $this->stats,
        ]);
    }
}
