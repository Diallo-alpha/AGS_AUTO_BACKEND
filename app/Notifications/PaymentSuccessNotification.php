<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class PaymentSuccessNotification extends Notification implements ShouldQueue
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
        try {
            return (new MailMessage)
                        ->subject('Paiement réussi pour ' . $this->formation->nom_formation)
                        ->line('Votre paiement a été effectué avec succès.')
                        ->line('Formation : ' . $this->formation->nom_formation)
                        ->line('Montant payé : ' . $this->paiement->montant . ' ' . $this->paiement->currency)
                        ->action('Voir les détails', url('/https://admirable-macaron-cbfcb1.netlify.app/detail-formation/' . $this->formation->id))
                        ->line('Merci d\'avoir choisi notre plateforme!');
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du message de notification', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function toArray($notifiable)
    {
        return [
            'paiement_id' => $this->paiement->id,
            'formation_id' => $this->formation->id,
            'montant' => $this->paiement->montant,
            'currency' => $this->paiement->currency,
        ];
    }
}
