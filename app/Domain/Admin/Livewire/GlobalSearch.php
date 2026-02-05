<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Services\GlobalSearchService;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    public string $query = '';

    public bool $isOpen = false;

    public function updatedQuery(): void
    {
        $this->isOpen = strlen($this->query) >= 2;
    }

    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->query  = '';
    }

    #[Computed]
    public function results(): array
    {
        if (strlen($this->query) < 2) {
            return [
                'orders'    => collect(),
                'products'  => collect(),
                'customers' => collect(),
            ];
        }

        return app(GlobalSearchService::class)->search($this->query);
    }

    #[Computed]
    public function hasResults(): bool
    {
        $results = $this->results;

        return $results['orders']->isNotEmpty()
            || $results['products']->isNotEmpty()
            || $results['customers']->isNotEmpty();
    }

    public function render(): View
    {
        return view('livewire.admin.global-search');
    }
}
