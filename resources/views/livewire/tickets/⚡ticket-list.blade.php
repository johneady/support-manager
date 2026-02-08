<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Notifications\NewTicketNotification;
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

    public bool $showCreateModal = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $editingTicketId = null;

    #[Validate('required|in:low,medium,high')]
    public string $editPriority = 'medium';

    #[Validate('required|string|min:5')]
    public string $replyBody = '';

    #[Validate('required|string|max:255')]
    public string $newSubject = '';

    #[Validate('required|string|min:10')]
    public string $newDescription = '';

    #[Validate('required|exists:ticket_categories,id')]
    public int $newTicketCategoryId = 1;

    #[Validate('required|in:low,medium,high')]
    public string $newPriority = 'medium';

    #[Computed]
    public function tickets(): Collection
    {
        return Ticket::query()
            ->forUser(auth()->id())
            ->with(['user', 'ticketCategory', 'replies' => fn ($query) => $query->latest()->limit(1)])
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->get();
    }

    #[Computed]
    public function categories(): Collection
    {
        return TicketCategory::query()
            ->active()
            ->ordered()
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
        $this->editPriority = $ticket->priority->value;
        $this->replyBody = '';
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTicketId = null;
        $this->reset(['editPriority', 'replyBody', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function openCreateModal(): void
    {
        $this->reset(['newSubject', 'newDescription', 'newTicketCategoryId', 'newPriority']);
        $technicalSupport = TicketCategory::where('slug', 'technical-support')->first();
        if ($technicalSupport) {
            $this->newTicketCategoryId = $technicalSupport->id;
        }
        $this->newPriority = 'medium';
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['newSubject', 'newDescription', 'newTicketCategoryId', 'newPriority']);
        $this->resetValidation();
    }

    public function createTicket(): void
    {
        $this->validate([
            'newSubject' => 'required|string|max:255',
            'newDescription' => 'required|string|min:10',
            'newTicketCategoryId' => 'required|exists:ticket_categories,id',
            'newPriority' => 'required|in:low,medium,high',
        ]);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'subject' => $this->newSubject,
            'description' => $this->newDescription,
            'ticket_category_id' => $this->newTicketCategoryId,
            'priority' => $this->newPriority,
        ]);

        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new NewTicketNotification($ticket));

        $this->closeCreateModal();

        session()->flash('success', 'Your support ticket has been submitted successfully.');
    }

    public function updateTicket(): void
    {
        $this->validate([
            'editPriority' => 'required|in:low,medium,high',
        ]);

        $ticket = Ticket::findOrFail($this->editingTicketId);
        $this->authorize('update', $ticket);

        $ticket->update([
            'priority' => $this->editPriority,
        ]);

        $this->modalMessage = 'Ticket updated successfully.';
        $this->modalMessageType = 'success';
    }

    public function submitReply(): void
    {
        $this->validate([
            'replyBody' => 'required|string|min:5',
        ]);

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

        $this->modalMessage = 'Your reply has been submitted.';
        $this->modalMessageType = 'success';

        $this->closeEditModal();
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

    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Header Banner --}}
    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-full bg-white/20 p-3">
                    <flux:icon.ticket class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">My Support Tickets</flux:heading>
                    <flux:text class="text-blue-100">Manage your support requests and conversations</flux:text>
                </div>
            </div>
            <button wire:click="openCreateModal" class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50">
                <flux:icon.plus class="size-4" />
                New Ticket
            </button>
        </div>
    </div>

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="max-w-xs">
            <flux:select wire:model.live="statusFilter" placeholder="All Statuses">
                <flux:select.option value="">All Statuses</flux:select.option>
                @foreach(\App\Enums\TicketStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
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
                <flux:button wire:click="openCreateModal" icon="plus">
                    Create your first ticket
                </flux:button>
            </div>
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reference</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Subject</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Priority</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:click="openEditModal({{ $ticket->id }})">
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-mono text-zinc-600 dark:text-zinc-400">
                                {{ $ticket->reference_number }}
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if($ticket->ticketCategory)
                                    <flux:badge color="{{ $ticket->ticketCategory->color }}" size="sm">
                                        {{ $ticket->ticketCategory->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">No Category</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2">
                                    <flux:badge color="{{ $ticket->status->color() }}" size="sm">
                                        {{ $ticket->status->label() }}
                                    </flux:badge>
                                    @if($ticket->status === \App\Enums\TicketStatus::Open && ! $ticket->needsResponse())
                                        <flux:badge color="sky" size="sm">
                                            Responded
                                        </flux:badge>
                                    @endif
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
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm" wire:click.stop>
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

    {{-- Create Ticket Modal --}}
    <flux:modal wire:model.self="showCreateModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.ticket class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Create New Ticket</flux:heading>
                    <flux:text class="mt-2 text-blue-700 dark:text-blue-300">Submit a new support request.</flux:text>
                </div>
            </div>

            <form wire:submit="createTicket" class="space-y-4">
                <div class="space-y-4 rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800">
                    <flux:input
                        wire:model="newSubject"
                        label="Subject"
                        placeholder="Brief description of your issue"
                        required
                    />

                    <flux:textarea
                        wire:model="newDescription"
                        label="Description"
                        placeholder="Please describe your issue in detail..."
                        rows="6"
                        required
                    />

                    <flux:select wire:model="newTicketCategoryId" label="Category">
                        @foreach($this->categories as $category)
                            <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                        @endforeach
                    </flux:select>

                    <flux:select wire:model="newPriority" label="Priority">
                        @foreach(\App\Enums\TicketPriority::cases() as $priority)
                            <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Submit Ticket
                    </flux:button>
                    <flux:button type="button" wire:click="closeCreateModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit Ticket Modal --}}
    <flux:modal wire:model.self="showEditModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        @if($this->editingTicket)
            <div class="space-y-6">
                <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                    <div class="flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <flux:icon.ticket class="size-6 text-blue-600 dark:text-blue-400" />
                            <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Edit Ticket</flux:heading>
                        </div>
                        <flux:badge color="{{ $this->editingTicket->status->color() }}">
                            {{ $this->editingTicket->status->label() }}
                        </flux:badge>
                    </div>
                    <flux:text class="mt-2 text-blue-700 dark:text-blue-300">Update your support ticket details and add replies.</flux:text>
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
                </div>

                {{-- Conversation --}}
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100">Conversation</h3>

                    @php
                        $replies = $this->editingTicket->replies ?? collect();
                    @endphp

                    @if($replies->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No responses received yet. Our team will review your request and respond promptly. Please note that adding a reply will not affect your ticket's position in the queue.</p>
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
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-6">
                        <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100 mb-4">Add a Reply</h3>
                        <form wire:submit="submitReply" class="space-y-4">
                            <flux:field>
                                <flux:label>Your Reply</flux:label>
                                <flux:textarea
                                    wire:model="replyBody"
                                    placeholder="Type your reply..."
                                    rows="4"
                                />
                                <flux:error name="replyBody" />
                            </flux:field>

                            <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
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

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="button" wire:click="closeEditModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
