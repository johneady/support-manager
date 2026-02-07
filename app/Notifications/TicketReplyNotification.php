<?php

namespace App\Notifications;

use App\Models\TicketReply;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class TicketReplyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public TicketReply $reply) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ticket = $this->reply->ticket;
        $isFromAdmin = $this->reply->is_from_admin;
        $replyPreview = Str::limit($this->reply->body, 200);

        if ($isFromAdmin) {
            $url = url('/tickets/'.$ticket->id);

            return (new MailMessage)
                ->subject("Reply to Ticket {$ticket->reference_number}: {$ticket->subject}")
                ->greeting("Hello {$notifiable->name},")
                ->line("**Reference Number:** {$ticket->reference_number}")
                ->line('You have received a reply to your support ticket.')
                ->line("**Ticket:** {$ticket->subject}")
                ->line('**Reply:**')
                ->line($replyPreview)
                ->action('View Ticket', $url)
                ->line('Thank you for using our support system.');
        }

        $url = url('/admin/tickets/'.$ticket->id);

        return (new MailMessage)
            ->subject("Customer Reply to Ticket {$ticket->reference_number}: {$ticket->subject}")
            ->greeting('New Customer Reply')
            ->line("**Reference Number:** {$ticket->reference_number}")
            ->line("{$this->reply->user->name} has replied to a support ticket.")
            ->line("**Ticket:** {$ticket->subject}")
            ->line('**Reply:**')
            ->line($replyPreview)
            ->action('View Ticket', $url)
            ->line('Please review and respond to this reply.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'ticket_id' => $this->reply->ticket_id,
            'reply_id' => $this->reply->id,
        ];
    }
}
