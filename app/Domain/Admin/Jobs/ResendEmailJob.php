<?php

declare(strict_types=1);

namespace App\Domain\Admin\Jobs;

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Models\EmailLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class ResendEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly EmailLog $emailLog,
    ) {
        $this->onQueue('emails');
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function handle(): void
    {
        if (! $this->emailLog->canBeResent()) {
            Log::warning("Cannot resend email log #{$this->emailLog->id}: not eligible for resend");

            return;
        }

        $newLog = EmailLog::create([
            'recipient' => $this->emailLog->recipient,
            'subject' => $this->emailLog->subject,
            'mailable_class' => $this->emailLog->mailable_class,
            'status' => EmailLogStatus::Pending,
            'driver' => $this->emailLog->driver,
            'resent_from_id' => $this->emailLog->id,
        ]);

        try {
            if (! class_exists($this->emailLog->mailable_class)) {
                throw new \RuntimeException("Mailable class {$this->emailLog->mailable_class} does not exist");
            }

            $mailable = app()->make($this->emailLog->mailable_class);

            Mail::to($this->emailLog->recipient)->send($mailable);

            $newLog->update(['status' => EmailLogStatus::Sent]);

            Log::info("Successfully resent email to {$this->emailLog->recipient} (original log #{$this->emailLog->id})");
        } catch (\Throwable $e) {
            $newLog->update([
                'status' => EmailLogStatus::Failed,
                'error_message' => $e->getMessage(),
            ]);

            Log::error("Failed to resend email log #{$this->emailLog->id}: {$e->getMessage()}");

            throw $e;
        }
    }
}
