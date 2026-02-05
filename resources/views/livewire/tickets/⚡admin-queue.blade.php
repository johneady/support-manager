<?php

use App\Enums\TicketCategory;
use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showEditModal = false;

    public bool $showCloseConfirmation = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $editingTicketId = null;

    #[Validate('required|string|min:5')]
    public string $replyBody = '';

    public ?string $newStatus = null;

    public ?string $newPriority = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['user', 'replies' => fn ($q) => $q->latest()->limit(1)])
            ->open()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('subject', 'like', '%'.$this->search.'%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%'.$this->search.'%')
                                ->orWhere('email', 'like', '%'.$this->search.'%');
                        });
                });
            })
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

    #[Computed]
    public function editingTicket(): ?Ticket
    {
        if ($this->editingTicketId === null) {
            return null;
        }

        return Ticket::with('replies.user', 'user')->find($this->editingTicketId);
    }

    public function openEditModal(Ticket $ticket): void
    {
        $this->editingTicketId = $ticket->id;
        $this->replyBody = '';
        $this->newStatus = $ticket->status->value;
        $this->newPriority = $ticket->priority->value;
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->showEditModal = true;
    }

    public function confirmClose(): void
    {
        $this->showCloseConfirmation = true;
    }

    public function cancelClose(): void
    {
        $this->showCloseConfirmation = false;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->showCloseConfirmation = false;
        $this->editingTicketId = null;
        $this->reset(['replyBody', 'newStatus', 'newPriority', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function submitReply(): void
    {
        $this->validate();

        $ticket = Ticket::findOrFail($this->editingTicketId);

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

        unset($this->tickets);
        unset($this->needsResponseCount);
        unset($this->editingTicket);

        $this->replyBody = '';
        $this->modalMessage = 'Reply sent successfully.';
        $this->modalMessageType = 'success';

        $this->closeEditModal();
        session()->flash('success', 'Reply sent successfully.');
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Search and Stats --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:badge color="red" size="lg">{{ $this->needsResponseCount }}</flux:badge>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">tickets need a response</span>
        </div>
        <div class="w-full sm:w-80">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search subject, name, or email..."
                icon="magnifying-glass"
            />
        </div>
    </div>

    @if($this->tickets->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if($search)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No tickets found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No tickets match your search "{{ $search }}".</p>
            @else
                <flux:icon.check-circle class="mx-auto h-12 w-12 text-green-500" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">There are no open tickets requiring attention.</p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:click="openEditModal({{ $ticket->id }})">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $ticket->id }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <flux:badge color="{{ $ticket->category->color() }}" size="sm">
                                    {{ $ticket->category->label() }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                <div>{{ $ticket->user->name }}</div>
                                <div class="text-xs">{{ $ticket->user->email }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2">
                                    @if($ticket->needsResponse())
                                        <flux:badge color="red" size="sm">Needs Response</flux:badge>
                                    @endif
                                    <flux:badge color="{{ $ticket->status->color() }}" size="sm">
                                        {{ $ticket->status->label() }}
                                    </flux:badge>
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <flux:badge color="{{ $ticket->priority->color() }}" size="sm">
                                    {{ $ticket->priority->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $this->tickets->links() }}
        </div>
    @endif

    {{-- Edit Ticket Modal --}}
    <flux:modal wire:model.self="showEditModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        @if($this->editingTicket)
            <div class="space-y-6">
                <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                    <div class="flex items-center gap-3">
                        <flux:icon.ticket class="size-6 text-blue-600 dark:text-blue-400" />
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                            Ticket #{{ $this->editingTicket->id }}
                        </flux:heading>
                    </div>
                    <flux:text class="mt-2 text-blue-700 dark:text-blue-300">
                        Submitted by {{ $this->editingTicket->user->name }} ({{ $this->editingTicket->user->email }})
                    </flux:text>
                </div>

                {{-- Modal Message --}}
                @if($modalMessage)
                    <flux:callout variant="{{ $modalMessageType }}" icon="{{ $modalMessageType === 'success' ? 'check-circle' : 'exclamation-circle' }}" dismissible>
                        {{ $modalMessage }}
                    </flux:callout>
                @endif

                {{-- Ticket Details --}}
                <div class="space-y-4 rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800">
                    <div>
                        <flux:label>Subject</flux:label>
                        <div class="mt-1 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                            {{ $this->editingTicket->subject }}
                        </div>
                    </div>

                    <div>
                        <flux:label>Description</flux:label>
                        <div class="mt-1 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 whitespace-pre-wrap">{{ $this->editingTicket->description }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <flux:label>Status</flux:label>
                            <div class="mt-1">
                                <flux:badge color="{{ $this->editingTicket->status->color() }}" size="sm">
                                    {{ $this->editingTicket->status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                        <div>
                            <flux:label>Priority</flux:label>
                            <div class="mt-1">
                                <flux:badge color="{{ $this->editingTicket->priority->color() }}" size="sm">
                                    {{ $this->editingTicket->priority->label() }}
                                </flux:badge>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conversation --}}
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100">Conversation</h3>

                    @php
                        $replies = $this->editingTicket->replies ?? collect();
                    @endphp

                    @if($replies->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No replies yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach($replies as $reply)
                                <div wire:key="reply-{{ $reply->id }}" class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 {{ $reply->is_from_admin ? 'bg-blue-50 dark:bg-blue-900/20 ml-8' : 'bg-zinc-50 dark:bg-zinc-800 mr-8' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span class="font-medium text-sm {{ $reply->is_from_admin ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-white' }}">
                                                {{ $reply->user?->name ?? 'Unknown' }}
                                            </span>
                                            @if($reply->is_from_admin)
                                                <flux:badge color="sky" size="sm">Admin</flux:badge>
                                            @endif
                                        </div>
                                        <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                            {{ $reply->created_at?->diffForHumans() ?? '' }}
                                        </span>
                                    </div>
                                    <div class="prose dark:prose-invert prose-sm max-w-none">
                                        {!! nl2br(e($reply->body)) !!}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                {{-- Reply Form --}}
                @if($this->editingTicket->status->value === 'open')
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-6">
                        <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100 mb-4">Send Reply</h3>
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
                                        @foreach(TicketStatus::cases() as $status)
                                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Priority</flux:label>
                                    <flux:select wire:model="newPriority">
                                        @foreach(TicketPriority::cases() as $priority)
                                            <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                            </div>

                            <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                                Send Reply
                            </flux:button>
                        </form>
                    </div>
                @else
                    <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 p-4 text-center">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            This ticket is closed.
                        </p>
                    </div>
                @endif

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    @if($showCloseConfirmation)
                        <div class="flex items-center gap-2">
                            <flux:text class="text-zinc-600 dark:text-zinc-400">Are you sure you want to close?</flux:text>
                            <flux:button type="button" wire:click="closeEditModal" variant="danger" size="sm">
                                Yes, Close
                            </flux:button>
                            <flux:button type="button" wire:click="cancelClose" variant="ghost" size="sm">
                                Cancel
                            </flux:button>
                        </div>
                    @else
                        <flux:button type="button" wire:click="confirmClose" variant="ghost">
                            Close
                        </flux:button>
                    @endif
                </div>
            </div>
        @endif
    </flux:modal>
</div>
