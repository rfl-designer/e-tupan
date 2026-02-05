<?php

declare(strict_types=1);

namespace App\Domain\Admin\Listeners;

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Models\EmailLog;
use App\Domain\Admin\Models\StoreSetting;
use Illuminate\Notifications\Events\NotificationFailed;

class LogFailedNotification
{
    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $recipient = $this->extractRecipientEmail($event->notifiable);

        if ($recipient === null) {
            return;
        }

        $errorMessage = $this->extractErrorMessage($event);

        EmailLog::create([
            'recipient' => $recipient,
            'subject' => $event->notification::class,
            'mailable_class' => $event->notification::class,
            'status' => EmailLogStatus::Failed,
            'error_message' => $errorMessage,
            'driver' => $this->getCurrentDriver(),
        ]);
    }

    /**
     * Extract the recipient email from the notifiable.
     */
    private function extractRecipientEmail(mixed $notifiable): ?string
    {
        if (method_exists($notifiable, 'routeNotificationForMail')) {
            $route = $notifiable->routeNotificationForMail();

            if (is_string($route)) {
                return $route;
            }

            if (is_array($route)) {
                return array_key_first($route) ?? null;
            }
        }

        if (isset($notifiable->email)) {
            return $notifiable->email;
        }

        if (isset($notifiable->routes['mail'])) {
            return $notifiable->routes['mail'];
        }

        return null;
    }

    /**
     * Extract the error message from the event data.
     */
    private function extractErrorMessage(NotificationFailed $event): ?string
    {
        $data = $event->data;

        if (isset($data['exception']) && $data['exception'] instanceof \Throwable) {
            return $data['exception']->getMessage();
        }

        if (isset($data['message'])) {
            return (string) $data['message'];
        }

        return null;
    }

    /**
     * Get the current mail driver from store settings.
     */
    private function getCurrentDriver(): ?string
    {
        $driver = StoreSetting::get('email.driver');

        return is_string($driver) ? $driver : null;
    }
}
