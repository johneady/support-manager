<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Validate('required|string|max:255')]
    public string $subject = '';

    #[Validate('required|string|min:10|max:5000')]
    public string $description = '';

    #[Validate('required|exists:ticket_categories,id')]
    public int $ticketCategoryId = 1;

    #[Validate('required|in:low,medium,high')]
    public string $priority = 'medium';

    #[Computed]
    public function categories(): Collection
    {
        return TicketCategory::query()
            ->active()
            ->ordered()
            ->get();
    }

    public function mount(): void
    {
        // Set default to Technical Support category
        $technicalSupport = TicketCategory::where('slug', 'technical-support')->first();
        if ($technicalSupport) {
            $this->ticketCategoryId = $technicalSupport->id;
        }
    }

    public function submit(): void
    {
        $this->validate();

        $key = 'create-ticket:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 2)) {
            $this->addError('subject', 'Too many tickets created. Please try again later.');

            return;
        }

        RateLimiter::increment($key);

        $ticket = Ticket::create([
            'user_id' => auth()->id(),
            'subject' => $this->subject,
            'description' => $this->description,
            'ticket_category_id' => $this->ticketCategoryId,
            'priority' => $this->priority,
        ]);

        $admins = User::admins();

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

        <flux:select wire:model="ticketCategoryId" label="Category">
            @foreach($this->categories as $category)
                <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model="priority" label="Priority">
            @foreach(\App\Enums\TicketPriority::cases() as $priority)
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
