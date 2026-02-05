<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire\Customers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\{Component, WithPagination};

class CustomerList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'sort')]
    public string $sortField = 'created_at';

    #[Url(as: 'dir')]
    public string $sortDirection = 'desc';

    public int $perPage = 15;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField     = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * @return LengthAwarePaginator<User>
     */
    public function getCustomersProperty(): LengthAwarePaginator
    {
        return User::query()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function render(): View
    {
        return view('livewire.admin.customers.customer-list', [
            'customers' => $this->customers,
        ]);
    }
}
