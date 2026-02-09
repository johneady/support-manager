<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketClosedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Ticket $ticket,
        public string $closedByName
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = url('/tickets/'.$this->ticket->id);

        return (new MailMessage)
            ->subject("Ticket {$this->ticket->reference_number} Closed: {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**Reference Number:** {$this->ticket->reference_number}")
            ->line("Your support ticket was closed by {$this->closedByName}.")
            ->line("**Ticket:** {$this->ticket->subject}")
            ->line('If you have any questions or need further assistance, please create a new ticket.')
            ->action('View Ticket', $url)
            ->line('Thank you for using our support system.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
            'closed_by' => $this->closedByName,
        ];
    }
}
