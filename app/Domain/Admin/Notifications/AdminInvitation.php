<?php declare(strict_types = 1);

declare(strict_types=1);

namespace App\Domain\Admin\Notifications;

use App\Mail\Concerns\QueueableNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminInvitation extends Notification implements ShouldQueue
{
    use QueueableNotification;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
    ) {
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
        $url = $this->resetUrl($notifiable);

        return (new MailMessage())
            ->subject(__('Convite para Administrador - :app', ['app' => config('app.name')]))
            ->greeting(__('Olá, :name!', ['name' => $notifiable->name]))
            ->line(__('Você foi convidado para ser administrador do painel de controle.'))
            ->line(__('Clique no botão abaixo para definir sua senha e acessar o sistema.'))
            ->action(__('Definir Senha'), $url)
            ->line(__('Este link expira em :count minutos.', ['count' => config('auth.passwords.admins.expire')]))
            ->line(__('Se você não esperava este convite, nenhuma ação é necessária.'))
            ->salutation(__('Atenciosamente, :app', ['app' => config('app.name')]));
    }

    /**
     * Get the password reset URL for the given notifiable.
     */
    protected function resetUrl(object $notifiable): string
    {
        return url(route('admin.password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'token' => $this->token,
        ];
    }
}
