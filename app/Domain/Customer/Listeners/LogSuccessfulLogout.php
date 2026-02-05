<?php declare(strict_types = 1);

namespace App\Domain\Customer\Listeners;

use App\Domain\Customer\Models\AuthLog;
use Illuminate\Auth\Events\Logout;

class LogSuccessfulLogout
{
    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        try {
            if ($event->user === null) {
                return;
            }

            /** @var \App\Models\User $user */
            $user = $event->user;

            AuthLog::create([
                'authenticatable_type' => get_class($user),
                'authenticatable_id'   => $user->getAuthIdentifier(),
                'email'                => $user->email,
                'event'                => 'logout',
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
