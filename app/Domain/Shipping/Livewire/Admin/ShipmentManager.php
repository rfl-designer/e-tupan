<?php

declare(strict_types = 1);

namespace App\Domain\Shipping\Livewire\Admin;

use App\Domain\Shipping\Contracts\LabelGeneratorInterface;
use App\Domain\Shipping\Enums\ShipmentStatus;
use App\Domain\Shipping\Jobs\{BatchGenerateLabelsJob, GenerateShipmentLabelJob};
use App\Domain\Shipping\Models\Shipment;
use App\Domain\Shipping\Services\ShipmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Livewire\Attributes\{Computed, Url};
use Livewire\{Component, WithPagination};

class ShipmentManager extends Component
{
    use WithPagination;

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $status = '';

    #[Url(except: '')]
    public string $carrier = '';

    #[Url(except: 'created_at')]
    public string $sortBy = 'created_at';

    #[Url(except: 'desc')]
    public string $sortDirection = 'desc';

    public bool $showFilters = false;

    /**
     * @var array<string>
     */
    public array $selected = [];

    public bool $selectAll = false;

    public bool $confirmCancelModal = false;

    public ?string $cancellingShipmentId = null;

    /**
     * Update the search query.
     */
    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Update status filter.
     */
    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    /**
     * Update carrier filter.
     */
    public function updatedCarrier(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle select all on current page.
     */
    public function updatedSelectAll(): void
    {
        if ($this->selectAll) {
            $this->selected = $this->shipments->pluck('id')->toArray();
        } else {
            $this->selected = [];
        }
    }

    /**
     * Sort by column.
     */
    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy        = $column;
            $this->sortDirection = 'desc';
        }
    }

    /**
     * Clear all filters.
     */
    public function clearFilters(): void
    {
        $this->search  = '';
        $this->status  = '';
        $this->carrier = '';
        $this->resetPage();
    }

    /**
     * Generate label for a single shipment.
     */
    public function generateLabel(string $shipmentId): void
    {
        $shipment = Shipment::query()->find($shipmentId);

        if (!$shipment) {
            $this->dispatch('notify', type: 'error', message: 'Envio nao encontrado.');

            return;
        }

        if (!$shipment->canGenerateLabel()) {
            $this->dispatch('notify', type: 'error', message: 'Este envio nao pode gerar etiqueta no status atual.');

            return;
        }

        GenerateShipmentLabelJob::dispatch($shipment);

        $this->dispatch('notify', type: 'success', message: 'Geracao de etiqueta iniciada. Voce sera notificado quando concluir.');
    }

    /**
     * Generate labels for selected shipments.
     */
    public function generateSelectedLabels(): void
    {
        if (empty($this->selected)) {
            $this->dispatch('notify', type: 'warning', message: 'Selecione ao menos um envio.');

            return;
        }

        BatchGenerateLabelsJob::dispatch(collect($this->selected));

        $count           = count($this->selected);
        $this->selected  = [];
        $this->selectAll = false;

        $this->dispatch('notify', type: 'success', message: "Geracao de {$count} etiquetas iniciada em lote.");
    }

    /**
     * Print label for a shipment.
     */
    public function printLabel(string $shipmentId): void
    {
        $shipment = Shipment::query()->find($shipmentId);

        if (!$shipment || !$shipment->label_url) {
            $this->dispatch('notify', type: 'error', message: 'Etiqueta nao disponivel.');

            return;
        }

        // Get printable URL from provider
        $labelGenerator = app(LabelGeneratorInterface::class);
        $result         = $labelGenerator->printLabel($shipment->shipment_id);

        if (!$result->success) {
            $this->dispatch('notify', type: 'error', message: $result->errorMessage ?? 'Erro ao gerar etiqueta para impressao.');

            return;
        }

        // Open the label URL in a new tab
        $this->dispatch('open-url', url: $result->labelUrl);
    }

    /**
     * Open cancel confirmation modal.
     */
    public function confirmCancel(string $shipmentId): void
    {
        $this->cancellingShipmentId = $shipmentId;
        $this->confirmCancelModal   = true;
    }

    /**
     * Cancel shipment.
     */
    public function cancelShipment(): void
    {
        if (!$this->cancellingShipmentId) {
            return;
        }

        $shipment = Shipment::query()->find($this->cancellingShipmentId);

        if (!$shipment) {
            $this->dispatch('notify', type: 'error', message: 'Envio nao encontrado.');
            $this->closeCancelModal();

            return;
        }

        if (!$shipment->canBeCancelled()) {
            $this->dispatch('notify', type: 'error', message: 'Este envio nao pode ser cancelado.');
            $this->closeCancelModal();

            return;
        }

        // Cancel in ME if has shipment_id
        if ($shipment->shipment_id) {
            $labelGenerator = app(LabelGeneratorInterface::class);
            $cancelled      = $labelGenerator->cancelShipment($shipment->shipment_id);

            if (!$cancelled) {
                $this->dispatch('notify', type: 'error', message: 'Erro ao cancelar envio na transportadora.');
                $this->closeCancelModal();

                return;
            }
        }

        $shipment->markAsCancelled();

        $this->closeCancelModal();
        $this->dispatch('notify', type: 'success', message: 'Envio cancelado com sucesso.');
    }

    /**
     * Close cancel modal.
     */
    public function closeCancelModal(): void
    {
        $this->confirmCancelModal   = false;
        $this->cancellingShipmentId = null;
    }

    /**
     * Mark shipment as posted.
     */
    public function markAsPosted(string $shipmentId): void
    {
        $shipment = Shipment::query()->find($shipmentId);

        if (!$shipment) {
            $this->dispatch('notify', type: 'error', message: 'Envio nao encontrado.');

            return;
        }

        if ($shipment->status !== ShipmentStatus::Generated) {
            $this->dispatch('notify', type: 'error', message: 'Envio deve estar com etiqueta gerada para ser postado.');

            return;
        }

        $shipment->markAsPosted();

        $this->dispatch('notify', type: 'success', message: 'Envio marcado como postado.');
    }

    /**
     * Get shipments.
     */
    #[Computed]
    public function shipments(): LengthAwarePaginator
    {
        return Shipment::query()
            ->with(['order'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('tracking_number', 'like', "%{$this->search}%")
                        ->orWhere('recipient_name', 'like', "%{$this->search}%")
                        ->orWhereHas('order', function ($orderQuery) {
                            $orderQuery->where('order_number', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($this->status, function ($query) {
                $query->where('status', $this->status);
            })
            ->when($this->carrier, function ($query) {
                $query->where('carrier_code', $this->carrier);
            })
            ->orderBy($this->sortBy, $this->sortDirection)
            ->paginate(15);
    }

    /**
     * Get dashboard statistics.
     */
    #[Computed]
    public function stats(): array
    {
        return app(ShipmentService::class)->getDashboardStats();
    }

    /**
     * Get available carriers for filter.
     */
    #[Computed]
    public function carriers(): Collection
    {
        return Shipment::query()
            ->select('carrier_code', 'carrier_name')
            ->distinct()
            ->whereNotNull('carrier_code')
            ->orderBy('carrier_name')
            ->get();
    }

    /**
     * Get status options for filter.
     */
    #[Computed]
    public function statusOptions(): array
    {
        return [
            ''                                    => 'Todos',
            ShipmentStatus::Pending->value        => ShipmentStatus::Pending->label(),
            ShipmentStatus::CartAdded->value      => ShipmentStatus::CartAdded->label(),
            ShipmentStatus::Purchased->value      => ShipmentStatus::Purchased->label(),
            ShipmentStatus::Generated->value      => ShipmentStatus::Generated->label(),
            ShipmentStatus::Posted->value         => ShipmentStatus::Posted->label(),
            ShipmentStatus::InTransit->value      => ShipmentStatus::InTransit->label(),
            ShipmentStatus::OutForDelivery->value => ShipmentStatus::OutForDelivery->label(),
            ShipmentStatus::Delivered->value      => ShipmentStatus::Delivered->label(),
            ShipmentStatus::Cancelled->value      => ShipmentStatus::Cancelled->label(),
        ];
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.shipment-manager');
    }
}
