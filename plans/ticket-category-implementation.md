# Ticket Category Implementation Plan

## Overview
Add a category field to tickets with 3 predefined categories that users must select when creating a ticket. Display the category in both the `/tickets` and `/tickets/queue` pages.

## Ticket Categories
The 3 categories to be implemented:
1. **Technical Issue** - Technical problems, errors, bugs, or system issues
2. **Feature Request** - Suggestions for new features or improvements
3. **General Inquiry** - General questions, feedback, or other inquiries

## Implementation Steps

### 1. Create TicketCategory Enum
**File:** `app/Enums/TicketCategory.php`

Follow the same pattern as [`TicketPriority`](app/Enums/TicketPriority.php:1) and [`TicketStatus`](app/Enums/TicketStatus.php:1):
- Define 3 cases: `TechnicalIssue`, `FeatureRequest`, `GeneralInquiry`
- Implement `label()` method to return display names
- Implement `color()` method to return Flux UI badge colors:
  - Technical Issue: `red` (indicating urgency)
  - Feature Request: `blue` (indicating enhancement)
  - General Inquiry: `zinc` (indicating neutral)

### 2. Update Tickets Migration
**File:** `database/migrations/2026_02_04_013418_create_tickets_table.php`

Add a `category` column to the tickets table:
- Type: `string`
- Default value: `'general_inquiry'` (most common case)
- Position: After the `priority` column

Since this is a new application, we can modify the existing migration directly.

### 3. Update Ticket Model
**File:** `app/Models/Ticket.php`

Make two changes:
- Add `'category'` to the `$fillable` array
- Add `'category' => TicketCategory::class` to the `casts()` method

### 4. Update Create Ticket Component
**File:** `resources/views/livewire/tickets/⚡create-ticket.blade.php`

Add category selection to the ticket creation form:
- Add a new public property `$category` with validation
- Add a `flux:select` component for category selection
- Include the category when creating the ticket in the `submit()` method

### 5. Update Ticket List Component
**File:** `resources/views/livewire/tickets/⚡ticket-list.blade.php`

Add category display to the tickets table:
- Add a new table header column "Category"
- Add a new table cell column displaying the category badge
- Position: Between "Subject" and "Status" columns

### 6. Update Admin Queue Component
**File:** `resources/views/livewire/tickets/⚡admin-queue.blade.php`

Add category display to the admin queue table:
- Add a new table header column "Category"
- Add a new table cell column displaying the category badge
- Position: Between "Subject" and "User" columns

### 7. Update Ticket Factory
**File:** `database/factories/TicketFactory.php`

Add category to the factory definition:
- Add `'category' => fake()->randomElement(TicketCategory::cases())` to the `definition()` method

### 8. Run Migration
Execute the migration to add the category column to the database:
```bash
php artisan migrate:fresh
```
Since this is a new application, we can use `migrate:fresh` to rebuild the database.

### 9. Run Tests
Run the test suite to verify all changes work correctly:
```bash
php artisan test --compact
```

## Database Schema Changes

### Before
```php
Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('subject');
    $table->text('description');
    $table->string('status')->default('open');
    $table->string('priority')->default('medium');
    $table->timestamp('closed_at')->nullable();
    $table->timestamps();
});
```

### After
```php
Schema::create('tickets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('subject');
    $table->text('description');
    $table->string('status')->default('open');
    $table->string('priority')->default('medium');
    $table->string('category')->default('general_inquiry');
    $table->timestamp('closed_at')->nullable();
    $table->timestamps();
});
```

## UI Changes

### Create Ticket Form
Add a category selection dropdown after the priority field:
```blade
<flux:select wire:model="category" label="Category">
    @foreach(TicketCategory::cases() as $category)
        <flux:select.option value="{{ $category->value }}">{{ $category->label() }}</flux:select.option>
    @endforeach
</flux:select>
```

### Ticket List Table (/tickets)
Add a category column between Subject and Status:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
```

### Admin Queue Table (/tickets/queue)
Add a category column between Subject and User:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Category</th>
```

## Category Badge Display
```blade
<flux:badge color="{{ $ticket->category->color() }}" size="sm">
    {{ $ticket->category->label() }}
</flux:badge>
```

## Files to Create/Modify

### Create (1 file)
- `app/Enums/TicketCategory.php`

### Modify (6 files)
- `database/migrations/2026_02_04_013418_create_tickets_table.php`
- `app/Models/Ticket.php`
- `resources/views/livewire/tickets/⚡create-ticket.blade.php`
- `resources/views/livewire/tickets/⚡ticket-list.blade.php`
- `resources/views/livewire/tickets/⚡admin-queue.blade.php`
- `database/factories/TicketFactory.php`

## Testing Considerations
- Verify category is required when creating a ticket
- Verify category displays correctly in both ticket list and admin queue
- Verify category badge colors are correct
- Verify factory generates tickets with random categories
- Verify existing tests still pass after changes
