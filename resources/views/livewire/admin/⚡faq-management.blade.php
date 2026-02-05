<?php

use App\Models\Faq;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showCreateModal = false;

    public bool $showEditModal = false;

    public bool $showDeleteConfirmation = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $editingFaqId = null;

    #[Locked]
    public ?int $deletingFaqId = null;

    #[Validate('required|string|max:500')]
    public string $question = '';

    #[Validate('required|string')]
    public string $answer = '';

    public bool $isPublished = false;

    #[Validate('integer|min:0')]
    public int $sortOrder = 0;

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
                        ->orWhere('answer', 'like', '%'.$this->search.'%');
                });
            })
            ->ordered()
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->reset(['question', 'answer', 'isPublished', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->sortOrder = Faq::query()->max('sort_order') + 1;
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['question', 'answer', 'isPublished', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function createFaq(): void
    {
        $this->validate();

        Faq::create([
            'question' => $this->question,
            'answer' => $this->answer,
            'is_published' => $this->isPublished,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->faqs);

        $this->closeCreateModal();
        session()->flash('success', 'FAQ created successfully.');
    }

    public function openEditModal(Faq $faq): void
    {
        $this->editingFaqId = $faq->id;
        $this->question = $faq->question;
        $this->answer = $faq->answer;
        $this->isPublished = $faq->is_published;
        $this->sortOrder = $faq->sort_order;
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingFaqId = null;
        $this->reset(['question', 'answer', 'isPublished', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function updateFaq(): void
    {
        $this->validate();

        $faq = Faq::findOrFail($this->editingFaqId);

        $faq->update([
            'question' => $this->question,
            'answer' => $this->answer,
            'is_published' => $this->isPublished,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->faqs);

        $this->closeEditModal();
        session()->flash('success', 'FAQ updated successfully.');
    }

    public function confirmDelete(Faq $faq): void
    {
        $this->deletingFaqId = $faq->id;
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

    public function togglePublished(Faq $faq): void
    {
        $faq->update(['is_published' => ! $faq->is_published]);
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
    <div class="rounded-lg bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-8 text-white shadow-lg">
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
            <button wire:click="openCreateModal" class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50">
                <flux:icon.plus class="size-4" />
                Create FAQ
            </button>
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
                placeholder="Search questions or answers..."
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
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Order</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Question</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->faqs as $faq)
                        <tr wire:key="faq-{{ $faq->id }}" wire:click="openEditModal({{ $faq->id }})" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $faq->sort_order }}
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm font-medium text-zinc-900 dark:text-white">
                                    {{ Str::limit($faq->question, 80) }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                    {{ Str::limit($faq->answer, 100) }}
                                </div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4" wire:click.stop>
                                <button wire:click="togglePublished({{ $faq->id }})" class="cursor-pointer">
                                    @if($faq->is_published)
                                        <flux:badge color="green" size="sm">Published</flux:badge>
                                    @else
                                        <flux:badge color="zinc" size="sm">Draft</flux:badge>
                                    @endif
                                </button>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm" wire:click.stop>
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil" wire:click="openEditModal({{ $faq->id }})" />
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

    {{-- Create FAQ Modal --}}
    <flux:modal wire:model.self="showCreateModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.question-mark-circle class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Create FAQ</flux:heading>
                </div>
            </div>

            <form wire:submit="createFaq" class="space-y-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Question</flux:label>
                        <flux:input wire:model="question" placeholder="What is the frequently asked question?" />
                        <flux:error name="question" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Answer</flux:label>
                        <flux:textarea wire:model="answer" placeholder="Provide a detailed answer..." rows="5" />
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

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Create FAQ
                    </flux:button>
                    <flux:button type="button" wire:click="closeCreateModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit FAQ Modal --}}
    <flux:modal wire:model.self="showEditModal" class="w-[50vw]! max-w-[50vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.question-mark-circle class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Edit FAQ</flux:heading>
                </div>
            </div>

            @if($modalMessage)
                <flux:callout variant="{{ $modalMessageType }}" icon="{{ $modalMessageType === 'success' ? 'check-circle' : 'exclamation-circle' }}" dismissible>
                    {{ $modalMessage }}
                </flux:callout>
            @endif

            <form wire:submit="updateFaq" class="space-y-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Question</flux:label>
                        <flux:input wire:model="question" placeholder="What is the frequently asked question?" />
                        <flux:error name="question" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Answer</flux:label>
                        <flux:textarea wire:model="answer" placeholder="Provide a detailed answer..." rows="5" />
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

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Update FAQ
                    </flux:button>
                    <flux:button type="button" wire:click="closeEditModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

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
