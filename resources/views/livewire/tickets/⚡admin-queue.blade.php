<?php

use App\Models\Ticket;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public ?int $replyingToTicketId = null;

    /** @var array<int, bool> */
    public array $expandedTickets = [];

    #[Validate('required|string|min:5')]
    public string $replyBody = '';

    public ?string $newStatus = null;

    public ?string $newPriority = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['user', 'replies' => fn ($q) => $q->latest()->limit(1)])
            ->open()
            ->orderByRaw("
                CASE
                    WHEN id IN (
                        SELECT t.id FROM tickets t
                        LEFT JOIN ticket_replies tr ON tr.ticket_id = t.id
                        WHERE t.status = 'open'
                        GROUP BY t.id
                        HAVING COUNT(tr.id) = 0
                           OR MAX(CASE WHEN tr.is_from_admin = 0 THEN tr.id ELSE 0 END) = MAX(tr.id)
                    ) THEN 0
                    ELSE 1
                END ASC
            ")
            ->orderByRaw("
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                END ASC
            ")
            ->orderBy('created_at', 'asc')
            ->paginate(10);
    }

    #[Computed]
    public function needsResponseCount(): int
    {
        return Ticket::query()->open()->needsResponse()->count();
    }

    public function toggleTicket(int $ticketId): void
    {
        if (isset($this->expandedTickets[$ticketId])) {
            unset($this->expandedTickets[$ticketId]);
        } else {
            $this->expandedTickets[$ticketId] = true;
        }
    }

    public function isExpanded(int $ticketId): bool
    {
        return isset($this->expandedTickets[$ticketId]) || $this->replyingToTicketId === $ticketId;
    }

    public function startReply(int $ticketId): void
    {
        $ticket = Ticket::findOrFail($ticketId);

        $this->replyingToTicketId = $ticketId;
        $this->expandedTickets[$ticketId] = true;
        $this->replyBody = '';
        $this->newStatus = $ticket->status->value;
        $this->newPriority = $ticket->priority->value;
    }

    public function cancelReply(): void
    {
        $this->replyingToTicketId = null;
        $this->replyBody = '';
        $this->newStatus = null;
        $this->newPriority = null;
        $this->resetValidation();
    }

    public function submitReply(): void
    {
        $this->validate();

        $ticket = Ticket::findOrFail($this->replyingToTicketId);

        $reply = $ticket->replies()->create([
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
            'is_from_admin' => true,
        ]);

        $ticket->update([
            'status' => $this->newStatus,
            'priority' => $this->newPriority,
            'closed_at' => $this->newStatus === 'closed' ? now() : null,
        ]);

        $ticket->user->notify(new TicketReplyNotification($reply));

        $ticketId = $this->replyingToTicketId;
        $this->cancelReply();
        unset($this->expandedTickets[$ticketId]);

        unset($this->tickets);
        unset($this->needsResponseCount);

        session()->flash('success', 'Reply sent successfully.');
    }

    /**
     * @return Collection<int, \App\Models\TicketReply>
     */
    public function getTicketReplies(Ticket $ticket): Collection
    {
        return $ticket->replies()->with('user')->orderBy('created_at')->get();
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Stats --}}
    <div class="flex items-center gap-4">
        <div class="flex items-center gap-2">
            <flux:badge color="red" size="lg">{{ $this->needsResponseCount }}</flux:badge>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">tickets need a response</span>
        </div>
    </div>

    @if($this->tickets->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            <flux:icon.check-circle class="mx-auto h-12 w-12 text-green-500" />
            <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">There are no open tickets requiring attention.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->tickets as $ticket)
                <div wire:key="ticket-{{ $ticket->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
                    {{-- Ticket Header (Clickable) --}}
                    <button
                        wire:click="toggleTicket({{ $ticket->id }})"
                        class="w-full px-6 py-4 bg-zinc-50 dark:bg-zinc-800/50 hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors text-left"
                    >
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <flux:icon.chevron-right class="w-5 h-5 text-zinc-400 transition-transform {{ $this->isExpanded($ticket->id) ? 'rotate-90' : '' }}" />
                                <div>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white">
                                        {{ $ticket->subject }}
                                    </h3>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                                        #{{ $ticket->id }} by {{ $ticket->user->name }} ({{ $ticket->user->email }})
                                        &middot; {{ $ticket->created_at->diffForHumans() }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 flex-wrap ml-8 sm:ml-0">
                                @if($ticket->needsResponse())
                                    <flux:badge color="red" size="sm">Needs Response</flux:badge>
                                @endif
                                <flux:badge color="{{ $ticket->priority->color() }}" size="sm">
                                    {{ $ticket->priority->label() }}
                                </flux:badge>
                                <flux:badge color="{{ $ticket->status->color() }}" size="sm">
                                    {{ $ticket->status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    </button>

                    {{-- Ticket Content (Collapsible) --}}
                    @if($this->isExpanded($ticket->id))
                        <div class="px-6 py-4 space-y-4 border-t border-zinc-200 dark:border-zinc-700">
                            {{-- Original description --}}
                            <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800 p-4">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="font-medium text-sm text-zinc-900 dark:text-white">
                                        {{ $ticket->user->name }}
                                    </span>
                                    <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $ticket->created_at->diffForHumans() }}
                                    </span>
                                </div>
                                <div class="prose dark:prose-invert prose-sm max-w-none">
                                    {!! nl2br(e($ticket->description)) !!}
                                </div>
                            </div>

                            {{-- Replies --}}
                            @php
                                $replies = $this->getTicketReplies($ticket);
                            @endphp

                            @if($replies->isNotEmpty())
                                <div class="space-y-3">
                                    @foreach($replies as $reply)
                                        <div wire:key="reply-{{ $reply->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 {{ $reply->is_from_admin ? 'bg-blue-50 dark:bg-blue-900/20 ml-8' : 'bg-zinc-50 dark:bg-zinc-800 mr-8' }}">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="flex items-center gap-2">
                                                    <span class="font-medium text-sm {{ $reply->is_from_admin ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-white' }}">
                                                        {{ $reply->user->name }}
                                                    </span>
                                                    @if($reply->is_from_admin)
                                                        <flux:badge color="sky" size="sm">Admin</flux:badge>
                                                    @endif
                                                </div>
                                                <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                                    {{ $reply->created_at->diffForHumans() }}
                                                </span>
                                            </div>
                                            <div class="prose dark:prose-invert prose-sm max-w-none">
                                                {!! nl2br(e($reply->body)) !!}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Reply Form --}}
                            @if($replyingToTicketId === $ticket->id)
                                <div class="rounded-lg border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4 mt-4">
                                    <h4 class="text-sm font-semibold text-zinc-900 dark:text-white mb-4">Send Reply</h4>
                                    <form wire:submit="submitReply" class="space-y-4">
                                        <flux:field>
                                            <flux:label>Your Reply</flux:label>
                                            <flux:textarea
                                                wire:model="replyBody"
                                                placeholder="Type your response..."
                                                rows="4"
                                            />
                                            <flux:error name="replyBody" />
                                        </flux:field>

                                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                            <flux:field>
                                                <flux:label>Status</flux:label>
                                                <flux:select wire:model="newStatus">
                                                    <flux:select.option value="open">Open</flux:select.option>
                                                    <flux:select.option value="closed">Closed</flux:select.option>
                                                </flux:select>
                                            </flux:field>
                                            <flux:field>
                                                <flux:label>Priority</flux:label>
                                                <flux:select wire:model="newPriority">
                                                    <flux:select.option value="low">Low</flux:select.option>
                                                    <flux:select.option value="medium">Medium</flux:select.option>
                                                    <flux:select.option value="high">High</flux:select.option>
                                                </flux:select>
                                            </flux:field>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <flux:button type="submit" variant="primary">
                                                Send Reply
                                            </flux:button>
                                            <flux:button type="button" variant="ghost" wire:click="cancelReply">
                                                Cancel
                                            </flux:button>
                                        </div>
                                    </form>
                                </div>
                            @else
                                <div class="flex items-center gap-2 pt-2">
                                    <flux:button wire:click="startReply({{ $ticket->id }})" icon="chat-bubble-left-right" variant="primary">
                                        Reply
                                    </flux:button>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->tickets->links() }}
        </div>
    @endif
</div>
