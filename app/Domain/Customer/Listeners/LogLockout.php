<?php declare(strict_types = 1);

namespace App\Domain\Customer\Listeners;

use App\Domain\Customer\Models\AuthLog;
use Illuminate\Auth\Events\Lockout;

class LogLockout
{
    /**
     * Handle the event.
     */
    public function handle(Lockout $event): void
    {
        try {
            /** @var string $email */
            $email = $event->request->input('email', 'unknown');

            AuthLog::create([
                'authenticatable_type' => null,
                'authenticatable_id'   => null,
                'email'                => $email,
                'event'                => 'lockout',
                'ip_address'           => $event->request->ip() ?? '0.0.0.0',
                'user_agent'           => $event->request->userAgent(),
                'metadata'             => [
                    'seconds_remaining' => $event->request->hasSession()
                        ? $event->request->session()->get('login.lockout_time', 0)
                        : 0,
                ],
            ]);
        } catch (\Exception $e) {
            report($e);
        }
    }
}
