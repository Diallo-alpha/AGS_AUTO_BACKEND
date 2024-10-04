<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Paiement;
use App\Models\Formation;
use Illuminate\Support\Facades\Log;

class PaymentSuccessNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $payment;
    protected $formation;

    /**
     * Create a new notification instance.
     */
    public function __construct(Paiement $payment, Formation $formation)
    {
        $this->payment = $payment;
        $this->formation = $formation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Vérifier si le paiement est validé avant d'envoyer la notification
        if (!$this->payment->validation || $this->payment->status_paiement !== 'payé') {
            Log::error('Tentative d\'envoi d\'une notification pour un paiement non validé', [
                'payment_id' => $this->payment->id,
                'status' => $this->payment->status_paiement,
                'validation' => $this->payment->validation
            ]);
            return []; // Ne pas envoyer de notification si le paiement n'est pas validé
        }

        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->subject('Confirmation de paiement pour votre formation')
                    ->line('Votre paiement a été traité avec succès.')
                    ->line('Formation: ' . $this->formation->nom_formation)
                    ->line('Montant: ' . $this->payment->montant)
                    ->action('Voir les détails', url('https://admirable-macaron-cbfcb1.netlify.app/detail-formation/' . $this->formation->id))
                    ->line('Merci d\'utiliser notre plateforme!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'payment_id' => $this->payment->id,
            'formation_id' => $this->formation->id,
            'montant' => $this->payment->montant,
        ];
    }

    /**
     * Determine which queues should be used for each notification channel.
     *
     * @return array
     */
    public function viaQueues()
    {
        return [
            'mail' => 'emails',
            'database' => 'default',
        ];
    }

    /**
     * Get the number of seconds before the job should timeout.
     *
     * @return int
     */
    public function retryUntil()
    {
        return now()->addMinutes(10);
    }
}
