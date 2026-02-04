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
        <div class="text-center py-12 bg-white/60 dark:bg-zinc-800/60 backdrop-blur-sm rounded-xl border border-violet-100 dark:border-zinc-700">
            <div class="w-16 h-16 mx-auto rounded-full bg-linear-to-br from-violet-100 to-pink-100 dark:from-violet-900/50 dark:to-pink-900/50 flex items-center justify-center">
                <flux:icon.question-mark-circle class="h-8 w-8 text-violet-500 dark:text-violet-400" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">No FAQs found</h3>
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
                        class="flex w-full items-center justify-between rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm px-5 py-4 text-left shadow-sm ring-1 ring-violet-100 dark:ring-zinc-700 hover:bg-violet-50/50 dark:hover:bg-zinc-700/50 hover:shadow-md hover:shadow-violet-200/30 dark:hover:shadow-violet-900/20 transition-all duration-300"
                    >
                        <span class="font-medium text-zinc-900 dark:text-white">{{ $faq->question }}</span>
                        <flux:icon.chevron-down
                            class="h-5 w-5 text-violet-400 shrink-0 transition-transform duration-200"
                            x-bind:class="{ 'rotate-180': open }"
                        />
                    </button>
                    <div
                        x-show="open"
                        x-collapse
                        class="mt-2 rounded-xl bg-linear-to-br from-violet-50/50 to-pink-50/50 dark:from-zinc-800/50 dark:to-zinc-800/30 px-5 py-4 border border-violet-100/50 dark:border-zinc-700/50"
                    >
                        <div class="prose dark:prose-invert prose-sm max-w-none prose-violet">
                            {!! $faq->answer !!}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
