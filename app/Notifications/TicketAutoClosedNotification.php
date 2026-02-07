<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAutoClosedNotification extends Notification implements ShouldQueue
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
        $url = url('/tickets/'.$this->ticket->id);

        return (new MailMessage)
            ->subject("Ticket {$this->ticket->reference_number} Closed: {$this->ticket->subject}")
            ->greeting("Hello {$notifiable->name},")
            ->line("**Reference Number:** {$this->ticket->reference_number}")
            ->line("We hope this message finds you well. We're writing to let you know that your support ticket has been automatically closed.")
            ->line("**Ticket:** {$this->ticket->subject}")
            ->line('Since we haven\'t heard back from you for 7 days after our last response, we\'ve marked this ticket as resolved. This helps us keep our support system organized and ensures we can focus on helping customers who currently need assistance.')
            ->line('If you still have questions or concerns about this issue, please don\'t hesitate to create a new ticket.')
            ->action('View Ticket', $url)
            ->line('Thank you for your understanding and for using our support system. We appreciate your patience!');
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
