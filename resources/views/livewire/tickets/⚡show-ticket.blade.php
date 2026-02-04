<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public Ticket $ticket;

    #[Validate('required|string|min:5')]
    public string $replyBody = '';

    public function mount(Ticket $ticket): void
    {
        $isOwner = $ticket->user_id === auth()->id();
        $isAdmin = auth()->user()?->isAdmin();

        abort_unless($isOwner || $isAdmin, 403);

        $this->ticket = $ticket;
    }

    public function submitReply(): void
    {
        $this->validate();

        $reply = $this->ticket->replies()->create([
            'user_id' => auth()->id(),
            'body' => $this->replyBody,
            'is_from_admin' => false,
        ]);

        $admins = User::where('is_admin', true)->get();

        if ($admins->isNotEmpty()) {
            Notification::send($admins, new TicketReplyNotification($reply));
        }

        $this->replyBody = '';
        $this->ticket->refresh();

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

    <div class="flex items-center justify-between">
        <flux:button href="{{ route('tickets.index') }}" variant="ghost" icon="arrow-left">
            Back to Tickets
        </flux:button>
    </div>

    {{-- Ticket Details --}}
    <div class="rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                        {{ $ticket->subject }}
                    </h2>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        Ticket #{{ $ticket->id }} &middot; Created {{ $ticket->created_at->diffForHumans() }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <flux:badge color="{{ $ticket->status->color() }}">
                        {{ $ticket->status->label() }}
                    </flux:badge>
                    <flux:badge color="{{ $ticket->priority->color() }}">
                        {{ $ticket->priority->label() }}
                    </flux:badge>
                </div>
            </div>
        </div>
        <div class="px-6 py-4">
            <div class="prose dark:prose-invert prose-sm max-w-none">
                {!! nl2br(e($ticket->description)) !!}
            </div>
        </div>
    </div>

    {{-- Conversation --}}
    <div class="space-y-4">
        <h3 class="text-base font-semibold text-zinc-900 dark:text-white">Conversation</h3>

        @php
            $replies = $ticket->replies()->with('user')->orderBy('created_at')->get();
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
                                    {{ $reply->user->name }}
                                </span>
                                @if($reply->is_from_admin)
                                    <flux:badge color="sky" size="sm">Support</flux:badge>
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
    </div>

    {{-- Reply Form --}}
    @if($ticket->status->value === 'open')
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
</div>
