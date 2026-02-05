<?php

declare(strict_types=1);

namespace App\Domain\Admin\Listeners;

use App\Domain\Admin\Enums\EmailLogStatus;
use App\Domain\Admin\Models\EmailLog;
use App\Domain\Admin\Models\StoreSetting;
use Illuminate\Notifications\Events\NotificationSent;

class LogSentNotification
{
    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'mail') {
            return;
        }

        $recipient = $this->extractRecipientEmail($event->notifiable);

        if ($recipient === null) {
            return;
        }

        $subject = $this->extractSubject($event);

        EmailLog::create([
            'recipient' => $recipient,
            'subject' => $subject,
            'mailable_class' => $event->notification::class,
            'status' => EmailLogStatus::Sent,
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
     * Extract the subject from the notification response.
     */
    private function extractSubject(NotificationSent $event): string
    {
        $response = $event->response;

        if (is_object($response) && method_exists($response, 'getOriginalMessage')) {
            $message = $response->getOriginalMessage();

            if (method_exists($message, 'getSubject')) {
                return $message->getSubject() ?? 'N/A';
            }
        }

        return $event->notification::class;
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
