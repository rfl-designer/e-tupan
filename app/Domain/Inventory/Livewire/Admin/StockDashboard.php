<?php

declare(strict_types = 1);

namespace App\Domain\Inventory\Livewire\Admin;

use App\Domain\Inventory\Models\{StockMovement, StockReservation};
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class StockDashboard extends Component
{
    /**
     * Get count of active reservations.
     */
    #[Computed]
    public function activeReservationsCount(): int
    {
        return StockReservation::query()
            ->active()
            ->count();
    }

    /**
     * Get total reserved quantity.
     */
    #[Computed]
    public function totalReservedQuantity(): int
    {
        return (int) StockReservation::query()
            ->active()
            ->sum('quantity');
    }

    /**
     * Get chart data for movements per day (last 7 days).
     *
     * @return array<int, array{date: string, count: int}>
     */
    #[Computed]
    public function movementsChartData(): array
    {
        $days = collect();

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            $days->put($date, [
                'date'  => Carbon::now()->subDays($i)->format('d/m'),
                'count' => 0,
            ]);
        }

        $movements = StockMovement::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', Carbon::now()->subDays(7)->startOfDay())
            ->groupBy('date')
            ->pluck('count', 'date');

        foreach ($movements as $date => $count) {
            if ($days->has($date)) {
                $days->put($date, [
                    'date'  => Carbon::parse($date)->format('d/m'),
                    'count' => $count,
                ]);
            }
        }

        return $days->values()->toArray();
    }

    /**
     * Render the component.
     */
    public function render(): View
    {
        return view('livewire.admin.stock-dashboard', [
            'activeReservationsCount' => $this->activeReservationsCount,
            'totalReservedQuantity'   => $this->totalReservedQuantity,
            'movementsChartData'      => $this->movementsChartData,
        ]);
    }
}
