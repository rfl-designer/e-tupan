<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Enums\NotificationType;
use App\Domain\Admin\Models\AdminNotification;
use App\Domain\Admin\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Livewire\{Component, WithPagination};

class NotificationList extends Component
{
    use WithPagination;

    public string $filterType = '';

    public string $filterStatus = '';

    protected $queryString = [
        'filterType'   => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingFilterType(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function markAsRead(string $notificationId, NotificationService $service): void
    {
        $notification = AdminNotification::find($notificationId);

        if ($notification && $notification->admin_id === Auth::guard('admin')->id()) {
            $service->markAsRead($notification);
        }
    }

    public function markAllAsRead(NotificationService $service): void
    {
        $admin = Auth::guard('admin')->user();
        $service->markAllAsRead($admin);
    }

    public function getNotificationsProperty(): LengthAwarePaginator
    {
        return AdminNotification::query()
            ->forAdmin(Auth::guard('admin')->user())
            ->when($this->filterType, fn ($query) => $query->where('type', $this->filterType))
            ->when($this->filterStatus === 'read', fn ($query) => $query->read())
            ->when($this->filterStatus === 'unread', fn ($query) => $query->unread())
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function getTypesProperty(): array
    {
        return collect(NotificationType::cases())
            ->mapWithKeys(fn (NotificationType $type) => [$type->value => $type->label()])
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.notification-list');
    }
}
