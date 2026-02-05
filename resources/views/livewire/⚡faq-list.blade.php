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
        <div class="text-center py-12 bg-white/60 dark:bg-zinc-800/60 backdrop-blur-sm rounded-xl border border-blue-100 dark:border-zinc-700">
            <div class="w-16 h-16 mx-auto rounded-full bg-linear-to-br from-blue-100 to-sky-100 dark:from-blue-900/50 dark:to-sky-900/50 flex items-center justify-center">
                <flux:icon.question-mark-circle class="h-8 w-8 text-blue-500 dark:text-blue-400" />
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
                <a
                    wire:key="faq-{{ $faq->id }}"
                    href="{{ route('faq.show', $faq) }}"
                    class="block rounded-xl bg-white/80 dark:bg-zinc-800/80 backdrop-blur-sm px-5 py-4 shadow-sm ring-1 ring-blue-100 dark:ring-zinc-700 hover:bg-blue-50/50 dark:hover:bg-zinc-700/50 hover:shadow-md hover:shadow-blue-200/30 dark:hover:shadow-blue-900/20 transition-all duration-300 group"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-medium text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $faq->question }}</h3>
                            <p class="mt-2 text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2">{{ $faq->summary() }}</p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
                                <flux:icon.clock class="size-3.5" />
                                {{ $faq->readingTime() }} min read
                            </span>
                        </div>
                        <flux:icon.chevron-right class="size-5 text-blue-400 shrink-0 mt-0.5 group-hover:translate-x-0.5 transition-transform" />
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
