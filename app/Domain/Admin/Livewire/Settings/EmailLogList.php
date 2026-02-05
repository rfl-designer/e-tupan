<?php

declare(strict_types=1);

namespace App\Domain\Admin\Livewire\Settings;

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Jobs\ResendEmailJob;
use App\Domain\Admin\Models\EmailLog;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class EmailLogList extends Component
{
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'status')]
    public string $filterStatus = '';

    #[Url(as: 'mailable')]
    public string $filterMailableClass = '';

    #[Url(as: 'from')]
    public string $filterDateFrom = '';

    #[Url(as: 'to')]
    public string $filterDateTo = '';

    public bool $showDetailsModal = false;

    public ?EmailLog $selectedLog = null;

    public bool $showResendModal = false;

    public ?EmailLog $logToResend = null;

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterStatus', 'filterMailableClass', 'filterDateFrom', 'filterDateTo'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'filterStatus', 'filterMailableClass', 'filterDateFrom', 'filterDateTo']);
        $this->resetPage();
    }

    public function showDetails(int $logId): void
    {
        $this->selectedLog = EmailLog::find($logId);
        $this->showDetailsModal = true;
    }

    public function closeDetails(): void
    {
        $this->showDetailsModal = false;
        $this->selectedLog = null;
    }

    public function confirmResend(int $logId): void
    {
        $this->logToResend = EmailLog::find($logId);
        $this->showResendModal = true;
    }

    public function cancelResend(): void
    {
        $this->showResendModal = false;
        $this->logToResend = null;
    }

    public function resend(): void
    {
        if (! $this->logToResend || ! $this->logToResend->canBeResent()) {
            $this->cancelResend();

            return;
        }

        ResendEmailJob::dispatch($this->logToResend);

        $this->cancelResend();
        $this->dispatch('resend-queued');
    }

    /**
     * @return LengthAwarePaginator<int, EmailLog>
     */
    public function getLogsProperty(): LengthAwarePaginator
    {
        return EmailLog::query()
            ->when($this->search, function ($query): void {
                $query->where(function ($q): void {
                    $q->where('recipient', 'like', "%{$this->search}%")
                        ->orWhere('subject', 'like', "%{$this->search}%");
                });
            })
            ->when($this->filterStatus, fn ($query) => $query->where('status', $this->filterStatus))
            ->when($this->filterMailableClass, fn ($query) => $query->where('mailable_class', $this->filterMailableClass))
            ->when($this->filterDateFrom, fn ($query) => $query->whereDate('created_at', '>=', $this->filterDateFrom))
            ->when($this->filterDateTo, fn ($query) => $query->whereDate('created_at', '<=', $this->filterDateTo))
            ->orderByDesc('created_at')
            ->paginate(20);
    }

    /**
     * @return array<string, string>
     */
    public function getStatusesProperty(): array
    {
        return collect(EmailLogStatus::cases())
            ->mapWithKeys(fn (EmailLogStatus $status) => [$status->value => $status->label()])
            ->toArray();
    }

    /**
     * @return array<int, string>
     */
    public function getMailableClassesProperty(): array
    {
        return EmailLog::query()
            ->distinct()
            ->pluck('mailable_class')
            ->toArray();
    }

    public function render(): View
    {
        return view('livewire.admin.settings.email-log-list', [
            'logs' => $this->getLogsProperty(),
            'statuses' => $this->getStatusesProperty(),
            'mailableClasses' => $this->getMailableClassesProperty(),
        ]);
    }
}
