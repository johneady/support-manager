<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Notifications\TicketReplyNotification;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?string $categoryFilter = null;

    public ?string $statusFilter = null;

    public ?string $priorityFilter = null;

    public string $sortBy = 'created_at';

    public string $sortDirection = 'desc';

    public bool $showViewModal = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $viewingTicketId = null;

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
        $this->resetPage();
    }

    public function updatedCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedPriorityFilter(): void
    {
        $this->resetPage();
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
    public function tickets(): LengthAwarePaginator
    {
        $searchIdPrefix = null;
        if (preg_match('/TX-1138-(\d+)/', $this->search, $matches)) {
            $searchIdPrefix = $matches[1];
        }

        $query = Ticket::query()
            ->with(['user', 'ticketCategory', 'latestReply'])
            ->when($this->search, function ($query) use ($searchIdPrefix) {
                $query->where(function ($q) use ($searchIdPrefix) {
                    if ($searchIdPrefix !== null) {
                        $q->where('id', (int) $searchIdPrefix);
                    } elseif (is_numeric($this->search)) {
                        $q->where('id', (int) $this->search);
                    } else {
                        $q->where('subject', 'like', '%'.$this->search.'%')
                            ->orWhereHas('user', function ($userQuery) {
                                $userQuery->where('name', 'like', '%'.$this->search.'%')
                                    ->orWhere('email', 'like', '%'.$this->search.'%');
                            });
                    }
                });
            })
            ->when($this->categoryFilter, function ($query) {
                $query->where('ticket_category_id', $this->categoryFilter);
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->priorityFilter, function ($query) {
                $query->where('priority', $this->priorityFilter);
            });

        $sortableColumns = ['id', 'subject', 'created_at', 'status', 'priority'];
        if (in_array($this->sortBy, $sortableColumns)) {
            $query->orderBy($this->sortBy, $this->sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate(10);
    }

    #[Computed]
    public function viewingTicket(): ?Ticket
    {
        if ($this->viewingTicketId === null) {
            return null;
        }

        return Ticket::with('replies.user', 'user', 'ticketCategory')->find($this->viewingTicketId);
    }

    public function openViewModal(Ticket $ticket): void
    {
        $this->viewingTicketId = $ticket->id;
        $this->replyBody = '';
        $this->newStatus = $ticket->status->value;
        $this->newPriority = $ticket->priority->value;
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->viewingTicketId = null;
        $this->reset(['replyBody', 'newStatus', 'newPriority', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function sortBy(string $column): void
    {
        if ($this->sortBy === $column) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortBy = $column;
            $this->sortDirection = 'desc';
        }
    }

    public function reopenTicket(Ticket $ticket): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        $ticket->reopen();

        unset($this->tickets);
        unset($this->viewingTicket);

        $this->modalMessage = 'Ticket reopened successfully.';
        $this->modalMessageType = 'success';

        session()->flash('success', 'Ticket reopened successfully.');
    }

    public function submitReply(): void
    {
        $this->validate();

        $key = 'ticket-reply:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 2)) {
            $this->addError('replyBody', 'Too many replies. Please try again later.');

            return;
        }

        RateLimiter::increment($key);

        $ticket = Ticket::findOrFail($this->viewingTicketId);

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
        unset($this->viewingTicket);

        $this->replyBody = '';

        $this->closeViewModal();
        session()->flash('success', 'Reply sent successfully.');
    }

    public function getSortIcon(string $column): string
    {
        if ($this->sortBy !== $column) {
            return 'chevrons-up-down';
        }

        return $this->sortDirection === 'asc' ? 'chevron-up' : 'chevron-down';
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
                    <flux:icon.layout-grid class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">All Tickets</flux:heading>
                    <flux:text class="text-blue-100">View and manage all support tickets</flux:text>
                </div>
            </div>
        </div>
    </div>

    {{-- Search and Filters --}}
    <div class="flex flex-col sm:flex-row gap-4">
        <div class="w-full sm:w-80">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search subject, reference, name, or email..."
                icon="magnifying-glass"
            />
        </div>
        <div class="w-full sm:w-64">
            <flux:select wire:model.live="categoryFilter">
                <flux:select.option value="">All Categories</flux:select.option>
                @foreach($this->categories as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="statusFilter">
                <flux:select.option value="">All Statuses</flux:select.option>
                @foreach(\App\Enums\TicketStatus::cases() as $status)
                    <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
        <div class="w-full sm:w-48">
            <flux:select wire:model.live="priorityFilter">
                <flux:select.option value="">All Priorities</flux:select.option>
                @foreach(\App\Enums\TicketPriority::cases() as $priority)
                    <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>
    </div>

    @if($this->tickets->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if($search || $categoryFilter || $statusFilter || $priorityFilter)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No tickets found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No tickets match your search criteria.</p>
            @else
                <flux:icon.ticket class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No tickets</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">There are no tickets in the system yet.</p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('id')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer uppercase">
                                Reference
                                <flux:icon.{{ $this->getSortIcon('id') }} class="size-3" />
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">User</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('subject')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer uppercase">
                                Subject
                                <flux:icon.{{ $this->getSortIcon('subject') }} class="size-3" />
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('status')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer uppercase">
                                Status
                                <flux:icon.{{ $this->getSortIcon('status') }} class="size-3" />
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('priority')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer uppercase">
                                Priority
                                <flux:icon.{{ $this->getSortIcon('priority') }} class="size-3" />
                            </button>
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <button wire:click="sortBy('created_at')" class="flex items-center gap-1 hover:text-zinc-700 dark:hover:text-zinc-300 cursor-pointer uppercase">
                                Created
                                <flux:icon.{{ $this->getSortIcon('created_at') }} class="size-3" />
                            </button>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->tickets as $ticket)
                        <tr wire:key="ticket-{{ $ticket->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:click="openViewModal({{ $ticket->id }})">
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
                                @if($ticket->ticketCategory)
                                    <flux:badge color="{{ $ticket->ticketCategory->color }}" size="sm">
                                        {{ $ticket->ticketCategory->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">No Category</flux:badge>
                                @endif
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
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $this->tickets->links() }}
        </div>
    @endif

    {{-- View Ticket Modal --}}
    <flux:modal wire:model.self="showViewModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        @if($this->viewingTicket)
            <div class="space-y-6">
                <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                    <div class="flex items-center gap-3">
                        <flux:icon.ticket class="size-6 text-blue-600 dark:text-blue-400" />
                        <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
                            Ticket {{ $this->viewingTicket->reference_number }}
                        </flux:heading>
                        <flux:text class="mt-2 text-blue-700 dark:text-blue-300">
                            Submitted by {{ $this->viewingTicket->user->name }} ({{ $this->viewingTicket->user->email }})
                        </flux:text>
                    </div>
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
                            {{ $this->viewingTicket->subject }}
                        </div>
                    </div>

                    <div>
                        <flux:label>Description</flux:label>
                        <div class="mt-1 px-3 py-2 bg-zinc-100 dark:bg-zinc-800 rounded-md text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 whitespace-pre-wrap">{{ $this->viewingTicket->description }}</div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <flux:label>Status</flux:label>
                            <div class="mt-1">
                                <flux:badge color="{{ $this->viewingTicket->status->color() }}" size="sm">
                                    {{ $this->viewingTicket->status->label() }}
                                </flux:badge>
                            </div>
                        </div>
                        <div>
                            <flux:label>Priority</flux:label>
                            <div class="mt-1">
                                <flux:badge color="{{ $this->viewingTicket->priority->color() }}" size="sm">
                                    {{ $this->viewingTicket->priority->label() }}
                                </flux:badge>
                            </div>
                        </div>
                        <div>
                            <flux:label>Category</flux:label>
                            <div class="mt-1">
                                @if($this->viewingTicket->ticketCategory)
                                    <flux:badge color="{{ $this->viewingTicket->ticketCategory->color }}" size="sm">
                                        {{ $this->viewingTicket->ticketCategory->name }}
                                    </flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">No Category</flux:badge>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Conversation --}}
                <div class="space-y-4">
                    <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100">Conversation</h3>

                    @php
                        $replies = $this->viewingTicket->replies ?? collect();
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

                {{-- Reply Form / Reopen --}}
                @if($this->viewingTicket->status->value === 'open')
                    <div class="rounded-lg border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 p-6">
                        <h3 class="text-base font-semibold text-blue-900 dark:text-blue-100 mb-4">Send Reply</h3>
                        <form wire:submit="submitReply" class="space-y-4">
                            <flux:field>
                                <flux:label>Your Reply</flux:label>
                                <flux:textarea wire:model="replyBody" placeholder="Type your response..." rows="4" />
                                <flux:error name="replyBody" />
                            </flux:field>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <flux:field>
                                    <flux:label>Status</flux:label>
                                    <flux:select wire:model="newStatus">
                                        @foreach(\App\Enums\TicketStatus::cases() as $status)
                                            <flux:select.option value="{{ $status->value }}">{{ $status->label() }}</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                </flux:field>
                                <flux:field>
                                    <flux:label>Priority</flux:label>
                                    <flux:select wire:model="newPriority">
                                        @foreach(\App\Enums\TicketPriority::cases() as $priority)
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
                    @if($this->viewingTicket->status->value === 'closed')
                        <flux:button wire:click="reopenTicket({{ $this->viewingTicket->id }})" variant="primary" class="bg-green-600 hover:bg-green-700">
                            Reopen Ticket
                        </flux:button>
                    @endif
                    <flux:button type="button" wire:click="closeViewModal" variant="ghost">
                        Close
                    </flux:button>
                </div>
            </div>
        @endif
    </flux:modal>
</div>
