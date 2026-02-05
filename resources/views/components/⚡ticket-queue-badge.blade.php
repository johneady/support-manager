<?php

use App\Models\Ticket;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
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

<span>
    @if($this->count > 0)
        <flux:badge color="red" size="sm">{{ $this->count }}</flux:badge>
    @endif
</span>