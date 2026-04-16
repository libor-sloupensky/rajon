<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetHesla extends Notification
{
    public function __construct(public string $token)
    {
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return (new MailMessage)
            ->subject('Obnovení hesla — Rajón')
            ->greeting('Dobrý den!')
            ->line('Tento e-mail jste obdrželi, protože jsme dostali žádost o obnovení hesla k vašemu účtu.')
            ->action('Obnovit heslo', $url)
            ->line('Platnost tohoto odkazu vyprší za 60 minut.')
            ->line('Pokud jste o obnovení hesla nežádali, tento e-mail můžete ignorovat.')
            ->salutation('S pozdravem, tým Rajón');
    }
}
