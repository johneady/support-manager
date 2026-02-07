# Ticket Category CRUD Implementation Plan

## Overview
Transform ticket categories from a fixed enum to a fully manageable database-backed system. Admins will be able to create, read, update, and delete categories through a dedicated CRUD page similar to the user management page.

## Key Requirements
1. Create a `ticket_categories` database table
2. Create a `TicketCategory` model with relationship to tickets
3. Create admin CRUD page for category management (similar to user management)
4. Update ticket creation to use categories from database
5. Default new tickets to "Technical Support" category
6. Create appropriate tests
7. Update seeder to create 3 initial categories:
   - Technical Support
   - Sales Support
   - General Inquiry

## Implementation Steps

### 1. Create Database Migration for ticket_categories Table
**File:** `database/migrations/2026_02_06_230000_create_ticket_categories_table.php`

Create a new migration with:
- `id` (primary key)
- `name` (string, unique, required) - Display name
- `slug` (string, unique, required) - URL-friendly identifier
- `description` (text, nullable) - Optional description
- `color` (string, required) - Badge color for Flux UI
- `is_active` (boolean, default true) - Soft disable instead of delete
- `sort_order` (integer, default 0) - For custom ordering
- `timestamps`

### 2. Create TicketCategory Model
**File:** `app/Models/TicketCategory.php`

Create model with:
- `$fillable` properties: name, slug, description, color, is_active, sort_order
- `$casts`: is_active to boolean, sort_order to integer
- `tickets()` hasMany relationship to Ticket
- `scopeActive()` for filtering active categories
- `scopeOrdered()` for sorting by sort_order

### 3. Create TicketCategory Factory
**File:** `database/factories/TicketCategoryFactory.php`

Create factory with:
- `name` - fake()->word()
- `slug` - Str::slug(fake()->word())
- `description` - fake()->sentence() (nullable)
- `color` - fake()->randomElement(['red', 'blue', 'green', 'amber', 'zinc', 'sky', 'emerald', 'rose'])
- `is_active` - true
- `sort_order` - 0

### 4. Create TicketCategorySeeder
**File:** `database/seeders/TicketCategorySeeder.php`

Create seeder with 3 initial categories:
1. Technical Support (slug: technical-support, color: red, sort_order: 1)
2. Sales Support (slug: sales-support, color: blue, sort_order: 2)
3. General Inquiry (slug: general-inquiry, color: zinc, sort_order: 3)

### 5. Update Existing tickets Migration
**File:** `database/migrations/2026_02_04_013418_create_tickets_table.php`

Since this is a new project, directly modify the existing migration:
- Replace the `category` string column with `ticket_category_id` foreign key
- Add foreign key constraint to `ticket_categories.id`
- Set `ticket_category_id` to nullable to allow for future flexibility

**Note:** No migration data strategy is needed since this is a new project.

### 6. Update Ticket Model
**File:** `app/Models/Ticket.php`

Update model:
- Remove `'category'` from `$fillable`
- Add `'ticket_category_id'` to `$fillable`
- Remove `'category' => TicketCategory::class` from `casts()`
- Add `ticketCategory()` belongsTo relationship to TicketCategory
- Update any scopes or methods that reference category

### 7. Create Category Management Livewire Component
**File:** `resources/views/livewire/admin/⚡category-management.blade.php`

Create component following user management pattern:
- `search` property for filtering
- `showCreateModal`, `showEditModal`, `showDeleteConfirmation` booleans
- `editingCategoryId`, `deletingCategoryId` locked properties
- Form properties: name, description, color, is_active, sort_order
- CRUD methods: openCreateModal, closeCreateModal, createCategory, openEditModal, closeEditModal, updateCategory, confirmDelete, cancelDelete, deleteCategory
- `categories()` computed property with pagination and search
- `mount()` method with admin authorization check

### 8. Create Admin View for Category Management
**File:** `resources/views/admin/categories.blade.php`

Simple wrapper view:
```blade
<x-layouts::app :title="__('Category Management')">
    <div class="flex h-full w-full flex-1 flex-col gap-6 rounded-xl">
        <livewire:admin.category-management />
    </div>
</x-layouts::app>
```

### 9. Add Route for Category Management
**File:** `routes/web.php`

Add route inside admin group:
```php
Route::view('admin/categories', 'admin.categories')->name('admin.categories');
```

### 10. Update Sidebar
**File:** `resources/views/layouts/app/sidebar.blade.php`

Add category management link for admins:
```blade
<flux:sidebar.item icon="tag" :href="route('admin.categories')" :current="request()->routeIs('admin.categories')" wire:navigate class="...">
    {{ __('Categories') }}
</flux:sidebar.item>
```

### 11. Update Create Ticket Component
**File:** `resources/views/livewire/tickets/⚡create-ticket.blade.php`

Update component:
- Remove `use App\Enums\TicketCategory;`
- Add `#[Computed] categories()` property to fetch active categories
- Change `$category` from string to int (ticket_category_id)
- Update validation to check category exists in database
- Set default category to "Technical Support" (find by slug)
- Update select dropdown to iterate over database categories
- Update ticket creation to use `ticket_category_id`

### 12. Update Ticket List Component
**File:** `resources/views/livewire/tickets/⚡ticket-list.blade.php`

Update component:
- Remove `use App\Enums\TicketCategory;`
- Update `newCategory` property type from string to int
- Update validation to check category exists
- Set default to "Technical Support" category ID
- Update category select dropdown to use database categories
- Update category badge display to use `ticketCategory->name` and `ticketCategory->color`

### 13. Update Admin Queue Component
**File:** `resources/views/livewire/tickets/⚡admin-queue.blade.php`

Update component:
- Remove `use App\Enums\TicketCategory;`
- Update category badge display to use `ticketCategory->name` and `ticketCategory->color`

### 14. Update TicketFactory
**File:** `database/factories/TicketFactory.php`

Update factory:
- Remove `use App\Enums\TicketCategory;`
- Update `definition()` to use `ticket_category_id` with random category from database
- Or use `TicketCategory::inRandomOrder()->first()?->id`

### 15. Update TicketSeeder
**File:** `database/seeders/TicketSeeder.php`

Update seeder:
- Remove `use App\Enums\TicketCategory;`
- Get categories from database instead of using enum
- Use category IDs when creating tickets

### 16. Update DatabaseSeeder
**File:** `database/seeders/DatabaseSeeder.php`

Add to `$this->call()`:
```php
TicketCategorySeeder::class,
```

### 17. Create Tests for Category Management
**File:** `tests/Feature/Livewire/Admin/CategoryManagementTest.php`

Create comprehensive tests following UserManagementTest pattern:
- Access control tests (requires auth, admin only)
- Listing tests (shows all categories, search by name)
- Creation tests (open modal, create category, validate required fields, validate unique name)
- Editing tests (open modal, update details, validate unique name excluding current)
- Deletion tests (confirm delete, delete category, prevent deletion if tickets exist)
- Active/inactive toggle tests
- Sort order tests

### 18. Update Ticket Tests
**File:** `tests/Feature/Livewire/Tickets/ShowTicketTest.php` and `tests/Feature/TicketTest.php`

Update tests to:
- Use category IDs instead of enum values
- Create categories in database before creating tickets
- Assert category relationships work correctly

### 19. Run Pint
Format all modified code:
```bash
vendor/bin/pint --dirty
```

## Database Schema Changes

### New Table: ticket_categories
```php
Schema::create('ticket_categories', function (Blueprint $table) {
    $table->id();
    $table->string('name')->unique();
    $table->string('slug')->unique();
    $table->text('description')->nullable();
    $table->string('color');
    $table->boolean('is_active')->default(true);
    $table->integer('sort_order')->default(0);
    $table->timestamps();
});
```

### Modified Table: tickets
```php
// Before
$table->string('category')->default('general_inquiry');

// After
$table->foreignId('ticket_category_id')->nullable()->constrained('ticket_categories')->nullOnDelete();
```

## Migration Data Strategy

Since this is a new project, no data migration is needed. The existing tickets migration will be modified to use `ticket_category_id` foreign key from the start.

## UI Changes

### Category Management Page (admin/categories)
Follow user management pattern with:
- Header banner with category count
- Search input
- Table with columns: Name, Slug, Color, Active, Sort Order, Created, Actions
- Create/Edit modals with form fields
- Delete confirmation modal

### Create Ticket Form
```blade
<flux:select wire:model="ticketCategoryId" label="Category">
    @foreach($this->categories as $category)
        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
    @endforeach
</flux:select>
```

### Category Badge Display
```blade
<flux:badge color="{{ $ticket->ticketCategory->color }}" size="sm">
    {{ $ticket->ticketCategory->name }}
</flux:badge>
```

## Files to Create (6 files)
1. `database/migrations/2026_02_06_230000_create_ticket_categories_table.php`
2. `app/Models/TicketCategory.php`
3. `database/factories/TicketCategoryFactory.php`
4. `database/seeders/TicketCategorySeeder.php`
5. `resources/views/livewire/admin/⚡category-management.blade.php`
6. `resources/views/admin/categories.blade.php`

## Files to Modify (11 files)
1. `database/migrations/2026_02_04_013418_create_tickets_table.php` (modify to use foreign key)
2. `app/Models/Ticket.php`
3. `routes/web.php`
4. `resources/views/layouts/app/sidebar.blade.php`
5. `resources/views/livewire/tickets/⚡create-ticket.blade.php`
6. `resources/views/livewire/tickets/⚡ticket-list.blade.php`
7. `resources/views/livewire/tickets/⚡admin-queue.blade.php`
8. `database/factories/TicketFactory.php`
9. `database/seeders/TicketSeeder.php`
10. `database/seeders/DatabaseSeeder.php`
11. `tests/Feature/Livewire/Tickets/ShowTicketTest.php`
12. `tests/Feature/TicketTest.php`

## Files to Create (Tests) (1 file)
1. `tests/Feature/Livewire/Admin/CategoryManagementTest.php`

## Testing Considerations
- Verify category management requires admin access
- Verify categories can be created, updated, and deleted
- Verify category uniqueness is enforced
- Verify categories can be marked inactive
- Verify tickets use categories from database
- Verify default category is "Technical Support"
- Verify category badges display correctly
- Verify category ordering works
- Verify deletion protection when tickets exist
- Verify search functionality in category management
- Verify all existing tests still pass

## Execution Order
1. Create migration for ticket_categories table
2. Create TicketCategory model
3. Create TicketCategory factory
4. Create TicketCategorySeeder
5. Update existing tickets migration to use ticket_category_id foreign key
6. Update Ticket model
7. Create category management Livewire component
8. Create admin view
9. Add route
10. Update sidebar
11. Update ticket creation components
12. Update ticket display components
13. Update factory and seeder
14. Update DatabaseSeeder
15. Run migrations and seeders
16. Create tests
17. Run Pint
18. Run full test suite
