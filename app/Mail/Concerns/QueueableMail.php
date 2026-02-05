<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;

/**
 * Trait for queueable mailables with standardized retry configuration.
 *
 * Provides:
 * - Automatic queue assignment to 'emails' queue
 * - 3 retry attempts
 * - Exponential backoff: 10s, 60s, 300s
 * - 30 second timeout
 */
trait QueueableMail
{
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function initializeQueueableMail(): void
    {
        $this->onQueue('emails');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }
}
