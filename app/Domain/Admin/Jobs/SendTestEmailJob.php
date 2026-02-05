<?php

declare(strict_types = 1);

namespace App\Domain\Admin\Jobs;

use App\Domain\Admin\Mail\TestEmail;
use App\Domain\Admin\Services\SettingsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\{InteractsWithQueue, SerializesModels};
use Illuminate\Support\Facades\Mail;

class SendTestEmailJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $email,
    ) {
    }

    public function handle(SettingsService $settingsService): void
    {
        $storeName = $settingsService->get('general.store_name')
            ?: $settingsService->get('email.sender_name')
            ?: config('app.name');

        Mail::to($this->email)->send(
            new TestEmail($storeName, now())
        );
    }
}
