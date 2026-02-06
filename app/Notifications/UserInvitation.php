<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitation extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public string $token,
        public string $inviterName,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("You're invited to join {$this->appName()}")
            ->greeting("Hello {$notifiable->name},")
            ->line("{$this->inviterName} has invited you to join {$this->appName()}.")
            ->action('Set Your Password', $this->invitationUrl())
            ->line("This link expires in {$this->expiryDays()} days and can only be used once.")
            ->salutation('If you did not request this invitation, please ignore this email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'invitation_token' => $this->token,
            'inviter_name' => $this->inviterName,
        ];
    }

    /**
     * Get the invitation URL.
     */
    protected function invitationUrl(): string
    {
        return route('invitation.accept', ['token' => $this->token]);
    }

    /**
     * Get the application name.
     */
    protected function appName(): string
    {
        return config('app.name');
    }

    /**
     * Get the number of days until expiration.
     */
    protected function expiryDays(): int
    {
        return config('fortify.invitation_token_expiration_days', 7);
    }
}
