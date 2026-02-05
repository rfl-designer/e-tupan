<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Dashboard;

use App\Domain\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class RecentOrders extends Component
{
    public function __construct(
        private readonly DashboardService $dashboardService = new DashboardService(),
    ) {
    }

    #[Computed]
    public function orders(): Collection
    {
        return $this->dashboardService->getRecentOrders(5);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.recent-orders');
    }
}
