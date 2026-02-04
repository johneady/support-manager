<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $statusFilter = '';

    #[Computed]
    public function tickets(): Collection
    {
        return Ticket::query()
            ->forUser(auth()->id())
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->latest()
            ->get();
    }
};
?>

<div class="space-y-6">
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
                        <tr wire:key="ticket-{{ $ticket->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $ticket->id }}
                            </td>
                            <td class="px-4 py-4">
                                <a href="{{ route('tickets.show', $ticket) }}" class="font-medium text-zinc-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400">
                                    {{ Str::limit($ticket->subject, 50) }}
                                </a>
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
                                <flux:button href="{{ route('tickets.show', $ticket) }}" size="sm" variant="ghost">
                                    View
                                </flux:button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
