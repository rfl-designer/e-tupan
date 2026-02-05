<?php declare(strict_types = 1);

declare(strict_types=1);

namespace App\Domain\Customer\Notifications;

use App\Mail\Concerns\QueueableNotification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public User $user,
    ) {
        $this->initializeQueueableNotification();
        $this->afterCommit();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Bem-vindo(a) à ' . config('app.name') . '!')
            ->greeting('Olá, ' . $this->user->name . '!')
            ->line('Obrigado por criar sua conta em nossa loja.')
            ->line('Agora você pode aproveitar todos os benefícios de ser nosso cliente.')
            ->action('Visitar a Loja', url('/'))
            ->line('Se tiver alguma dúvida, entre em contato conosco.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'user_id'   => $this->user->id,
            'user_name' => $this->user->name,
        ];
    }
}
