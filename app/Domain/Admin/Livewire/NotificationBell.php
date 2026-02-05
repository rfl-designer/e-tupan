<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Models\AdminNotification;
use App\Domain\Admin\Services\NotificationService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Computed;
use Livewire\Component;

class NotificationBell extends Component
{
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

    #[Computed]
    public function unreadCount(): int
    {
        return AdminNotification::query()
            ->forAdmin(Auth::guard('admin')->user())
            ->unread()
            ->count();
    }

    #[Computed]
    public function notifications(): Collection
    {
        return AdminNotification::query()
            ->forAdmin(Auth::guard('admin')->user())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get();
    }

    public function render(): View
    {
        return view('livewire.admin.notification-bell');
    }
}
