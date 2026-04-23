<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        public array $stats,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $s = $this->stats;
        $lines = [];

        if ($s['games_played'] ?? 0)  $lines[] = "Nospēlētas partijas: {$s['games_played']}";
        if ($s['games_won'] ?? 0)     $lines[] = "Uzvaras: {$s['games_won']}";
        if ($s['puzzles_solved'] ?? 0) $lines[] = "Atrisināti uzdevumi: {$s['puzzles_solved']}";
        if ($s['elo_change'] ?? 0) {
            $sign = $s['elo_change'] > 0 ? '+' : '';
            $lines[] = "ELO izmaiņa: {$sign}{$s['elo_change']} → {$s['current_elo']}";
        }
        if ($s['streak'] ?? 0)        $lines[] = "Dienas sērija: {$s['streak']} dienas";

        $mail = (new MailMessage)
            ->subject("♟ Tava nedēļas šaha atskaite")
            ->greeting("Sveiks, {$notifiable->name}!");

        if (empty($lines)) {
            $mail->line('Šonedēļ nebija aktivitātes. Atgriezies un nospēlē partiju!');
        } else {
            $mail->line('Šeit ir tava nedēļas atskaite:');
            foreach ($lines as $line) {
                $mail->line("• {$line}");
            }
        }

        $suggestion = $s['suggestion'] ?? 'Turpini trenēties ar dienas uzdevumiem!';
        $mail->line("Ieteikums: {$suggestion}");

        return $mail
            ->action('Atvērt platformu', url('/'))
            ->salutation('Šaha Analīzes Platforma');
    }
}
