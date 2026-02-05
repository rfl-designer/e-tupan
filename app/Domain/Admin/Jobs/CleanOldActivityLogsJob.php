<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Jobs;

use App\Domain\Admin\Models\ActivityLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class CleanOldActivityLogsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $retentionDays = 90,
    ) {
    }

    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->retentionDays);

        $deleted = ActivityLog::query()
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned {$deleted} activity log entries older than {$this->retentionDays} days");
    }
}
