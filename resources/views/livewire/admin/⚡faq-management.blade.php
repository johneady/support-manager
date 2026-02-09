<?php

use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showDeleteConfirmation = false;

    #[Locked]
    public ?int $deletingFaqId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function faqs(): LengthAwarePaginator
    {
        return Faq::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('question', 'like', '%'.$this->search.'%')
                        ->orWhere('answer', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->ordered()
            ->paginate(10);
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingFaqId = $id;
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingFaqId = null;
        $this->showDeleteConfirmation = false;
    }

    public function deleteFaq(): void
    {
        $faq = Faq::findOrFail($this->deletingFaqId);
        $faq->delete();

        unset($this->faqs);

        $this->cancelDelete();
        session()->flash('success', 'FAQ deleted successfully.');
    }

    public function togglePublished(int $id): void
    {
        $faq = Faq::findOrFail($id);
        $faq->update(['is_published' => ! $faq->is_published]);
        unset($this->faqs);
    }

    public function reorderFaqs(int $id, int $position): void
    {
        $orderedIds = $this->faqs->pluck('id')->toArray();

        $oldIndex = array_search($id, $orderedIds);

        if ($oldIndex === false || $oldIndex === $position) {
            return;
        }

        array_splice($orderedIds, $oldIndex, 1);
        array_splice($orderedIds, $position, 0, $id);

        foreach ($orderedIds as $index => $faqId) {
            Faq::where('id', $faqId)->update(['sort_order' => $index]);
        }

        unset($this->faqs);
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    {{-- Header Banner --}}
    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-full bg-white/20 p-3">
                    <flux:icon.question-mark-circle class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">FAQ Management</flux:heading>
                    <flux:text class="text-blue-100">Manage frequently asked questions</flux:text>
                </div>
            </div>
            <a href="{{ route('admin.faqs.create') }}" wire:navigate class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50">
                <flux:icon.plus class="size-4" />
                Create FAQ
            </a>
        </div>
    </div>

    {{-- Search and Stats --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:badge color="blue" size="lg">{{ $this->faqs->total() }}</flux:badge>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">total FAQs</span>
        </div>
        <div class="w-full sm:w-80">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search questions, answers, or slugs..."
                icon="magnifying-glass"
            />
        </div>
    </div>

    @if($this->faqs->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if($search)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No FAQs found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No FAQs match your search "{{ $search }}".</p>
            @else
                <flux:icon.question-mark-circle class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No FAQs yet</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Get started by creating a new FAQ.</p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="w-10 px-2 py-3"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Question</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Slug</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody wire:sort="reorderFaqs" class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->faqs as $faq)
                        <tr wire:key="faq-{{ $faq->id }}" wire:sort:item="{{ $faq->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" onclick="window.location='{{ route('admin.faqs.edit', $faq->id) }}'">
                            <td class="w-10 px-2 py-4 text-center" onclick="event.stopPropagation()">
                                <div wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                    <flux:icon.bars-3 class="mx-auto size-4" />
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ Str::limit($faq->question, 80) }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ Str::limit($faq->answer, 100) }}
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $faq->slug }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4" onclick="event.stopPropagation()">
                                <button wire:click="togglePublished({{ $faq->id }})" class="cursor-pointer">
                                    @if($faq->is_published)
                                        <flux:badge color="green" size="sm">Published</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Draft</flux:badge>
                                    @endif
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm" onclick="event.stopPropagation()">
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button href="{{ route('admin.faqs.edit', $faq->id) }}" wire:navigate variant="ghost" size="sm" icon="pencil" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $faq->id }})" class="text-red-600 hover:text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $this->faqs->links() }}
        </div>
    @endif

    {{-- Delete Confirmation Modal --}}
    <flux:modal wire:model.self="showDeleteConfirmation" class="w-[30vw]! max-w-[30vw]!">
        <div class="space-y-6">
            <div class="border-b border-red-200 dark:border-red-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.exclamation-triangle class="size-6 text-red-600 dark:text-red-400" />
                    <flux:heading size="lg" class="text-red-900 dark:text-red-100">Delete FAQ</flux:heading>
                </div>
            </div>

            <div class="rounded-lg bg-red-50 dark:bg-red-950/30 p-4 border border-red-200 dark:border-red-800">
                <flux:text class="text-red-700 dark:text-red-300">
                    Are you sure you want to delete this FAQ? This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-red-200 dark:border-red-800">
                <flux:button wire:click="deleteFaq" variant="danger">
                    Delete FAQ
                </flux:button>
                <flux:button wire:click="cancelDelete" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
