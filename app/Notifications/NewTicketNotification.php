<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewTicketNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $priorityLabel = $this->ticket->priority->label();
        $adminUrl = route('tickets.queue');

        return (new MailMessage)
            ->subject("New Support Ticket: {$this->ticket->reference_number} - {$this->ticket->subject}")
            ->greeting('New Support Ticket')
            ->line("**Reference Number:** {$this->ticket->reference_number}")
            ->line("A new support ticket has been submitted by {$this->ticket->user->name}.")
            ->line("**Subject:** {$this->ticket->subject}")
            ->line("**Priority:** {$priorityLabel}")
            ->action('View Tickets', $adminUrl)
            ->line('Please review and respond to this ticket.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->ticket->id,
            'subject' => $this->ticket->subject,
        ];
    }
}
