<?php

use App\Enums\TicketPriority;
use App\Models\Ticket;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required|string|min:10')]
    public string $description = '';

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'medium';

    public function submit(): void
    {
        $this->validate();

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'subject' => $this->subject,
            'description' => $this->description,
            'priority' => $this->priority,
        ]);

        $admins = User::where('is_admin', true)->get();

        Notification::send($admins, new NewTicketNotification($ticket));

        session()->flash('success', 'Your support ticket has been submitted successfully.');

        $this->redirect(route('tickets.show', $ticket), navigate: true);
    }
};
?>

<div class="max-w-2xl">
    <form wire:submit="submit" class="space-y-6">
        <flux:input
            wire:model="subject"
            label="Subject"
            placeholder="Brief description of your issue"
            required
        />

        <flux:textarea
            wire:model="description"
            label="Description"
            placeholder="Please describe your issue in detail..."
            rows="6"
            required
        />

        <flux:select wire:model="priority" label="Priority">
            @foreach(TicketPriority::cases() as $priority)
                <flux:select.option value="{{ $priority->value }}">{{ $priority->label() }}</flux:select.option>
            @endforeach
        </flux:select>

        <div class="flex items-center gap-4">
            <flux:button type="submit" variant="primary">
                Submit Ticket
            </flux:button>
            <flux:button href="{{ route('tickets.index') }}" variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
