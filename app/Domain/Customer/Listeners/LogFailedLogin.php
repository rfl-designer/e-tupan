<?php declare(strict_types = 1);

namespace App\Domain\Customer\Listeners;

use App\Domain\Customer\Models\AuthLog;
use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        try {
            $email = $event->credentials['email'] ?? 'unknown';

            AuthLog::create([
                'authenticatable_type' => $event->user ? get_class($event->user) : null,
                'authenticatable_id'   => $event->user?->getAuthIdentifier(),
                'email'                => $email,
                'event'                => 'failed',
                'ip_address'           => request()->ip() ?? '0.0.0.0',
                'user_agent'           => request()->userAgent(),
                'metadata'             => [
                    'guard' => $event->guard,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
