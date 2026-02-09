<?php

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
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

    public bool $showResendConfirmation = false;

    public string $modalMessage = '';

    public string $modalMessageType = '';

    #[Locked]
    public ?int $editingUserId = null;

    #[Locked]
    public ?int $deletingUserId = null;

    #[Locked]
    public ?int $resendingUserId = null;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|email|max:255')]
    public string $email = '';

    public bool $isAdmin = false;

    public function mount(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function users(): LengthAwarePaginator
    {
        return User::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
            })
            ->orderBy('name')
            ->paginate(10);
    }

    public function openCreateModal(): void
    {
        $this->reset(['name', 'email', 'isAdmin', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
        $this->showCreateModal = true;
    }

    public function closeCreateModal(): void
    {
        $this->showCreateModal = false;
        $this->reset(['name', 'email', 'isAdmin', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function createUser(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
        ]);

        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => null,
        ]);

        $user->is_admin = $this->isAdmin;
        $user->email_verified_at = now();
        $user->save();

        $user->sendInvitationNotification(auth()->user()->name);

        if ($this->isAdmin) {
            Cache::forget('admin_users');
        }

        unset($this->users);

        $this->closeCreateModal();
        session()->flash('success', 'User created successfully. An invitation email has been sent.');
    }

    public function openEditModal(User $user): void
    {
        $this->editingUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->isAdmin = $user->is_admin;
        $this->modalMessage = '';
        $this->modalMessageType = '';
        $this->resetValidation();
        $this->showEditModal = true;
    }

    public function closeEditModal(): void
    {
        $this->showEditModal = false;
        $this->editingUserId = null;
        $this->reset(['name', 'email', 'isAdmin', 'modalMessage', 'modalMessageType']);
        $this->resetValidation();
    }

    public function isEditingSelf(): bool
    {
        return $this->editingUserId === auth()->id();
    }

    public function updateUser(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$this->editingUserId,
        ]);

        $user = User::findOrFail($this->editingUserId);

        $user->update([
            'name' => $this->name,
            'email' => $this->email,
        ]);

        $adminChanged = ! $this->isEditingSelf() && $user->is_admin !== $this->isAdmin;

        if (! $this->isEditingSelf()) {
            $user->is_admin = $this->isAdmin;
            $user->save();
        }

        if ($adminChanged) {
            Cache::forget('admin_users');
        }

        unset($this->users);

        $this->closeEditModal();
        session()->flash('success', 'User updated successfully.');
    }

    public function confirmDelete(User $user): void
    {
        $this->deletingUserId = $user->id;
        $this->showDeleteConfirmation = true;
    }

    public function cancelDelete(): void
    {
        $this->deletingUserId = null;
        $this->showDeleteConfirmation = false;
    }

    public function confirmResendInvitation(User $user): void
    {
        if (! $user->hasPendingInvitation()) {
            session()->flash('error', 'This user does not have a pending invitation.');

            return;
        }

        $this->resendingUserId = $user->id;
        $this->showResendConfirmation = true;
    }

    public function cancelResendInvitation(): void
    {
        $this->resendingUserId = null;
        $this->showResendConfirmation = false;
    }

    public function deleteUser(): void
    {
        $user = User::findOrFail($this->deletingUserId);

        if ($user->id === auth()->id()) {
            $this->modalMessage = 'You cannot delete your own account.';
            $this->modalMessageType = 'danger';
            $this->cancelDelete();

            return;
        }

        $wasAdmin = $user->is_admin;

        $user->delete();

        if ($wasAdmin) {
            Cache::forget('admin_users');
        }

        unset($this->users);

        $this->cancelDelete();
        session()->flash('success', 'User deleted successfully.');
    }

    public function resendInvitation(): void
    {
        $user = User::findOrFail($this->resendingUserId);

        $user->resendInvitation(auth()->user()->name);

        $this->cancelResendInvitation();
        session()->flash('success', 'Invitation email has been resent successfully.');
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
                    <flux:icon.users class="size-8 text-white" />
                </div>
                <div>
                    <flux:heading size="2xl" class="text-white">User Management</flux:heading>
                    <flux:text class="text-blue-100">Manage user accounts and permissions</flux:text>
                </div>
            </div>
            <button wire:click="openCreateModal" class="flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-medium text-blue-600 transition-colors hover:bg-blue-50">
                <flux:icon.plus class="size-4" />
                Create User
            </button>
        </div>
    </div>

    {{-- Search and Stats --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-2">
            <flux:badge color="blue" size="lg">{{ $this->users->total() }}</flux:badge>
            <span class="text-sm text-zinc-600 dark:text-zinc-400">total users</span>
        </div>
        <div class="w-full sm:w-80">
            <flux:input
                wire:model.live.debounce.300ms="search"
                placeholder="Search name or email..."
                icon="magnifying-glass"
            />
        </div>
    </div>

    @if($this->users->isEmpty())
        <div class="text-center py-12 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-900">
            @if($search)
                <flux:icon.magnifying-glass class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No users found</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">No users match your search "{{ $search }}".</p>
            @else
                <flux:icon.users class="mx-auto h-12 w-12 text-zinc-400" />
                <h3 class="mt-2 text-sm font-semibold text-zinc-900 dark:text-white">No users yet</h3>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Get started by creating a new user.</p>
            @endif
        </div>
    @else
        <div class="overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Name</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Email</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Role</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Status</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Created</th>
                        <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700 bg-white dark:bg-zinc-900">
                    @foreach($this->users as $user)
                        <tr wire:key="user-{{ $user->id }}" wire:click="openEditModal({{ $user->id }})" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-4 text-sm font-medium text-zinc-900 dark:text-white">
                                {{ $user->name }}
                            </td>
                            <td class="px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $user->email }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if($user->is_admin)
                                    <flux:badge color="sky" size="sm">Admin</flux:badge>
                                @else
                                    <flux:badge color="zinc" size="sm">User</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4">
                                @if($user->getInvitationStatus() === 'pending')
                                    <flux:badge color="amber" size="sm">Pending</flux:badge>
                                @elseif($user->getInvitationStatus() === 'accepted')
                                    <flux:badge color="emerald" size="sm">Accepted</flux:badge>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                                {{ $user->created_at->format('M j, Y') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-4 text-right text-sm" wire:click.stop>
                                <div class="flex items-center justify-end gap-2">
                                    @if($user->hasPendingInvitation())
                                        <flux:button variant="ghost" size="sm" icon="paper-airplane" wire:click="confirmResendInvitation({{ $user->id }})" class="text-blue-600 hover:text-blue-700" />
                                    @endif
                                    <flux:button variant="ghost" size="sm" icon="pencil" wire:click="openEditModal({{ $user->id }})" />
                                    <flux:button variant="ghost" size="sm" icon="trash" wire:click="confirmDelete({{ $user->id }})" class="text-red-600 hover:text-red-700" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-6">
            {{ $this->users->links() }}
        </div>
    @endif

    {{-- Create User Modal --}}
    <flux:modal wire:model.self="showCreateModal" class="w-[40vw]! max-w-[40vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.user-plus class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Create User</flux:heading>
                </div>
            </div>

            <form wire:submit="createUser" class="space-y-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" placeholder="Full name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="isAdmin" label="Administrator" />
                        <flux:text size="sm" class="text-zinc-500">Administrators can manage users, FAQs, and view all tickets.</flux:text>
                    </flux:field>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Create User
                    </flux:button>
                    <flux:button type="button" wire:click="closeCreateModal" variant="ghost">
                        Cancel
                    </flux:button>
                </div>
            </form>
        </div>
    </flux:modal>

    {{-- Edit User Modal --}}
    <flux:modal wire:model.self="showEditModal" class="w-[40vw]! max-w-[40vw]! max-h-[90vh] overflow-y-auto">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.user class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Edit User</flux:heading>
                </div>
            </div>

            @if($modalMessage)
                <flux:callout variant="{{ $modalMessageType }}" icon="{{ $modalMessageType === 'success' ? 'check-circle' : 'exclamation-circle' }}" dismissible>
                    {{ $modalMessage }}
                </flux:callout>
            @endif

            <form wire:submit="updateUser" class="space-y-4">
                <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800 space-y-4">
                    <flux:field>
                        <flux:label>Name</flux:label>
                        <flux:input wire:model="name" placeholder="Full name" />
                        <flux:error name="name" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Email</flux:label>
                        <flux:input type="email" wire:model="email" placeholder="email@example.com" />
                        <flux:error name="email" />
                    </flux:field>

                    <flux:field>
                        <flux:checkbox wire:model="isAdmin" label="Administrator" :disabled="$this->isEditingSelf()" />
                        @if($this->isEditingSelf())
                            <flux:text size="sm" class="text-amber-600 dark:text-amber-400">You cannot change your own administrator status.</flux:text>
                        @else
                            <flux:text size="sm" class="text-zinc-500">Administrators can manage users, FAQs, and view all tickets.</flux:text>
                        @endif
                    </flux:field>
                </div>

                <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                    <flux:button type="submit" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                        Update User
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
                    <flux:heading size="lg" class="text-red-900 dark:text-red-100">Delete User</flux:heading>
                </div>
            </div>

            <div class="rounded-lg bg-red-50 dark:bg-red-950/30 p-4 border border-red-200 dark:border-red-800">
                <flux:text class="text-red-700 dark:text-red-300">
                    Are you sure you want to delete this user? This action cannot be undone and will remove all associated data.
                </flux:text>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-red-200 dark:border-red-800">
                <flux:button wire:click="deleteUser" variant="danger">
                    Delete User
                </flux:button>
                <flux:button wire:click="cancelDelete" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>

    {{-- Resend Invitation Confirmation Modal --}}
    <flux:modal wire:model.self="showResendConfirmation" class="w-[30vw]! max-w-[30vw]!">
        <div class="space-y-6">
            <div class="border-b border-blue-200 dark:border-blue-800 pb-4">
                <div class="flex items-center gap-3">
                    <flux:icon.paper-airplane class="size-6 text-blue-600 dark:text-blue-400" />
                    <flux:heading size="lg" class="text-blue-900 dark:text-blue-100">Resend Invitation</flux:heading>
                </div>
            </div>

            <div class="rounded-lg bg-blue-50 dark:bg-blue-950/30 p-4 border border-blue-200 dark:border-blue-800">
                <flux:text class="text-blue-700 dark:text-blue-300">
                    Are you sure you want to resend the invitation email to this user?
                </flux:text>
            </div>

            <div class="flex items-center gap-4 pt-4 border-t border-blue-200 dark:border-blue-800">
                <flux:button wire:click="resendInvitation" variant="primary" class="bg-blue-600 hover:bg-blue-700">
                    Resend Invitation
                </flux:button>
                <flux:button wire:click="cancelResendInvitation" variant="ghost">
                    Cancel
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
