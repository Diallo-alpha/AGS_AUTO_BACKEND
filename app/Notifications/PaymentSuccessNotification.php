<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use App\Models\Paiement;
use App\Models\Formation;

class PaymentSuccessNotification extends Notification
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
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
                    ->line('Votre paiement a été traité avec succès.')
                    ->line('Formation: ' . $this->formation->nom_formation)
                    ->line('Montant: ' . $this->payment->montant)
                    ->action('Voir les détails', url('/https://admirable-macaron-cbfcb1.netlify.app/detail-formation/' . $this->formation->id))
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
}
