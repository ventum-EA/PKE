<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GameInviteNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $fromName,
        public string $inviteUrl,
        public int $timeControl,
        public bool $rated,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $minutes = intdiv($this->timeControl, 60);
        $ratedLabel = $this->rated ? 'reitinga' : 'draudzīgu';

        return (new MailMessage)
            ->subject("♚ {$this->fromName} aicina tevi uz šaha spēli!")
            ->greeting("Sveiks, {$notifiable->name}!")
            ->line("{$this->fromName} tevi aicina uz {$ratedLabel} spēli ({$minutes} min).")
            ->action('Pievienoties spēlei', url($this->inviteUrl))
            ->line('Saite ir derīga kamēr pretinieks gaida.')
            ->salutation('Šaha Analīzes Platforma');
    }
}
