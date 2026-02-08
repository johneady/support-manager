<?php

use App\Models\TicketCategory;
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
    public ?int $editingCategoryId = null;

    #[Locked]
    public ?int $deletingCategoryId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:255|unique:ticket_categories,slug')]
    public string $slug = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('required|in:red,blue,green,amber,zinc,sky,emerald,rose')]
    public string $color = 'zinc';

    #[Validate('required|boolean')]
    public bool $isActive = true;

    #[Validate('required|integer|min:0')]
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
    public function categories(): LengthAwarePaginator
    {
        return TicketCategory::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('slug', 'like', '%'.$this->search.'%');
                });
            })
            ->ordered()
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'slug', 'description', 'color', 'isActive', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->color = 'zinc';
        $this->isActive = true;
        $this->sortOrder = 0;
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'slug', 'description', 'color', 'isActive', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function createCategory(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name',
            'slug' => 'required|string|max:255|unique:ticket_categories,slug',
            'color' => 'required|in:red,blue,green,amber,zinc,sky,emerald,rose',
            'isActive' => 'required|boolean',
            'sortOrder' => 'required|integer|min:0',
        ]);

        TicketCategory::create([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'color' => $this->color,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->categories);

        $this->closeCreateModal();
        session()->flash('success', 'Category created successfully.');
    }

    public function openEditModal(TicketCategory $category): void
    {
        $this->editingCategoryId = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->description = $category->description ?? '';
        $this->color = $category->color;
        $this->isActive = $category->is_active;
        $this->sortOrder = $category->sort_order;
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingCategoryId = null;
        $this->reset(['name', 'slug', 'description', 'color', 'isActive', 'sortOrder', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function updateCategory(): void
    {
        $this->validate([
            'name' => 'required|string|max:255|unique:ticket_categories,name,'.$this->editingCategoryId,
            'slug' => 'required|string|max:255|unique:ticket_categories,slug,'.$this->editingCategoryId,
            'color' => 'required|in:red,blue,green,amber,zinc,sky,emerald,rose',
            'isActive' => 'required|boolean',
            'sortOrder' => 'required|integer|min:0',
        ]);

        $category = TicketCategory::findOrFail($this->editingCategoryId);

        $category->update([
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description ?: null,
            'color' => $this->color,
            'is_active' => $this->isActive,
            'sort_order' => $this->sortOrder,
        ]);

        unset($this->categories);

        $this->closeEditModal();
        session()->flash('success', 'Category updated successfully.');
    }

    public function confirmDelete(TicketCategory $category): void
    {
        $this->deletingCategoryId = $category->id;
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingCategoryId = null;
        $this->showDeleteConfirmation = false;
    }

    public function deleteCategory(): void
    {
        $category = TicketCategory::findOrFail($this->deletingCategoryId);

        if ($category->tickets()->exists()) {
            $this->modalMessage = 'Cannot delete category because it has associated tickets.';
            $this->modalMessageType = 'danger';
            $this->cancelDelete();

            return;
        }

        $category->delete();

        unset($this->categories);

        $this->cancelDelete();
        session()->flash('success', 'Category deleted successfully.');
    }

    public function reorderCategories(int $id, int $position): void
    {
        $orderedIds = $this->categories->pluck('id')->toArray();

        $oldIndex = array_search($id, $orderedIds);

        if ($oldIndex === false || $oldIndex === $position) {
            return;
        }

        array_splice($orderedIds, $oldIndex, 1);
        array_splice($orderedIds, $position, 0, $id);

        foreach ($orderedIds as $index => $categoryId) {
            TicketCategory::where('id', $categoryId)->update(['sort_order' => $index]);
        }

        unset($this->categories);
    }
};
?>

<div class="space-y-6">
    @if(session('success'))
        <flux:callout variant="success" icon="check-circle" dismissible>
            {{ session('success') }}
        </flux:callout>
    @endif

    @if(session('error'))
        <flux:callout variant="danger" icon="exclamation-circle" dismissible>
            {{ session('error') }}
        </flux:callout>
    @endif

    {{-- Header Banner --}}
    <div class="rounded-lg bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="rounded-full bg-white/20 p-3">
                    <flux:icon.tag class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">Category Management</flux:heading>
                    <flux:text class="text-blue-100">Manage ticket categories for support requests</flux:text>
                </div>
            </div>
            <button wire:click="openCreateModal" class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50">
                <flux:icon.plus class="size-4" />
                Create Category
            </button>
        </div>
    </div>

    {{-- Search and Stats --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:badge color="blue" size="lg">{{ $this->categories->total() }}</flux:badge>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">total categories</span>
        </div>
        <div class="w-full sm:w-80">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name or slug..."
                icon="magnifying-glass"
            />
        </div>
    </div>

    @if($this->categories->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if($search)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No categories found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No categories match your search "{{ $search }}".</p>
            @else
                <flux:icon.tag class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No categories yet</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Get started by creating a new category.</p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="w-10 px-2 py-3"></th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Slug</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Color</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Active</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody wire:sort="reorderCategories" class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->categories as $category)
                        <tr wire:key="category-{{ $category->id }}" wire:sort:item="{{ $category->id }}" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50" wire:click="openEditModal({{ $category->id }})">
                            <td class="w-10 px-2 py-4 text-center" wire:click.stop>
                                <div wire:sort:handle class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                    <flux:icon.bars-3 class="mx-auto size-4" />
                                </div>
                            </td>
                            <td class="px-4 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $category->name }}
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category->slug }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                <flux:badge color="{{ $category->color }}" size="sm">
                                    {{ $category->color }}
                                </flux:badge>
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if($category->is_active)
                                    <flux:badge color="emerald" size="sm">Active</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">Inactive</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $category->created_at->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm" wire:click.stop>
                                <div class="flex items-center justify-end gap-2">
                                    <flux:button variant="ghost" size="sm" icon="pencil" wire:click="openEditModal({{ $category->id }})" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $category->id }})" class="text-red-600 hover:text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $this->categories->links() }}
        </div>
    @endif

    {{-- Create Category Modal --}}
    <flux:modal wire:model.self="showCreateModal" class="w-[40vw]! max-w-[40vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.tag class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Create Category</flux:heading>
                </div>
            </div>

            <form wire:submit="createCategory" class="space-y-4" x-data>
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input
                            wire:model="name"
                            placeholder="Category name"
                            x-on:input="$wire.set('slug', $el.value.toLowerCase().replace(/[^a-z0-9\s-]/g, '').trim().replace(/\s+/g, '-').replace(/-+/g, '-'))"
                        />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="URL-friendly identifier" />
                        <flux:text size="sm" class="text-zinc-500">Used in URLs and database queries. Must be unique.</flux:text>
                        <flux:error name="slug" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" placeholder="Optional description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Color</flux:label>
                        <flux:select wire:model="color">
                            <flux:select.option value="red">Red</flux:select.option>
                            <flux:select.option value="blue">Blue</flux:select.option>
                            <flux:select.option value="green">Green</flux:select.option>
                            <flux:select.option value="amber">Amber</flux:select.option>
                            <flux:select.option value="zinc">Zinc</flux:select.option>
                            <flux:select.option value="sky">Sky</flux:select.option>
                            <flux:select.option value="emerald">Emerald</flux:select.option>
                            <flux:select.option value="rose">Rose</flux:select.option>
                        </flux:select>
                        <flux:error name="color" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="isActive" label="Active" />
                        <flux:text size="sm" class="text-zinc-500">Inactive categories won't be shown in ticket creation form.</flux:text>
                    </flux:field>

                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="sortOrder" placeholder="0" min="0" />
                        <flux:text size="sm" class="text-zinc-500">Lower numbers appear first in lists.</flux:text>
                        <flux:error name="sortOrder" />
                    </flux:field>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Create Category
                    </flux:button>
                    <flux:button type="button" wire:click="closeCreateModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit Category Modal --}}
    <flux:modal wire:model.self="showEditModal" class="w-[40vw]! max-w-[40vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.tag class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Edit Category</flux:heading>
                </div>
            </div>

            @if($modalMessage)
                <flux:callout variant="{{ $modalMessageType }}" icon="{{ $modalMessageType === 'success' ? 'check-circle' : 'exclamation-circle' }}" dismissible>
                    {{ $modalMessage }}
                </flux:callout>
            @endif

            <form wire:submit="updateCategory" class="space-y-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" placeholder="Category name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Slug</flux:label>
                        <flux:input wire:model="slug" placeholder="URL-friendly identifier" />
                        <flux:text size="sm" class="text-zinc-500">Used in URLs and database queries. Must be unique.</flux:text>
                        <flux:error name="slug" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Description</flux:label>
                        <flux:textarea wire:model="description" placeholder="Optional description" rows="3" />
                        <flux:error name="description" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Color</flux:label>
                        <flux:select wire:model="color">
                            <flux:select.option value="red">Red</flux:select.option>
                            <flux:select.option value="blue">Blue</flux:select.option>
                            <flux:select.option value="green">Green</flux:select.option>
                            <flux:select.option value="amber">Amber</flux:select.option>
                            <flux:select.option value="zinc">Zinc</flux:select.option>
                            <flux:select.option value="sky">Sky</flux:select.option>
                            <flux:select.option value="emerald">Emerald</flux:select.option>
                            <flux:select.option value="rose">Rose</flux:select.option>
                        </flux:select>
                        <flux:error name="color" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="isActive" label="Active" />
                        <flux:text size="sm" class="text-zinc-500">Inactive categories won't be shown in ticket creation form.</flux:text>
                    </flux:field>

                    <flux:field>
                        <flux:label>Sort Order</flux:label>
                        <flux:input type="number" wire:model="sortOrder" placeholder="0" min="0" />
                        <flux:text size="sm" class="text-zinc-500">Lower numbers appear first in lists.</flux:text>
                        <flux:error name="sortOrder" />
                    </flux:field>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Update Category
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
                    <flux:heading size="lg" class="text-red-900 dark:text-red-100">Delete Category</flux:heading>
                </div>
            </div>

            <div class="rounded-lg bg-red-50 dark:bg-red-950/30 p-4 border border-red-200 dark:border-red-800">
                <flux:text class="text-red-700 dark:text-red-300">
                    Are you sure you want to delete this category? This action cannot be undone.
                </flux:text>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-red-200 dark:border-red-800">
                <flux:button wire:click="deleteCategory" variant="danger">
                    Delete Category
                </flux:button>
                <flux:button wire:click="cancelDelete" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
