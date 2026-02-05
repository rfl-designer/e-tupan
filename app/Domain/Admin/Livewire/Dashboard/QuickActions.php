<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Dashboard;

use App\Domain\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuickActions extends Component
{
    public function __construct(
        private readonly DashboardService $dashboardService = new DashboardService(),
    ) {
    }

    #[Computed]
    public function pendingOrdersCount(): int
    {
        return $this->dashboardService->getPendingOrdersCount();
    }

    #[Computed]
    public function lowStockCount(): int
    {
        return $this->dashboardService->getLowStockCount();
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.quick-actions');
    }
}
