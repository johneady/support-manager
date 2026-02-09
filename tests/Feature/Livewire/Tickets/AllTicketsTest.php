<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create();
});

describe('all tickets page', function () {
    it('requires authentication', function () {
        $this->get(route('tickets.all'))
            ->assertRedirect(route('login'));
    });

    it('requires admin access', function () {
        $this->actingAs($this->user)
            ->get(route('tickets.all'))
            ->assertForbidden();
    });

    it('shows all tickets for admin when status filter is cleared', function () {
        $openTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'Open Ticket For Admin',
        ]);
        $closedTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Closed Ticket For Admin',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', '')
            ->assertSee('Open Ticket For Admin')
            ->assertSee('Closed Ticket For Admin');
    });

    it('shows all tickets by default', function () {
        $openTicket = Ticket::factory()->create(['user_id' => $this->user->id, 'status' => 'open', 'subject' => 'Open Ticket Subject']);
        $closedTicket = Ticket::factory()->closed()->create(['user_id' => $this->user->id, 'subject' => 'Closed Ticket Subject']);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->assertSee('Closed Ticket Subject')
            ->assertSee('Open Ticket Subject');
    });

    it('search filters by subject', function () {
        $matchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Specific Search Term',
        ]);
        $nonMatchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Different Content',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', 'Specific Search')
            ->assertSee('Specific Search Term')
            ->assertDontSee('Different Content');
    });

    it('search filters by user name', function () {
        $searchUser = User::factory()->create(['name' => 'John Doe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith']);

        $matchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $searchUser->id,
            'subject' => 'Matching Name Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $otherUser->id,
            'subject' => 'Non Matching Name Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', 'John Doe')
            ->assertSee('Matching Name Ticket')
            ->assertDontSee('Non Matching Name Ticket');
    });

    it('search filters by user email', function () {
        $searchUser = User::factory()->create(['email' => 'john@example.com']);
        $otherUser = User::factory()->create(['email' => 'jane@example.com']);

        $matchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $searchUser->id,
            'subject' => 'Matching Email Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $otherUser->id,
            'subject' => 'Non Matching Email Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', 'john@example.com')
            ->assertSee('Matching Email Ticket')
            ->assertDontSee('Non Matching Email Ticket');
    });

    it('search filters by ticket reference number (id)', function () {
        $matchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Matching Reference Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Non Matching Reference Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', (string) $matchingTicket->id)
            ->assertSee('Matching Reference Ticket')
            ->assertDontSee('Non Matching Reference Ticket');
    });

    it('search filters by full ticket reference number', function () {
        $matchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Full Reference Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Non Full Reference Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', $matchingTicket->reference_number)
            ->assertSee('Full Reference Ticket')
            ->assertDontSee('Non Full Reference Ticket');
    });

    it('search filters by partial ticket reference number', function () {
        $ticket1 = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Ticket One',
        ]);
        $ticket2 = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Ticket Two',
        ]);
        $ticket3 = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Ticket Three',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', 'TX-1138-0000'.$ticket1->id)
            ->assertSee('Ticket One')
            ->assertDontSee('Ticket Two')
            ->assertDontSee('Ticket Three');
    });

    it('category filter works correctly', function () {
        $category1 = TicketCategory::where('slug', 'technical-support')->first();
        $category2 = TicketCategory::where('slug', 'general-inquiry')->first();

        $ticket1 = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'ticket_category_id' => $category1->id,
            'subject' => 'Technical Support Ticket',
        ]);
        $ticket2 = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'ticket_category_id' => $category2->id,
            'subject' => 'General Inquiry Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('categoryFilter', (string) $category1->id)
            ->assertSee('Technical Support Ticket')
            ->assertDontSee('General Inquiry Ticket');
    });

    it('status filter works correctly', function () {
        $openTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'Open Status Ticket',
        ]);
        $closedTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Closed Status Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', 'open')
            ->assertSee('Open Status Ticket')
            ->assertDontSee('Closed Status Ticket');
    });

    it('priority filter works correctly', function () {
        $highTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'subject' => 'High Priority Ticket',
        ]);
        $lowTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
            'subject' => 'Low Priority Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('priorityFilter', 'high')
            ->assertSee('High Priority Ticket')
            ->assertDontSee('Low Priority Ticket');
    });

    it('sorts by created_at descending by default', function () {
        $oldTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
            'subject' => 'Old Ticket',
        ]);
        $newTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
            'subject' => 'New Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', '')
            ->assertSeeInOrder(['New Ticket', 'Old Ticket']);
    });

    it('sorts by created_at ascending', function () {
        $oldTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDays(5),
            'subject' => 'Old Ticket',
        ]);
        $newTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'created_at' => now()->subDay(),
            'subject' => 'New Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', '')
            ->call('sortBy', 'created_at')
            ->call('sortBy', 'created_at')
            ->assertSeeInOrder(['New Ticket', 'Old Ticket']);
    });

    it('sorts by status', function () {
        $closedTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Closed Ticket',
        ]);
        $openTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'Open Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', '')
            ->call('sortBy', 'status')
            ->assertSeeInOrder(['Open Ticket', 'Closed Ticket']);
    });

    it('sorts by priority', function () {
        $highTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
            'subject' => 'High Priority',
        ]);
        $lowTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
            'subject' => 'Low Priority',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('sortBy', 'priority')
            ->assertSeeInOrder(['Low Priority', 'High Priority']);
    });

    it('view modal shows ticket details', function () {
        $ticket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Subject',
            'description' => 'Test Description',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->assertSet('viewingTicketId', $ticket->id)
            ->assertSet('showViewModal', true)
            ->assertSee('Test Subject')
            ->assertSee('Test Description');
    });

    it('view modal shows conversation replies', function () {
        $ticket = Ticket::factory()->closed()->create(['user_id' => $this->user->id]);
        $reply = TicketReply::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->admin->id,
            'body' => 'This is a test reply',
            'is_from_admin' => true,
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->assertSee('This is a test reply');
    });

    it('reopen functionality works for closed tickets', function () {
        $ticket = Ticket::factory()->closed()->create(['user_id' => $this->user->id]);

        expect($ticket->status)->toBe(TicketStatus::Closed);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket)
            ->call('reopenTicket', $ticket)
            ->assertHasNoErrors();

        expect($ticket->fresh()->status)->toBe(TicketStatus::Open)
            ->and($ticket->fresh()->closed_at)->toBeNull();
    });

    it('non-admin cannot access all tickets component', function () {
        Livewire::actingAs($this->user)
            ->test('tickets.all-tickets')
            ->assertForbidden();
    });

    it('pagination works correctly', function () {
        Ticket::factory()->count(15)->closed()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->assertSet('tickets', function ($tickets) {
                return $tickets->count() === 10 && $tickets->hasMorePages();
            });
    });

    it('close modal resets viewing ticket', function () {
        $ticket = Ticket::factory()->closed()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket)
            ->call('closeViewModal')
            ->assertSet('viewingTicketId', null)
            ->assertSet('showViewModal', false);
    });

    it('shows empty state when no tickets match filters', function () {
        Ticket::factory()->closed()->create(['user_id' => $this->user->id, 'subject' => 'Test Ticket']);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('search', 'NonExistent')
            ->assertSee('No tickets found');
    });

    it('shows empty state when no tickets exist', function () {
        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->set('statusFilter', '')
            ->assertSee('No tickets');
    });
});

describe('all tickets reply functionality', function () {
    it('can submit a reply to an open ticket', function () {
        Notification::fake();

        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', 'This is an admin reply from all tickets')
            ->call('submitReply')
            ->assertHasNoErrors();

        expect($ticket->replies()->count())->toBe(1)
            ->and($ticket->replies()->first()->body)->toBe('This is an admin reply from all tickets')
            ->and($ticket->replies()->first()->is_from_admin)->toBeTrue();

        Notification::assertSentTo($ticket->user, TicketReplyNotification::class);
    });

    it('can update ticket status when replying', function () {
        Notification::fake();

        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', 'Closing this ticket now')
            ->set('newStatus', 'closed')
            ->call('submitReply')
            ->assertHasNoErrors();

        expect($ticket->fresh()->status)->toBe(TicketStatus::Closed)
            ->and($ticket->fresh()->closed_at)->not->toBeNull();
    });

    it('can update ticket priority when replying', function () {
        Notification::fake();

        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'priority' => 'low',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', 'Escalating priority')
            ->set('newPriority', 'high')
            ->call('submitReply')
            ->assertHasNoErrors();

        expect($ticket->fresh()->priority)->toBe(TicketPriority::High);
    });

    it('validates reply body is required', function () {
        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', '')
            ->call('submitReply')
            ->assertHasErrors(['replyBody' => 'required']);
    });

    it('validates reply body minimum length', function () {
        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', 'Hi')
            ->call('submitReply')
            ->assertHasErrors(['replyBody' => 'min']);
    });

    it('shows reply form for open tickets in modal', function () {
        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->assertSee('Send Reply');
    });

    it('does not show reply form for closed tickets in modal', function () {
        $ticket = Ticket::factory()->closed()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->assertDontSee('Send Reply')
            ->assertSee('This ticket is closed.');
    });

    it('initializes status and priority from ticket when opening modal', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'priority' => 'high',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->assertSet('newStatus', 'open')
            ->assertSet('newPriority', 'high');
    });

    it('resets reply state when closing modal', function () {
        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->call('openViewModal', $ticket->id)
            ->set('replyBody', 'Some text')
            ->call('closeViewModal')
            ->assertSet('replyBody', '')
            ->assertSet('newStatus', null)
            ->assertSet('newPriority', null);
    });
});
