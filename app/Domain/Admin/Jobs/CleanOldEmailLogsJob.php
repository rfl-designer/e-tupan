<?php

declare(strict_types=1);

namespace App\Domain\Admin\Jobs;

use App\Domain\Admin\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CleanOldEmailLogsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $retentionDays = 90,
    ) {}

    public function handle(): void
    {
        $cutoffDate = now()->subDays($this->retentionDays);

        $deleted = EmailLog::query()
            ->where('created_at', '<', $cutoffDate)
            ->delete();

        Log::info("Cleaned {$deleted} email log entries older than {$this->retentionDays} days");
    }
}
