<?php

use App\Models\Faq;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

new class extends Component
{
    #[Locked]
    public ?int $faqId = null;

    #[Validate('required|string|max:500')]
    public string $question = '';

    #[Validate('required|string')]
    public string $answer = '';

    public bool $isPublished = false;

    #[Validate('integer|min:0')]
    public int $sortOrder = 0;

    public function mount(?int $faqId = null): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);

        if ($faqId) {
            $faq = Faq::findOrFail($faqId);
            $this->faqId = $faq->id;
            $this->question = $faq->question;
            $this->answer = $faq->answer;
            $this->isPublished = $faq->is_published;
            $this->sortOrder = $faq->sort_order;
        } else {
            $this->sortOrder = (Faq::query()->max('sort_order') ?? 0) + 1;
        }
    }

    public function isEditing(): bool
    {
        return $this->faqId !== null;
    }

    public function save(): void
    {
        $this->validate();

        if ($this->isEditing()) {
            $faq = Faq::findOrFail($this->faqId);
            $faq->update([
                'question' => $this->question,
                'answer' => $this->answer,
                'is_published' => $this->isPublished,
                'sort_order' => $this->sortOrder,
            ]);
            session()->flash('success', 'FAQ updated successfully.');
        } else {
            Faq::create([
                'question' => $this->question,
                'answer' => $this->answer,
                'is_published' => $this->isPublished,
                'sort_order' => $this->sortOrder,
            ]);
            session()->flash('success', 'FAQ created successfully.');
        }

        $this->redirect(route('admin.faqs'), navigate: true);
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center gap-2">
        <flux:button href="{{ route('admin.faqs') }}" wire:navigate variant="ghost" size="sm" icon="arrow-left">
            Back to FAQs
        </flux:button>
    </div>

    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center gap-4">
            <div class="rounded-full bg-white/20 p-3">
                <flux:icon.question-mark-circle class="size-8 text-white" />
            </div>
            <div>
                <flux:heading size="2xl" class="text-white">{{ $this->isEditing() ? 'Edit FAQ' : 'Create FAQ' }}</flux:heading>
                <flux:text class="text-blue-100">{{ $this->isEditing() ? 'Update this frequently asked question' : 'Add a new frequently asked question' }}</flux:text>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="space-y-4">
        <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
            <flux:field>
                <flux:label>Question</flux:label>
                <flux:input wire:model="question" placeholder="What is the frequently asked question?" />
                <flux:error name="question" />
            </flux:field>

            <flux:field>
                <flux:label>Answer</flux:label>
                <x-markdown-editor wire-model="answer" />
                <flux:error name="answer" />
            </flux:field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <flux:field>
                    <flux:label>Sort Order</flux:label>
                    <flux:input type="number" wire:model="sortOrder" min="0" />
                    <flux:error name="sortOrder" />
                    <flux:text size="sm" class="text-zinc-500">Lower numbers appear first.</flux:text>
                </flux:field>

                <flux:field class="flex items-center pt-6">
                    <flux:checkbox wire:model="isPublished" label="Published" />
                    <flux:text size="sm" class="text-zinc-500 ml-2">Make visible on the public FAQ page.</flux:text>
                </flux:field>
            </div>
        </div>

        <div class="flex items-center gap-4 pt-4">
            <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                {{ $this->isEditing() ? 'Update FAQ' : 'Create FAQ' }}
            </flux:button>
            <flux:button href="{{ route('admin.faqs') }}" wire:navigate variant="ghost">
                Cancel
            </flux:button>
        </div>
    </form>
</div>
