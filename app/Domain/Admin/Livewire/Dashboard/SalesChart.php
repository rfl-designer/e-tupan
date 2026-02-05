<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Dashboard;

use App\Domain\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SalesChart extends Component
{
    public int $days = 7;

    public function __construct(
        private readonly DashboardService $dashboardService = new DashboardService(),
    ) {
    }

    public function setDays(int $days): void
    {
        $this->days = $days;
    }

    #[Computed]
    public function chartData(): array
    {
        return $this->dashboardService->getSalesChart($this->days);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.sales-chart');
    }
}
