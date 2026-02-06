<?php

use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

describe('user management access', function () {
    it('requires authentication', function () {
        $this->get(route('admin.users'))
            ->assertRedirect(route('login'));
    });

    it('denies access to non-admin users', function () {
        $this->actingAs($this->user)
            ->get(route('admin.users'))
            ->assertForbidden();
    });

    it('allows access to admin users', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.users'))
            ->assertSuccessful();
    });
});

describe('user listing', function () {
    it('shows all users to admin', function () {
        User::factory()->create(['name' => 'Test User One']);
        User::factory()->create(['name' => 'Test User Two']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->assertSee('Test User One')
            ->assertSee('Test User Two');
    });

    it('can search users by name', function () {
        User::factory()->create(['name' => 'Alice Smith']);
        User::factory()->create(['name' => 'Bob Jones']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->set('search', 'Alice')
            ->assertSee('Alice Smith')
            ->assertDontSee('Bob Jones');
    });

    it('can search users by email', function () {
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->set('search', 'alice@example')
            ->assertSee('Alice')
            ->assertDontSee('Bob');
    });
});

describe('user creation', function () {
    it('can open create modal', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    });

    it('creates a new user', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', 'New User')
            ->set('email', 'newuser@example.com')
            ->set('password', 'password123')
            ->set('isAdmin', false)
            ->call('createUser')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('users', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'is_admin' => false,
        ]);
    });

    it('creates an admin user', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', 'New Admin')
            ->set('email', 'newadmin@example.com')
            ->set('password', 'password123')
            ->set('isAdmin', true)
            ->call('createUser')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'name' => 'New Admin',
            'email' => 'newadmin@example.com',
            'is_admin' => true,
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', '')
            ->set('email', '')
            ->set('password', '')
            ->call('createUser')
            ->assertHasErrors(['name', 'email', 'password']);
    });

    it('validates email format', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('email', 'invalid-email')
            ->set('password', 'password123')
            ->call('createUser')
            ->assertHasErrors(['email']);
    });

    it('validates unique email', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('email', 'existing@example.com')
            ->set('password', 'password123')
            ->call('createUser')
            ->assertHasErrors(['email']);
    });

    it('validates password length', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('email', 'test@example.com')
            ->set('password', 'short')
            ->call('createUser')
            ->assertHasErrors(['password']);
    });
});

describe('user editing', function () {
    it('can open edit modal', function () {
        $userToEdit = User::factory()->create(['name' => 'User To Edit']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->assertSet('showEditModal', true)
            ->assertSet('name', 'User To Edit')
            ->assertSet('editingUserId', $userToEdit->id);
    });

    it('updates user details', function () {
        $userToEdit = User::factory()->create([
            'name' => 'Old Name',
            'email' => 'old@example.com',
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->set('name', 'New Name')
            ->set('email', 'new@example.com')
            ->call('updateUser')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('users', [
            'id' => $userToEdit->id,
            'name' => 'New Name',
            'email' => 'new@example.com',
        ]);
    });

    it('updates user admin status', function () {
        $userToEdit = User::factory()->create(['is_admin' => false]);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->set('isAdmin', true)
            ->call('updateUser')
            ->assertHasNoErrors();

        expect($userToEdit->fresh()->is_admin)->toBeTrue();
    });

    it('allows blank password to keep existing', function () {
        $userToEdit = User::factory()->create();
        $originalPassword = $userToEdit->password;

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->set('name', 'Updated Name')
            ->set('password', '')
            ->call('updateUser')
            ->assertHasNoErrors();

        expect($userToEdit->fresh()->password)->toBe($originalPassword);
    });

    it('validates unique email excluding current user', function () {
        $userToEdit = User::factory()->create(['email' => 'current@example.com']);
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->set('email', 'existing@example.com')
            ->call('updateUser')
            ->assertHasErrors(['email']);
    });

    it('allows keeping same email', function () {
        $userToEdit = User::factory()->create(['email' => 'same@example.com']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $userToEdit->id)
            ->set('email', 'same@example.com')
            ->call('updateUser')
            ->assertHasNoErrors();
    });

    it('cannot change own admin status', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $this->admin->id)
            ->set('isAdmin', false)
            ->call('updateUser')
            ->assertHasNoErrors();

        expect($this->admin->fresh()->is_admin)->toBeTrue();
    });

    it('can change other user admin status', function () {
        $otherUser = User::factory()->create(['is_admin' => false]);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('openEditModal', $otherUser->id)
            ->set('isAdmin', true)
            ->call('updateUser')
            ->assertHasNoErrors();

        expect($otherUser->fresh()->is_admin)->toBeTrue();
    });
});

describe('user deletion', function () {
    it('can open delete confirmation', function () {
        $userToDelete = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('confirmDelete', $userToDelete->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('deletingUserId', $userToDelete->id);
    });

    it('deletes user', function () {
        $userToDelete = User::factory()->create(['email' => 'delete-me@example.com']);

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('confirmDelete', $userToDelete->id)
            ->call('deleteUser')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseMissing('users', ['email' => 'delete-me@example.com']);
    });

    it('cannot delete self', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('confirmDelete', $this->admin->id)
            ->call('deleteUser')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    });

    it('can cancel delete', function () {
        $userToDelete = User::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.user-management')
            ->call('confirmDelete', $userToDelete->id)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deletingUserId', null);

        $this->assertDatabaseHas('users', ['id' => $userToDelete->id]);
    });
});
