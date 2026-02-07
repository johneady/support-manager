# All Tickets Page Implementation Plan

## Overview
Create an "all tickets" page below the ticket queue that shows all tickets (opened or closed) in descending sequence. If they are closed, they can be opened, but all fields must be read only. Include search, category filter, status filter, and priority filter. By default just show closed, but all categories and all statuses. Make column headers sortable.

## Requirements

### Page Features
1. **Display all tickets** (both open and closed) in descending order (newest first)
2. **Default filters**:
   - Status: Closed only
   - Category: All categories
   - Priority: All priorities
3. **Search functionality**: Search by subject, user name, or email
4. **Filters**:
   - Category filter (dropdown with all active categories)
   - Status filter (dropdown: All, Open, Closed)
   - Priority filter (dropdown: All, Low, Medium, High)
5. **Sortable column headers**: Reference, Subject, Category, User, Status, Priority, Created
6. **Read-only ticket details**: When viewing closed tickets, all fields should be read-only
7. **Reopen functionality**: Closed tickets can be reopened by admins

### Access Control
- Only admin users can access this page (based on `TicketPolicy::viewAny`)

### Location
- Route: `/tickets/all` (below `/tickets/queue`)
- View: `resources/views/tickets/all.blade.php`
- Livewire component: `resources/views/livewire/tickets/⚡all-tickets.blade.php`

## Implementation Details

### 1. Create Livewire Volt Component
**File**: `resources/views/livewire/tickets/⚡all-tickets.blade.php`

**Component State**:
```php
public string $search = '';
public ?string $categoryFilter = null;
public ?string $statusFilter = 'closed'; // Default to closed
public ?string $priorityFilter = null;
public string $sortBy = 'created_at';
public string $sortDirection = 'desc';
public bool $showViewModal = false;
#[Locked] public ?int $viewingTicketId = null;
```

**Computed Properties**:
- `categories()`: All active ticket categories
- `tickets()`: Paginated tickets with filters and sorting applied
- `viewingTicket()`: The ticket currently being viewed in the modal

**Methods**:
- `openViewModal(Ticket $ticket)`: Open modal to view ticket details
- `closeViewModal()`: Close the view modal
- `reopenTicket(Ticket $ticket)`: Reopen a closed ticket
- `updatedSearch()`: Reset pagination when search changes
- `updatedCategoryFilter()`: Reset pagination when category filter changes
- `updatedStatusFilter()`: Reset pagination when status filter changes
- `updatedPriorityFilter()`: Reset pagination when priority filter changes
- `sortBy(string $column)`: Handle column sorting

**Query Logic**:
```php
Ticket::query()
    ->with(['user', 'ticketCategory', 'replies' => fn ($q) => $q->latest()->limit(1)])
    ->when($this->search, function ($query) {
        $query->where(function ($q) {
            $q->where('subject', 'like', '%'.$this->search.'%')
                ->orWhereHas('user', function ($userQuery) {
                    $userQuery->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                });
        });
    })
    ->when($this->categoryFilter, function ($query) {
        $query->where('ticket_category_id', $this->categoryFilter);
    })
    ->when($this->statusFilter, function ($query) {
        $query->where('status', $this->statusFilter);
    })
    ->when($this->priorityFilter, function ($query) {
        $query->where('priority', $this->priorityFilter);
    })
    ->orderBy($this->sortBy, $this->sortDirection)
    ->paginate(10);
```

### 2. Create Blade View
**File**: `resources/views/tickets/all.blade.php`

```blade
<x-layouts.app>
    @volt('tickets.all-tickets')
        {{-- Component content here --}}
    @endvolt
</x-layouts.app>
```

### 3. Add Route
**File**: `routes/web.php`

Add inside the auth middleware group:
```php
Route::view('tickets/all', 'tickets.all')->name('tickets.all');
```

### 4. Update Sidebar Navigation
**File**: `resources/views/layouts/app/sidebar.blade.php`

Add a new sidebar item after the Ticket Queue item:
```blade
<flux:sidebar.item icon="list" :href="route('tickets.all')" :current="request()->routeIs('tickets.all')" wire:navigate class="text-zinc-900 dark:text-zinc-300 hover:text-zinc-900 dark:hover:text-zinc-100 hover:bg-zinc-200 dark:hover:bg-zinc-800 font-medium">
    {{ __('All Tickets') }}
</flux:sidebar.item>
```

### 5. Create Tests
**File**: `tests/Feature/Livewire/Tickets/AllTicketsTest.php`

**Test Cases**:
1. Requires authentication
2. Requires admin access
3. Shows all tickets (open and closed) by default
4. Default filter shows only closed tickets
5. Search filters by subject
6. Search filters by user name
7. Search filters by user email
8. Category filter works correctly
9. Status filter works correctly
10. Priority filter works correctly
11. Sortable columns work (reference, subject, category, user, status, priority, created)
12. View modal shows ticket details
13. Reopen functionality works for closed tickets
14. Closed ticket fields are read-only in view modal
15. Pagination works correctly

## UI Design

### Header Banner
Similar to ticket queue but with different icon and text:
- Icon: `list` (Flux icon)
- Title: "All Tickets"
- Subtitle: "View and manage all support tickets"

### Table Layout
Columns (all sortable):
1. Reference (TX-1138-XXXXXX)
2. Subject (truncated to 50 chars)
3. Category (badge with category color)
4. User (name and email)
5. Status (badge with status color)
6. Priority (badge with priority color)
7. Created (relative time)

### Sortable Headers
Use clickable headers with sort indicators:
- Unsorted: Show column name only
- Ascending: Show up arrow icon
- Descending: Show down arrow icon

### View Modal
Read-only view of ticket details:
- Subject (read-only)
- Description (read-only, whitespace preserved)
- Status (badge, read-only)
- Priority (badge, read-only)
- Category (badge, read-only)
- Conversation history (all replies, read-only)
- Reopen button (only for closed tickets, admin-only)

## Technical Considerations

1. **Authorization**: Use `abort_unless(auth()->user()?->isAdmin(), 403)` in `mount()` method
2. **Performance**: Use eager loading for user, ticketCategory, and latest reply
3. **Pagination**: Use Laravel's built-in pagination (10 per page)
4. **Sorting**: Handle sorting in PHP, not database, for complex cases
5. **Dark Mode**: Ensure all UI elements support dark mode
6. **Responsive**: Use responsive classes for mobile/tablet views

## Dependencies
- Existing: `Ticket` model, `TicketCategory` model, `TicketStatus` enum, `TicketPriority` enum
- Flux UI components: `badge`, `button`, `input`, `select`, `modal`, `heading`, `text`, `icon`
- Livewire Volt: For component logic

## Success Criteria
- [ ] Page loads successfully for admin users
- [ ] Non-admin users are redirected/forbidden
- [ ] Default filter shows only closed tickets
- [ ] All filters work correctly
- [ ] Search functionality works
- [ ] Column headers are sortable
- [ ] View modal displays ticket details
- [ ] Closed tickets can be reopened
- [ ] All fields are read-only in view modal
- [ ] Pagination works
- [ ] All tests pass
- [ ] Code follows project conventions (Pint formatted)
