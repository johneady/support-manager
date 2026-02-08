<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component
{
    #[On('ticket-replied')]
    public function refreshCount(): void
    {
        unset($this->count);
    }

    #[Computed]
    public function count(): int
    {
        if (! auth()->user()?->isAdmin()) {
            return 0;
        }

        return Ticket::query()->open()->needsResponse()->count();
    }
};
?>

<div>
    <flux:sidebar.item icon="inbox-stack" :href="route('tickets.queue')" :current="request()->routeIs('tickets.queue')" :badge="$this->count > 0 ? $this->count : null" wire:navigate class="text-zinc-900 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 font-medium">
        {{ __('Ticket Queue') }}
    </flux:sidebar.item>
</div>
