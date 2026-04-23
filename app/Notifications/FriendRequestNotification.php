<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FriendRequestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromName,
        public int $fromElo,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("♟ {$this->fromName} vēlas būt tavs draugs")
            ->greeting("Sveiks, {$notifiable->name}!")
            ->line("{$this->fromName} (ELO {$this->fromElo}) nosūtīja tev drauga pieprasījumu.")
            ->action('Apskatīt pieprasījumu', url('/multiplayer'))
            ->line('Pieņem pieprasījumu, lai varētu izaicināt viens otru uz spēlēm!')
            ->salutation('Šaha Analīzes Platforma');
    }
}
