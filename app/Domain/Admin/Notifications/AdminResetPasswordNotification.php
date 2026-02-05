<?php declare(strict_types = 1);

declare(strict_types=1);

namespace App\Domain\Admin\Notifications;

use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminResetPasswordNotification extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(public string $token)
    {
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
        $url = url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->email,
        ], false));

        return (new MailMessage())
            ->subject('Redefinição de Senha - Painel Administrativo')
            ->greeting('Olá, ' . $notifiable->name . '!')
            ->line('Você está recebendo este email porque recebemos uma solicitação de redefinição de senha para sua conta administrativa.')
            ->action('Redefinir Senha', $url)
            ->line('Este link expira em 30 minutos.')
            ->line('Se você não solicitou a redefinição de senha, nenhuma ação é necessária.')
            ->salutation('Atenciosamente, ' . config('app.name'));
    }
}
