<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class CommandeLivreeNotification extends Notification
{
    use Queueable;

    protected $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
        Log::info('CommandeLivreeNotification initialisée pour la commande: ' . $commande->id);
        Log::info('Données de la commande:', [
            'commande_id' => $commande->id,
            'user_id' => $commande->user_id,
            'status' => $commande->status
        ]);
    }

    public function via($notifiable)
    {
        Log::info('Méthode via() appelée pour l\'utilisateur:', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email
        ]);
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        Log::info('Préparation de l\'email pour l\'utilisateur:', [
            'user_id' => $notifiable->id,
            'email' => $notifiable->email,
            'commande_id' => $this->commande->id
        ]);

        try {
            if (!view()->exists('emails.commande-livree')) {
                Log::error('La vue emails.commande-livree n\'existe pas!');
                throw new \Exception('Template d\'email manquant');
            }

            return (new MailMessage)
                ->subject('Votre commande a été livrée')
                ->view(
                    'emails.commande-livree',
                    [
                        'commande' => $this->commande,
                        'user' => $notifiable
                    ]
                );
        } catch (\Exception $e) {
            Log::error('Erreur lors de la création du mail:', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'commande_id' => $this->commande->id,
            'status' => 'livrée',
            'date_livraison' => now()->format('Y-m-d H:i:s'),
            'user_id' => $this->commande->user_id,
            'message' => 'Votre commande a été livrée avec succès',
            // Vous pouvez ajouter d'autres informations pertinentes ici
        ];
    }
}
