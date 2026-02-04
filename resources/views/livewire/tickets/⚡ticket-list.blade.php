<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    public string $statusFilter = '';

    public bool $showEditModal = false;

    #[Locked]
    public ?int $editingTicketId = null;

    #[Validate('required|string|max:255')]
    public string $editSubject = '';

    #[Validate('required|string|min:10')]
    public string $editDescription = '';

    #[Validate('required|in:low,medium,high')]
    public string $editPriority = 'medium';

    #[Validate('required|string|min:5')]
    public string $replyBody = '';

    #[Computed]
    public function tickets(): Collection
    {
        return Ticket::query()
            ->forUser(auth()->id())
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->get();
    }

    #[Computed]
    public function editingTicket(): ?Ticket
    {
        if ($this->editingTicketId === null) {
            return null;
        }

        return Ticket::with('replies.user')->find($this->editingTicketId);
    }

    public function openEditModal(Ticket $ticket): void
    {
        $this->authorize('update', $ticket);

        $this->editingTicketId = $ticket->id;
        $this->editSubject = $ticket->subject;
        $this->editDescription = $ticket->description;
        $this->editPriority = $ticket->priority->value;
        $this->replyBody = '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTicketId = null;
        $this->reset(['editSubject', 'editDescription', 'editPriority', 'replyBody']);
        $this->resetValidation();
    }

    public function updateTicket(): void
    {
        $this->validate(['editSubject', 'editDescription', 'editPriority']);

        $ticket = Ticket::findOrFail($this->editingTicketId);
        $this->authorize('update', $ticket);

        $ticket->update([
            'subject' => $this->editSubject,
            'description' => $this->editDescription,
            'priority' => $this->editPriority,
        ]);

        session()->flash('success', 'Ticket updated successfully.');
    }

    public function submitReply(): void
    {
        $this->validate(['replyBody']);

        $ticket = Ticket::findOrFail($this->editingTicketId);

        $reply = $ticket->replies()->create([
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
            'is_from_admin' => false,
        ]);

        $admins = User::where('is_admin', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new TicketReplyNotification($reply));
        }

        $this->replyBody = '';

        session()->flash('success', 'Your reply has been submitted.');
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="max-w-xs">
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                @foreach(TicketStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <flux:button href="{{ route('tickets.create') }}" icon="plus">
            New Ticket
        </flux:button>
    </div>

    @if($this->tickets->isEmpty())
        <div class="text-center py-12">
            <flux:icon.ticket class="mx-auto h-12 w-12 text-zinc-400" />
            <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No tickets</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if($statusFilter)
                    No tickets match your filter.
                @else
                    You haven't submitted any support tickets yet.
                @endif
            </p>
            <div class="mt-6">
                <flux:button href="{{ route('tickets.create') }}" icon="plus">
                    Create your first ticket
                </flux:button>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</th>
                        <th class="px-4 py-3"></th>
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
                                <flux:badge color="{{ $ticket->status->color() }}" size="sm">
                                    {{ $ticket->status->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <flux:badge color="{{ $ticket->priority->color() }}" size="sm">
                                    {{ $ticket->priority->label() }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $ticket->created_at->diffForHumans() }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm">
                                <flux:button wire:click.stop="openEditModal({{ $ticket->id }})" size="sm" variant="ghost" icon="pencil">
                                    Edit
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    {{-- Edit Ticket Modal --}}
    <flux:modal wire:model.self="showEditModal" class="md:w-3xl max-h-[90vh] overflow-y-auto">
        @if($this->editingTicket)
            <div class="space-y-6">
                <div>
                    <flux:heading size="lg">Edit Ticket</flux:heading>
                    <flux:text class="mt-2">Update your support ticket details and add replies.</flux:text>
                </div>

                {{-- Ticket Details Form --}}
                <form wire:submit="updateTicket" class="space-y-4">
                    <flux:input
                        wire:model="editSubject"
                        label="Subject"
                        placeholder="Brief description of your issue"
                        required
                    />

                    <flux:textarea
                        wire:model="editDescription"
                        label="Description"
                        placeholder="Please describe your issue in detail..."
                        rows="6"
                        required
                    />

                    <flux:select wire:model="editPriority" label="Priority">
                        @foreach(TicketPriority::cases() as $priority)
                            <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <div class="flex items-center gap-4 pt-4">
                        <flux:button type="submit" variant="primary">
                            Save Changes
                        </flux:button>
                    </div>
                </form>

                {{-- Conversation --}}
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Conversation</h3>

                    @php
                        $replies = $this->editingTicket->replies ?? collect();
                    @endphp

                    @if($replies->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No replies yet. We'll respond as soon as possible.</p>
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
                                                <flux:badge color="sky" size="sm">Support</flux:badge>
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
                    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 p-6">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-white mb-4">Add a Reply</h3>
                        <form wire:submit="submitReply" class="space-y-4">
                            <flux:textarea
                                wire:model="replyBody"
                                placeholder="Type your reply..."
                                rows="4"
                                required
                            />
                            <flux:button type="submit" variant="primary">
                                Send Reply
                            </flux:button>
                        </form>
                    </div>
                @else
                    <div class="rounded-lg bg-zinc-100 dark:bg-zinc-800 p-4 text-center">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            This ticket is closed. If you need further assistance, please create a new ticket.
                        </p>
                    </div>
                @endif

                <div class="flex items-center gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <flux:button type="button" wire:click="closeEditModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
