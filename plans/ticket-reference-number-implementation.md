# Ticket Reference Number Implementation Plan

## Overview
Add a fancy 6-7 digit reference number (format: `TX-1138-000001`) to all tickets, displayed prominently in emails and on the customer's My Tickets page.

## Format Specification
- **Pattern**: `TX-1138-000001`
- **Components**:
  - `TX` = Ticket prefix (static)
  - `1138` = Static identifier (never changes)
  - `000001` = Zero-padded 6-digit ticket ID

## Implementation Steps

### 1. Add Reference Number Accessor to Ticket Model
**File**: [`app/Models/Ticket.php`](app/Models/Ticket.php)

Add an accessor method that generates the reference number on-the-fly from the ticket ID:

```php
public function getReferenceNumberAttribute(): string
{
    return sprintf('TX-1138-%06d', $this->id);
}
```

**Benefits**:
- No database migration needed
- Always stays in sync with the ID
- Easy to change the format later if needed

---

### 2. Update Email Notifications

#### 2.1 NewTicketNotification (Admin notification)
**File**: [`app/Notifications/NewTicketNotification.php`](app/Notifications/NewTicketNotification.php)

**Changes**:
- Add reference number to email subject line
- Display reference number prominently in the email body

**Before**:
```php
->subject("New Support Ticket: {$this->ticket->subject}")
```

**After**:
```php
->subject("New Support Ticket: {$this->ticket->reference_number} - {$this->ticket->subject}")
```

**Add to body** (after greeting, before ticket details):
```php
->line("**Reference Number:** {$this->ticket->reference_number}")
```

---

#### 2.2 TicketReplyNotification (Customer and Admin notification)
**File**: [`app/Notifications/TicketReplyNotification.php`](app/Notifications/TicketReplyNotification.php)

**Changes**:
- Add reference number to email subject line for both customer and admin emails
- Display reference number prominently in the email body for both

**Customer Email (isFromAdmin = true)**:
```php
->subject("Reply to Ticket {$this->reply->ticket->reference_number}: {$ticket->subject}")
```

**Add to body**:
```php
->line("**Reference Number:** {$ticket->reference_number}")
```

**Admin Email (isFromAdmin = false)**:
```php
->subject("Customer Reply to Ticket {$this->reply->ticket->reference_number}: {$ticket->subject}")
```

**Add to body**:
```php
->line("**Reference Number:** {$ticket->reference_number}")
```

---

#### 2.3 TicketAutoClosedNotification (Customer notification)
**File**: [`app/Notifications/TicketAutoClosedNotification.php`](app/Notifications/TicketAutoClosedNotification.php)

**Changes**:
- Add reference number to email subject line
- Display reference number prominently in the email body

**Before**:
```php
->subject("Your Ticket Has Been Closed: {$this->ticket->subject}")
```

**After**:
```php
->subject("Ticket {$this->ticket->reference_number} Closed: {$this->ticket->subject}")
```

**Add to body** (after greeting):
```php
->line("**Reference Number:** {$this->ticket->reference_number}")
```

---

### 3. Update Customer-Facing Views

#### 3.1 My Tickets Page (Ticket List)
**File**: [`resources/views/livewire/tickets/⚡ticket-list.blade.php`](resources/views/livewire/tickets/⚡ticket-list.blade.php)

**Changes**:
- Replace `{{ $ticket->id }}` with `{{ $ticket->reference_number }}` in the `#` column (line 269)
- Update column header from `#` to `Reference` (line 256)

**Before**:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
```

**After**:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reference</th>
```

**Before**:
```blade
<td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
    {{ $ticket->id }}
</td>
```

**After**:
```blade
<td class="whitespace-nowrap px-4 py-4 text-sm font-mono text-zinc-600 dark:text-zinc-400">
    {{ $ticket->reference_number }}
</td>
```

---

#### 3.2 Individual Ticket Page (Show Ticket)
**File**: [`resources/views/livewire/tickets/⚡show-ticket.blade.php`](resources/views/livewire/tickets/⚡show-ticket.blade.php)

**Changes**:
- Display reference number prominently near the ticket subject
- Replace "Ticket #{{ $ticket->id }}" with the reference number

**Before**:
```blade
<p class="text-sm text-zinc-500 dark:text-zinc-400">
    Ticket #{{ $ticket->id }} &middot; Created {{ $ticket->created_at->diffForHumans() }}
</p>
```

**After**:
```blade
<p class="text-sm text-zinc-500 dark:text-zinc-400">
    <span class="font-mono text-zinc-600 dark:text-zinc-400">{{ $ticket->reference_number }}</span>
    &middot; Created {{ $ticket->created_at->diffForHumans() }}
</p>
```

---

### 4. Update Admin Views

#### 4.1 Admin Queue Page
**File**: [`resources/views/livewire/tickets/⚡admin-queue.blade.php`](resources/views/livewire/tickets/⚡admin-queue.blade.php)

**Changes**:
- Replace `{{ $ticket->id }}` with `{{ $ticket->reference_number }}` in the `#` column (line 223)
- Update column header from `#` to `Reference` (line 210)
- Update modal header from "Ticket #{{ $this->editingTicket->id }}" to reference number (line 280)

**Before**:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">#</th>
```

**After**:
```blade
<th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500 dark:text-zinc-400">Reference</th>
```

**Before**:
```blade
<td class="whitespace-nowrap px-4 py-4 text-sm text-zinc-500 dark:text-zinc-400">
    {{ $ticket->id }}
</td>
```

**After**:
```blade
<td class="whitespace-nowrap px-4 py-4 text-sm font-mono text-zinc-600 dark:text-zinc-400">
    {{ $ticket->reference_number }}
</td>
```

**Before**:
```blade
<flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
    Ticket #{{ $this->editingTicket->id }}
</flux:heading>
```

**After**:
```blade
<flux:heading size="lg" class="text-blue-900 dark:text-blue-100">
    Ticket {{ $this->editingTicket->reference_number }}
</flux:heading>
```

---

### 5. Write Tests

#### 5.1 Unit Test for Reference Number Accessor
**File**: `tests/Unit/TicketTest.php` (create if doesn't exist)

Test that the reference number accessor generates the correct format:
```php
it('generates correct reference number format', function () {
    $ticket = Ticket::factory()->create(['id' => 1]);
    expect($ticket->reference_number)->toBe('TX-1138-000001');
});

it('generates zero-padded reference numbers', function () {
    $ticket = Ticket::factory()->create(['id' => 42]);
    expect($ticket->reference_number)->toBe('TX-1138-000042');
});

it('generates reference numbers for large IDs', function () {
    $ticket = Ticket::factory()->create(['id' => 123456]);
    expect($ticket->reference_number)->toBe('TX-1138-123456');
});
```

#### 5.2 Feature Test for Email Notifications
Test that reference numbers appear correctly in email notifications:
```php
it('includes reference number in new ticket notification', function () {
    $ticket = Ticket::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);

    Notification::fake();

    Notification::send([$admin], new NewTicketNotification($ticket));

    Notification::assertSentTo(
        [$admin],
        NewTicketNotification::class,
        function ($notification) use ($ticket) {
            $mail = $notification->toMail($admin);
            return str($mail->subject)->contains($ticket->reference_number) &&
                   str($mail->introLines[1])->contains($ticket->reference_number);
        }
    );
});
```

---

### 6. Run Code Formatter
Run Laravel Pint to ensure code formatting follows project standards:
```bash
vendor/bin/pint --dirty
```

---

### 7. Run Tests
Run the tests to ensure everything works correctly:
```bash
php artisan test --compact tests/Unit/TicketTest.php
php artisan test --compact tests/Feature/NotificationTest.php
```

---

### 8. PreviewMailCommand (No Changes Needed)
**File**: [`app/Console/Commands/PreviewMailCommand.php`](app/Console/Commands/PreviewMailCommand.php)

**Note**: This command creates test data for email previews. Since the reference number is implemented as an accessor on the Ticket model, PreviewMailCommand will automatically use the reference number when notifications are sent. No changes are needed to this file.

The test tickets created in this command (with ID 99999) will automatically generate the reference number `TX-1138-099999` when the accessor is called.

---

## Summary of Files to Modify

1. **Model**: [`app/Models/Ticket.php`](app/Models/Ticket.php) - Add reference number accessor
2. **Notifications**:
   - [`app/Notifications/NewTicketNotification.php`](app/Notifications/NewTicketNotification.php)
   - [`app/Notifications/TicketReplyNotification.php`](app/Notifications/TicketReplyNotification.php)
   - [`app/Notifications/TicketAutoClosedNotification.php`](app/Notifications/TicketAutoClosedNotification.php)
3. **Views**:
   - [`resources/views/livewire/tickets/⚡ticket-list.blade.php`](resources/views/livewire/tickets/⚡ticket-list.blade.php)
   - [`resources/views/livewire/tickets/⚡show-ticket.blade.php`](resources/views/livewire/tickets/⚡show-ticket.blade.php)
   - [`resources/views/livewire/tickets/⚡admin-queue.blade.php`](resources/views/livewire/tickets/⚡admin-queue.blade.php)
4. **Tests** (create new):
   - `tests/Unit/TicketTest.php`
5. **No changes needed**:
   - [`app/Console/Commands/PreviewMailCommand.php`](app/Console/Commands/PreviewMailCommand.php) - Will automatically use the reference number accessor

---

## Design Decisions

### Why an Accessor Instead of a Database Column?
- **No migration needed**: Less risk of data corruption
- **Always in sync**: Reference number is always consistent with the ID
- **Flexible**: Easy to change the format later without touching the database
- **Performance**: Minimal overhead (simple string formatting)

### Why Monospace Font for Reference Numbers?
- Makes the reference number easier to read and distinguish from other text
- Helps users identify the reference number quickly
- Common practice for reference/case numbers in support systems

### Why Include in Email Subject Lines?
- Helps users quickly identify which ticket the email is about
- Makes email filtering and searching easier for users
- Professional appearance for customer communications

---

## Future Enhancements (Optional)

1. **Search by Reference Number**: Add ability to search tickets by reference number in admin queue
2. **Copy to Clipboard**: Add a button to copy the reference number to clipboard
3. **QR Code**: Generate a QR code for the reference number (useful for support agents)
4. **Customizable Format**: Make the reference number format configurable via environment variables
