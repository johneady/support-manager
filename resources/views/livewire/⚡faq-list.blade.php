<?php

use App\Models\Faq;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public string $search = '';

    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()
            ->published()
            ->ordered()
            ->when($this->search, fn ($query) => $query->where('question', 'like', "%{$this->search}%"))
            ->get();
    }
};
?>

<div class="space-y-6">
    <div class="max-w-md">
        <flux:input
            wire:model.live.debounce.300ms="search"
            placeholder="Search FAQs..."
            icon="magnifying-glass"
        />
    </div>

    @if($this->faqs->isEmpty())
        <div class="text-center py-12">
            <flux:icon.question-mark-circle class="mx-auto h-12 w-12 text-zinc-400" />
            <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No FAQs found</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if($search)
                    Try adjusting your search terms.
                @else
                    No frequently asked questions have been published yet.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($this->faqs as $faq)
                <div wire:key="faq-{{ $faq->id }}" x-data="{ open: false }">
                    <button
                        @click="open = !open"
                        class="flex w-full items-center justify-between rounded-lg bg-white dark:bg-zinc-800 px-4 py-4 text-left shadow-sm ring-1 ring-zinc-200 dark:ring-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition"
                    >
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $faq->question }}</span>
                        <flux:icon.chevron-down
                            class="h-5 w-5 text-zinc-500 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': open }"
                        />
                    </button>
                    <div
                        x-show="open"
                        x-collapse
                        class="mt-2 rounded-lg bg-zinc-50 dark:bg-zinc-800/50 px-4 py-4"
                    >
                        <div class="prose dark:prose-invert prose-sm max-w-none">
                            {!! $faq->answer !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
