<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentSuccessNotification extends Notification
{
    use Queueable;

    protected $paiement;
    protected $formation;

    public function __construct($paiement, $formation)
    {
        $this->paiement = $paiement;
        $this->formation = $formation;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
                    ->line('Votre paiement a été effectué avec succès.')
                    ->line('Formation : ' . $this->formation->name)
                    ->line('Montant payé : ' . $this->paiement->montant)
                    ->action('Voir les détails', url('/formations/' . $this->formation->id))
                    ->line('Merci d\'avoir choisi notre plateforme!');
    }
}
