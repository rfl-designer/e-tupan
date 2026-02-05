<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use Illuminate\Bus\Queueable;

/**
 * Trait for queueable notifications with standardized retry configuration.
 *
 * Provides:
 * - Automatic queue assignment to 'emails' queue
 * - 3 retry attempts
 * - Exponential backoff: 10s, 60s, 300s
 * - 30 second timeout
 */
trait QueueableNotification
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function initializeQueueableNotification(): void
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
