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
        <flux:input wire:model.live.debounce.300ms="search" placeholder="Search FAQs..." icon="magnifying-glass" />
    </div>

    @if ($this->faqs->isEmpty())
        <div class="rounded-xl border border-blue-100 bg-white/60 py-12 text-center backdrop-blur-sm dark:border-zinc-700 dark:bg-zinc-800/60">
            <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-linear-to-br from-blue-100 to-sky-100 dark:from-blue-900/50 dark:to-sky-900/50">
                <flux:icon.question-mark-circle class="h-8 w-8 text-blue-500 dark:text-blue-400" />
            </div>
            <h3 class="mt-4 text-sm font-semibold text-zinc-900 dark:text-white">No FAQs found</h3>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">
                @if ($search)
                    Try adjusting your search terms.
                @else
                    No frequently asked questions have been published yet.
                @endif
            </p>
        </div>
    @else
        <div class="space-y-4">
            @foreach ($this->faqs as $faq)
                <a
                    wire:key="faq-{{ $faq->id }}"
                    href="{{ route('faq.show', $faq) }}"
                    class="group block rounded-xl bg-white/80 px-5 py-4 shadow-sm ring-1 ring-blue-100 backdrop-blur-sm transition-all duration-300 hover:bg-blue-50/50 hover:shadow-md hover:shadow-blue-200/30 dark:bg-zinc-800/80 dark:ring-zinc-700 dark:hover:bg-zinc-700/50 dark:hover:shadow-blue-900/20"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0">
                            <h3 class="font-medium text-zinc-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                                {{ $faq->question }}
                            </h3>
                            <p class="mt-2 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $faq->summary() }}
                            </p>
                            <span class="mt-3 inline-flex items-center gap-1 text-xs text-zinc-400 dark:text-zinc-500">
                                <flux:icon.clock class="size-3.5" />
                                {{ $faq->readingTime() }} min read
                            </span>
                        </div>
                        <flux:icon.chevron-right class="mt-0.5 size-5 shrink-0 text-blue-400 transition-transform group-hover:translate-x-0.5" />
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>
