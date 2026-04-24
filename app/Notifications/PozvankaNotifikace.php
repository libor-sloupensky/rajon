<?php

namespace App\Notifications;

use App\Models\Pozvanka;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PozvankaNotifikace extends Notification
{
    public function __construct(public Pozvanka $pozvanka)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $pozvalJmeno = $this->pozvanka->pozval
            ? $this->pozvanka->pozval->celejmeno()
            : 'Tým Rajón';

        $mail = (new MailMessage)
            ->subject('Pozvánka do Rajónu — katalog akcí pro WormUP')
            ->greeting('Dobrý den!')
            ->line("**{$pozvalJmeno}** vás zve do aplikace **Rajón** — katalog akcí a festivalů pro franšízanty WormUP.");

        if ($this->pozvanka->role === 'admin') {
            $mail->line('Budete mít **administrátorský přístup**.');
        }

        if ($this->pozvanka->region) {
            $mail->line("Přednastavený region: **{$this->pozvanka->region}**.");
        }

        $mail->line('Pro dokončení registrace klikněte na tlačítko níže:')
            ->action('Dokončit registraci', $this->pozvanka->url())
            ->line("Pozvánka je platná do **{$this->pozvanka->plati_do?->format('j. n. Y H:i')}**.")
            ->line('Pokud tuto pozvánku nečekáte, tento e-mail můžete ignorovat.')
            ->salutation('S pozdravem, tým Rajón');

        return $mail;
    }
}
