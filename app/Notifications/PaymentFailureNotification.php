<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\DatabaseMessage;

class PaymentFailureNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $paiement;
    public $formation;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($paiement, $formation)
    {
        $this->paiement = $paiement;
        $this->formation = $formation;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Échec de votre paiement pour la formation ' . $this->formation->nom)
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Nous sommes désolés, mais votre paiement pour la formation ' . $this->formation->nom . ' a échoué.')
            ->line('Montant : ' . number_format($this->paiement->montant, 2) . ' ' . $this->paiement->devise)
            ->line('Date : ' . $this->paiement->created_at->format('d/m/Y H:i'))
            ->line('Si vous avez des questions ou souhaitez réessayer, n’hésitez pas à nous contacter.')
            ->line('Merci pour votre compréhension.');
    }

    /**
     * Get the array representation of the notification (for database).
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'formation_name' => $this->formation->nom,
            'amount' => number_format($this->paiement->montant, 2),
            'currency' => $this->paiement->devise,
            'payment_status' => 'échoué',
            'payment_date' => $this->paiement->created_at->format('d/m/Y H:i'),
        ];
    }
}
