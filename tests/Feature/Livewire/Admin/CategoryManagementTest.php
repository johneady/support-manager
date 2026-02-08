<?php

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create(['is_admin' => false]);
});

describe('category management access', function () {
    it('requires authentication', function () {
        $this->get(route('admin.categories'))
            ->assertRedirect(route('login'));
    });

    it('denies access to non-admin users', function () {
        $this->actingAs($this->user)
            ->get(route('admin.categories'))
            ->assertForbidden();
    });

    it('allows access to admin users', function () {
        $this->actingAs($this->admin)
            ->get(route('admin.categories'))
            ->assertSuccessful();
    });
});

describe('category listing', function () {
    it('shows all categories to admin', function () {
        TicketCategory::factory()->create(['name' => 'Test Category One']);
        TicketCategory::factory()->create(['name' => 'Test Category Two']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->assertSee('Test Category One')
            ->assertSee('Test Category Two');
    });

    it('can search categories by name', function () {
        TicketCategory::factory()->create(['name' => 'Alice Category']);
        TicketCategory::factory()->create(['name' => 'Bob Category']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->set('search', 'Alice')
            ->assertSee('Alice Category')
            ->assertDontSee('Bob Category');
    });

    it('can search categories by slug', function () {
        TicketCategory::factory()->create(['slug' => 'test-slug-one']);
        TicketCategory::factory()->create(['slug' => 'test-slug-two']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->set('search', 'test-slug-one')
            ->assertSee('test-slug-one')
            ->assertDontSee('test-slug-two');
    });
});

describe('category creation', function () {
    it('can open create modal', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->assertSet('showCreateModal', true);
    });

    it('creates a new category', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'New Category')
            ->set('slug', 'new-category')
            ->set('description', 'Test description')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 5)
            ->call('createCategory')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'New Category',
            'slug' => 'new-category',
            'description' => 'Test description',
            'color' => 'blue',
            'is_active' => true,
            'sort_order' => 5,
        ]);
    });

    it('creates a category without description', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Category Without Description')
            ->set('slug', 'category-without-description')
            ->set('description', '')
            ->set('color', 'red')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Category Without Description',
            'slug' => 'category-without-description',
            'description' => null,
            'color' => 'red',
            'is_active' => true,
            'sort_order' => 0,
        ]);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', '')
            ->set('slug', '')
            ->set('color', 'zinc')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasErrors(['name', 'slug']);
    });

    it('validates email format', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Test')
            ->set('slug', 'test')
            ->set('description', 'Test')
            ->set('color', 'invalid-color')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasErrors(['color']);
    });

    it('validates unique name', function () {
        TicketCategory::factory()->create(['name' => 'Existing Category', 'slug' => 'existing-category']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Existing Category')
            ->set('slug', 'another-category')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasErrors(['name']);
    });

    it('validates unique slug', function () {
        TicketCategory::factory()->create(['name' => 'Test Category', 'slug' => 'existing-slug']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Another Category')
            ->set('slug', 'existing-slug')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasErrors(['slug']);
    });
});

describe('category editing', function () {
    it('can open edit modal', function () {
        $categoryToEdit = TicketCategory::factory()->create(['name' => 'Category To Edit']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->assertSet('showEditModal', true)
            ->assertSet('name', 'Category To Edit')
            ->assertSet('editingCategoryId', $categoryToEdit->id);
    });

    it('updates category details', function () {
        $categoryToEdit = TicketCategory::factory()->create([
            'name' => 'Old Name',
            'slug' => 'old-slug',
            'description' => 'Old Description',
            'color' => 'red',
            'is_active' => true,
            'sort_order' => 1,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->set('name', 'New Name')
            ->set('slug', 'new-slug')
            ->set('description', 'New Description')
            ->set('color', 'blue')
            ->set('isActive', false)
            ->set('sortOrder', 5)
            ->call('updateCategory')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $categoryToEdit->id,
            'name' => 'New Name',
            'slug' => 'new-slug',
            'description' => 'New Description',
            'color' => 'blue',
            'is_active' => false,
            'sort_order' => 5,
        ]);
    });

    it('updates category without description', function () {
        $categoryToEdit = TicketCategory::factory()->create([
            'name' => 'Category With Description',
            'slug' => 'category-with-description',
            'description' => 'Has Description',
            'color' => 'green',
            'is_active' => true,
            'sort_order' => 2,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->set('name', 'Updated Name')
            ->set('slug', 'updated-slug')
            ->set('description', '')
            ->set('color', 'amber')
            ->set('isActive', true)
            ->set('sortOrder', 3)
            ->call('updateCategory')
            ->assertHasNoErrors()
            ->assertSet('showEditModal', false);

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $categoryToEdit->id,
            'name' => 'Updated Name',
            'slug' => 'updated-slug',
            'description' => null,
            'color' => 'amber',
            'is_active' => true,
            'sort_order' => 3,
        ]);
    });

    it('validates unique name excluding current category', function () {
        $categoryToEdit = TicketCategory::factory()->create(['name' => 'Current Category', 'slug' => 'current-slug']);
        TicketCategory::factory()->create(['name' => 'Existing Category', 'slug' => 'existing-slug']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->set('name', 'Existing Category')
            ->set('slug', 'another-category')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('updateCategory')
            ->assertHasErrors(['name']);
    });

    it('validates unique slug excluding current category', function () {
        $categoryToEdit = TicketCategory::factory()->create(['name' => 'Test Category', 'slug' => 'current-slug']);
        TicketCategory::factory()->create(['name' => 'Another Category', 'slug' => 'existing-slug']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->set('name', 'Another Category')
            ->set('slug', 'existing-slug')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('updateCategory')
            ->assertHasErrors(['slug']);
    });

    it('allows keeping same name', function () {
        $categoryToEdit = TicketCategory::factory()->create(['name' => 'Same Name', 'slug' => 'same-slug']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $categoryToEdit->id)
            ->set('name', 'Same Name')
            ->set('slug', 'same-slug')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('updateCategory')
            ->assertHasNoErrors();
    });
});

describe('category deletion', function () {
    it('can open delete confirmation', function () {
        $categoryToDelete = TicketCategory::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('confirmDelete', $categoryToDelete->id)
            ->assertSet('showDeleteConfirmation', true)
            ->assertSet('deletingCategoryId', $categoryToDelete->id);
    });

    it('deletes category', function () {
        $categoryToDelete = TicketCategory::factory()->create(['name' => 'Delete Me']);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('confirmDelete', $categoryToDelete->id)
            ->call('deleteCategory')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseMissing('ticket_categories', ['name' => 'Delete Me']);
    });

    it('cannot delete category with tickets', function () {
        $categoryToDelete = TicketCategory::factory()->create(['name' => 'Category With Tickets']);
        Ticket::factory()->create(['ticket_category_id' => $categoryToDelete->id]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('confirmDelete', $categoryToDelete->id)
            ->call('deleteCategory')
            ->assertSet('showDeleteConfirmation', false);

        $this->assertDatabaseHas('ticket_categories', ['name' => 'Category With Tickets']);
    });

    it('can cancel delete', function () {
        $categoryToDelete = TicketCategory::factory()->create();

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('confirmDelete', $categoryToDelete->id)
            ->call('cancelDelete')
            ->assertSet('showDeleteConfirmation', false)
            ->assertSet('deletingCategoryId', null);

        $this->assertDatabaseHas('ticket_categories', ['id' => $categoryToDelete->id]);
    });
});

describe('category active/inactive toggle', function () {
    it('can create inactive category', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Inactive Category')
            ->set('slug', 'inactive-category')
            ->set('description', 'Test')
            ->set('color', 'zinc')
            ->set('isActive', false)
            ->set('sortOrder', 0)
            ->call('createCategory')
            ->assertHasNoErrors()
            ->assertSet('showCreateModal', false);

        $this->assertDatabaseHas('ticket_categories', [
            'name' => 'Inactive Category',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);
    });

    it('can activate inactive category', function () {
        $inactiveCategory = TicketCategory::factory()->create([
            'name' => 'Inactive Category',
            'slug' => 'inactive-category',
            'is_active' => false,
        ]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openEditModal', $inactiveCategory->id)
            ->set('name', 'Inactive Category')
            ->set('slug', 'inactive-category')
            ->set('description', 'Test')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 0)
            ->call('updateCategory')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_categories', [
            'id' => $inactiveCategory->id,
            'is_active' => true,
        ]);
    });
});

describe('category sort order', function () {
    it('creates categories with custom sort order', function () {
        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'First Category')
            ->set('slug', 'first-category')
            ->set('color', 'red')
            ->set('isActive', true)
            ->set('sortOrder', 10)
            ->call('createCategory')
            ->assertHasNoErrors();

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Second Category')
            ->set('slug', 'second-category')
            ->set('color', 'blue')
            ->set('isActive', true)
            ->set('sortOrder', 5)
            ->call('createCategory')
            ->assertHasNoErrors();

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('openCreateModal')
            ->set('name', 'Third Category')
            ->set('slug', 'third-category')
            ->set('color', 'green')
            ->set('isActive', true)
            ->set('sortOrder', 1)
            ->call('createCategory')
            ->assertHasNoErrors();

        $categories = TicketCategory::ordered()->get();

        expect($categories[0]->name)->toBe('Third Category');
        expect($categories[1]->name)->toBe('Second Category');
        expect($categories[2]->name)->toBe('First Category');
    });

    it('reorders categories via drag and drop', function () {
        $catA = TicketCategory::factory()->create(['name' => 'Alpha', 'sort_order' => 0]);
        $catB = TicketCategory::factory()->create(['name' => 'Bravo', 'sort_order' => 1]);
        $catC = TicketCategory::factory()->create(['name' => 'Charlie', 'sort_order' => 2]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('reorderCategories', $catC->id, 0);

        expect(TicketCategory::ordered()->pluck('name')->toArray())
            ->toBe(['Charlie', 'Alpha', 'Bravo']);
    });

    it('reorders categories moving item down', function () {
        $catA = TicketCategory::factory()->create(['name' => 'Alpha', 'sort_order' => 0]);
        $catB = TicketCategory::factory()->create(['name' => 'Bravo', 'sort_order' => 1]);
        $catC = TicketCategory::factory()->create(['name' => 'Charlie', 'sort_order' => 2]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('reorderCategories', $catA->id, 2);

        expect(TicketCategory::ordered()->pluck('name')->toArray())
            ->toBe(['Bravo', 'Charlie', 'Alpha']);
    });

    it('does not change order when position is the same', function () {
        $catA = TicketCategory::factory()->create(['name' => 'Alpha', 'sort_order' => 0]);
        $catB = TicketCategory::factory()->create(['name' => 'Bravo', 'sort_order' => 1]);

        Livewire::actingAs($this->admin)
            ->test('admin.category-management')
            ->call('reorderCategories', $catA->id, 0);

        expect(TicketCategory::ordered()->pluck('name')->toArray())
            ->toBe(['Alpha', 'Bravo']);
    });
});
