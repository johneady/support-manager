<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';

    public ?string $categoryFilter = null;

    public bool $showEditModal = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $editingTicketId = null;

    #[Validate('required|string|min:5|max:5000')]
    public string $replyBody = '';

    public ?string $newStatus = null;

    public ?string $newPriority = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->search = trim($this->search);
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function categories(): Collection
    {
        return TicketCategory::query()->active()->ordered()->get();
    }

    #[Computed]
    public function tickets(): LengthAwarePaginator
    {
        return Ticket::query()
            ->with(['user', 'ticketCategory', 'latestReply'])
            ->open()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('subject', 'like', '%' . $this->search . '%')
                        ->orWhereHas('user', function ($userQuery) {
                            $userQuery->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('email', 'like', '%' . $this->search . '%');
                        })
                        ->orWhere('ticket_reference_number', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('ticket_category_id', $this->categoryFilter);
            })
            ->orderByRaw(
                "
                CASE
                    WHEN NOT EXISTS (SELECT 1 FROM ticket_replies tr WHERE tr.ticket_id = tickets.id)
                         OR (SELECT tr.is_from_admin FROM ticket_replies tr WHERE tr.ticket_id = tickets.id ORDER BY tr.id DESC LIMIT 1) = 0
                    THEN 0
                    ELSE 1
                END ASC
            ",
            )
            ->orderByRaw(
                "
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                END ASC
            ",
            )
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

        return Ticket::with('replies.user', 'user', 'ticketCategory')->find($this->editingTicketId);
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

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingTicketId = null;
        $this->reset(['replyBody', 'newStatus', 'newPriority', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function submitReply(): void
    {
        $this->validate();

        $key = 'ticket-reply:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $this->addError('replyBody', 'Too many replies. Please try again later.');

            return;
        }

        RateLimiter::increment($key);

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

        $this->dispatch('ticket-replied');

        $this->closeEditModal();
        session()->flash('success', 'Reply sent successfully.');
    }
};
?>

<div class="space-y-6">
    @if (session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if (session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Header Banner --}}
    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-full bg-white/20 p-3">
                    <flux:icon.inbox-stack class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">Ticket Queue</flux:heading>
                    <flux:text class="text-blue-100">Manage and respond to open support tickets that require a response
                    </flux:text>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <flux:badge color="blue" size="lg" class="!bg-white/20 !text-white border border-white/30">
                    {{ $this->needsResponseCount }}</flux:badge>
                <span class="text-sm text-blue-100">need response</span>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="w-full sm:w-80">
            <flux:input wire:model.live.debounce.300ms="search"
                placeholder="Search subject, reference, name, or email..." icon="magnifying-glass" />
        </div>
        <div class="w-full sm:w-64">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach ($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if ($this->tickets->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if ($search)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No tickets found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No tickets match your search
                    "{{ $search }}".</p>
            @else
                <flux:icon.check-circle class="mx-auto h-12 w-12 text-green-500" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">All caught up!</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">There are no open tickets requiring attention.
                </p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Reference</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            User</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Subject</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Category</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Status</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Priority</th>
                        <th
                            class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            Created</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach ($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}"
                            class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50"
                            wire:click="openEditModal({{ $ticket->id }})">
                            <td class="whitespace-nowrap px-4 py-4 text-sm font-mono text-zinc-600 dark:text-zinc-400">
                                {{ $ticket->reference_number }}
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                <div>{{ $ticket->user->name }}</div>
                                <div class="text-xs">{{ $ticket->user->email }}</div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="font-medium text-zinc-900 dark:text-white">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if ($ticket->ticketCategory)
                                    <flux:badge color="{{ $ticket->ticketCategory->color }}" size="sm">
                                        {{ $ticket->ticketCategory->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">No Category</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <div class="flex items-center gap-2">
                                    @if ($ticket->needsResponse())
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
        @if ($this->editingTicket)
            <div class="space-y-6">
                <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                    <div class="flex items-center gap-3">
                        <flux:icon.ticket class="size-6 text-blue-600 dark:text-blue-400" />
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                            Ticket {{ $this->editingTicket->reference_number }}
                        </flux:heading>
                        <flux:text class="mt-2 text-blue-700 dark:text-blue-300">
                            Submitted by {{ $this->editingTicket->user->name }}
                            ({{ $this->editingTicket->user->email }})
                        </flux:text>
                    </div>
                </div>

                {{-- Modal Message --}}
                @if ($modalMessage)
                    <flux:callout variant="{{ $modalMessageType }}"
                        icon="{{ $modalMessageType === 'success' ? 'check-circle' : 'exclamation-circle' }}"
                        dismissible>
                        {{ $modalMessage }}
                    </flux:callout>
                @endif

                {{-- Ticket Details --}}
                <div
                    class="space-y-4 rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800">
                    <div>
                        <flux:label>Subject</flux:label>
                        <div
                            class="mt-1 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700">
                            {{ $this->editingTicket->subject }}
                        </div>
                    </div>

                    <div>
                        <flux:label>Description</flux:label>
                        <div
                            class="mt-1 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 whitespace-pre-wrap">
                            {{ $this->editingTicket->description }}</div>
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

                    @if ($replies->isEmpty())
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">No replies yet.</p>
                    @else
                        <div class="space-y-4">
                            @foreach ($replies as $reply)
                                <div wire:key="reply-{{ $reply->id }}"
                                    class="rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 {{ $reply->is_from_admin ? 'bg-blue-50 dark:bg-blue-900/20 ml-8' : 'bg-zinc-50 dark:bg-zinc-800 mr-8' }}">
                                    <div class="flex items-center justify-between mb-2">
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="font-medium text-sm {{ $reply->is_from_admin ? 'text-blue-600 dark:text-blue-400' : 'text-zinc-900 dark:text-white' }}">
                                                {{ $reply->user?->name ?? 'Unknown' }}
                                            </span>
                                            @if ($reply->is_from_admin)
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
                @if ($this->editingTicket->status->value === 'open')
                    <div
                        class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-6">
                        <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100 mb-4">Send Reply</h3>
                        <form wire:submit="submitReply" class="space-y-4">
                            <flux:field>
                                <flux:label>Your Reply</flux:label>
                                <flux:textarea wire:model="replyBody" placeholder="Type your response..."
                                    rows="4" />
                                <flux:error name="replyBody" />
                            </flux:field>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Status</flux:label>
                                    <flux:select wire:model="newStatus">
                                        @foreach (\App\Enums\TicketStatus::cases() as $status)
                                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Priority</flux:label>
                                    <flux:select wire:model="newPriority">
                                        @foreach (\App\Enums\TicketPriority::cases() as $priority)
                                            <flux:select.option value="{{ $priority->value }}">
                                                {{ $priority->label() }}</flux:select.option>
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
                    <flux:button type="button" wire:click="closeEditModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
