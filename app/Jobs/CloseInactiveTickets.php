<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Notifications\TicketAutoClosedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CloseInactiveTickets implements ShouldQueue
{
    use Queueable;

    /**
     * Close open tickets that have had no activity for 7 days after the last admin reply.
     *
     * A ticket is considered inactive when:
     * - It has at least one admin reply older than 7 days
     * - No replies (from anyone) have been added in the last 7 days
     */
    public function handle(): void
    {
        Ticket::query()
            ->open()
            ->whereHas('replies', function ($query) {
                $query->where('is_from_admin', true)
                    ->where('created_at', '<=', now()->subDays(7));
            })
            ->whereDoesntHave('replies', function ($query) {
                $query->where('created_at', '>', now()->subDays(7));
            })
            ->with(['user', 'replies' => fn ($q) => $q->where('is_from_admin', true)->latest()->limit(1)])
            ->chunk(100, function ($tickets) {
                foreach ($tickets as $ticket) {
                    $this->closeTicket($ticket);
                }
            });
    }

    /**
     * Close a ticket and send notification.
     */
    protected function closeTicket(Ticket $ticket): void
    {
        $lastAdminReply = $ticket->replies->first();

        // Add an automated reply explaining why the ticket was closed
        TicketReply::create([
            'ticket_id' => $ticket->id,
            'user_id' => $lastAdminReply->user_id ?? null,
            'body' => "This ticket has been automatically closed due to inactivity. Since we haven't heard back from you for 7 days after our last response, we've marked this ticket as resolved.\n\nIf you still have questions or concerns about this issue, please don't hesitate to create a new ticket.",
            'is_from_admin' => true,
        ]);

        // Close the ticket
        $ticket->close();

        // Send notification to the customer
        if ($ticket->user) {
            $ticket->user->notify(new TicketAutoClosedNotification($ticket));
        }
    }
}
