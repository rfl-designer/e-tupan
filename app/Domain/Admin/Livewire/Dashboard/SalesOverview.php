<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Dashboard;

use App\Domain\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class SalesOverview extends Component
{
    public function __construct(
        private readonly DashboardService $dashboardService = new DashboardService(),
    ) {
    }

    #[Computed]
    public function overview(): array
    {
        return $this->dashboardService->getSalesOverview();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.sales-overview');
    }
}
