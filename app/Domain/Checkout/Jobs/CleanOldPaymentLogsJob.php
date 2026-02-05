<?php

declare(strict_types = 1);

namespace App\Domain\Checkout\Jobs;

use App\Domain\Checkout\Services\PaymentLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Log;

class CleanOldPaymentLogsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        private readonly ?int $days = null,
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(PaymentLogService $paymentLogService): void
    {
        $days = $this->days ?? (int) config('payment.logging.retention_days', 90);

        $deleted = $paymentLogService->cleanup($days);

        Log::info('Payment logs cleanup completed', [
            'deleted_count'  => $deleted,
            'retention_days' => $days,
        ]);
    }
}
