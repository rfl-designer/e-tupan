<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Dashboard;

use App\Domain\Admin\Services\DashboardService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TopProducts extends Component
{
    public string $period = 'month';

    public function __construct(
        private readonly DashboardService $dashboardService = new DashboardService(),
    ) {
    }

    public function setPeriod(string $period): void
    {
        $this->period = $period;
    }

    #[Computed]
    public function products(): Collection
    {
        return $this->dashboardService->getTopProducts(5, $this->period);
    }

    public function render(): View
    {
        return view('livewire.admin.dashboard.top-products');
    }
}
