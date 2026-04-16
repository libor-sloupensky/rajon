<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\URL;

class OvereniEmailu extends Notification
{
    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = $this->verificationUrl($notifiable);

        return (new MailMessage)
            ->subject('Vítejte na Rajónu — ověřte svůj e-mail')
            ->greeting('Vítejte na Rajónu!')
            ->line('Vytvořili jsme vám účet pro přístup ke katalogu akcí a festivalů.')
            ->line('Pro aktivaci účtu prosím ověřte svůj e-mail kliknutím na tlačítko níže:')
            ->action('Ověřit e-mail', $url)
            ->line('Odkaz je platný 60 minut.')
            ->line('Pokud jste si účet nezakládali, tento e-mail můžete ignorovat.')
            ->salutation('S pozdravem, tým Rajón');
    }

    protected function verificationUrl($notifiable): string
    {
        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes(Config::get('auth.verification.expire', 60)),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ]
        );
    }
}
