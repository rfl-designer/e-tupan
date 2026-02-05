<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Livewire;

use App\Domain\Admin\Enums\ActivityAction;
use App\Domain\Admin\Models\{ActivityLog, Admin};
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\{Component, WithPagination};

class ActivityLogList extends Component
{
    use WithPagination;

    public string $search = '';

    public string $filterAdmin = '';

    public string $filterAction = '';

    public string $filterDateFrom = '';

    public string $filterDateTo = '';

    protected $queryString = [
        'search'         => ['except' => ''],
        'filterAdmin'    => ['except' => ''],
        'filterAction'   => ['except' => ''],
        'filterDateFrom' => ['except' => ''],
        'filterDateTo'   => ['except' => ''],
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAdmin(): void
    {
        $this->resetPage();
    }

    public function updatingFilterAction(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterAdmin', 'filterAction', 'filterDateFrom', 'filterDateTo']);
        $this->resetPage();
    }

    public function getLogsProperty(): LengthAwarePaginator
    {
        return ActivityLog::query()
            ->with('admin:id,name,email')
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhere('subject_type', 'like', "%{$this->search}%")
                        ->orWhere('admin_name', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterAdmin, fn ($query) => $query->where('admin_id', $this->filterAdmin))
            ->when($this->filterAction, fn ($query) => $query->where('action', $this->filterAction))
            ->when($this->filterDateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    public function getAdminsProperty(): array
    {
        return Admin::query()
            ->orderBy('name')
            ->pluck('name', 'id')
            ->toArray();
    }

    public function getActionsProperty(): array
    {
        return collect(ActivityAction::cases())
            ->mapWithKeys(fn (ActivityAction $action) => [$action->value => $action->label()])
            ->toArray();
    }

    public function export(): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $logs = ActivityLog::query()
            ->with('admin:id,name')
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('description', 'like', "%{$this->search}%")
                        ->orWhere('subject_type', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterAdmin, fn ($query) => $query->where('admin_id', $this->filterAdmin))
            ->when($this->filterAction, fn ($query) => $query->where('action', $this->filterAction))
            ->when($this->filterDateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderByDesc('created_at')
            ->get();

        $filename = 'activity_logs_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($logs): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Data/Hora', 'Admin', 'Acao', 'Descricao', 'Entidade', 'ID', 'IP']);

            foreach ($logs as $log) {
                fputcsv($handle, [
                    $log->created_at->format('d/m/Y H:i:s'),
                    $log->admin_name ?? 'Sistema',
                    $log->action->label(),
                    $log->description,
                    class_basename($log->subject_type),
                    $log->subject_id,
                    $log->ip_address ?? '-',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function render(): View
    {
        return view('livewire.admin.activity-log-list');
    }
}
