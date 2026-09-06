<?php

namespace App\Notifications;

use App\Models\WeddingInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeddingInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        public WeddingInvitation $invitation,
        public string $token,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Undangan ruang pernikahan')
            ->greeting('Anda menerima undangan')
            ->line($this->invitation->inviter->name.' mengundang Anda ke ruang '.$this->invitation->wedding->name.'.')
            ->action('Lihat undangan', route('invitations.show', $this->token))
            ->line('Undangan ini berlaku selama tujuh hari dan hanya dapat digunakan oleh alamat email tujuan.');
    }
}
