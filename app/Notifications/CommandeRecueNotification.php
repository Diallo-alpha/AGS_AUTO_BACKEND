<?php

namespace App\Notifications;

use App\Models\Commande;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class CommandeRecueNotification extends Notification 
{
    use Queueable;

    protected $commande;

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Nouvelle commande reçue')
            ->view('emails.commande-recue', ['commande' => $this->commande]);
    }

    // Méthode pour les notifications basées sur la base de données
    public function toArray($notifiable)
    {
        return [
            'commande_id' => $this->commande->id,
            'user_id' => $this->commande->user_id,
            'somme' => $this->commande->somme,
            'status' => $this->commande->status,
            'date' => $this->commande->date,
        ];
    }
}
