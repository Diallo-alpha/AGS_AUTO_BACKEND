<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CommandeLivreeNotification extends Notification
{
    use Queueable;

    protected $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Votre commande a été livrée')
            ->view(
                'emails.commande-livree',
                ['commande' => $this->commande]
            );
    }
}
