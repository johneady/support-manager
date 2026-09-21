<?php

use App\Models\Ticket;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
    $this->admin = User::factory()->create(['is_admin' => true]);
    $this->user = User::factory()->create();
});

describe('admin dashboard stat cards', function () {
    it('links open tickets to the filtered all tickets list', function () {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('tickets.all', ['status' => 'open']), false);
    });

    it('links needs response to the ticket queue', function () {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('tickets.queue'), false);
    });

    it('links recently resolved to the closed tickets list', function () {
        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('tickets.all', ['status' => 'closed']), false);
    });
});

describe('non-admin dashboard stat cards', function () {
    it('links open tickets to their own filtered list', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('tickets.index', ['status' => 'open']), false);
    });

    it('links resolved to their own closed list', function () {
        $this->actingAs($this->user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('tickets.index', ['status' => 'closed']), false);
    });
});

describe('status query string filtering', function () {
    it('applies the status query string on the all tickets page', function () {
        $open = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'Open Ticket Subject',
        ]);
        $closed = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Closed Ticket Subject',
        ]);

        Livewire::actingAs($this->admin)
            ->withQueryParams(['status' => 'open'])
            ->test('tickets.all-tickets')
            ->assertSet('statusFilter', 'open')
            ->assertSee($open->subject)
            ->assertDontSee($closed->subject);
    });

    it('applies the status query string on the my tickets page', function () {
        $open = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'My Open Ticket Subject',
        ]);
        $closed = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'My Closed Ticket Subject',
        ]);

        Livewire::actingAs($this->user)
            ->withQueryParams(['status' => 'closed'])
            ->test('tickets.ticket-list')
            ->assertSet('statusFilter', 'closed')
            ->assertSee($closed->subject)
            ->assertDontSee($open->subject);
    });

    it('shows every ticket when no status query string is present', function () {
        $open = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'Unfiltered Open Subject',
        ]);
        $closed = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Unfiltered Closed Subject',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.all-tickets')
            ->assertSet('statusFilter', null)
            ->assertSee($open->subject)
            ->assertSee($closed->subject);
    });
});

describe('recent ticket rows', function () {
    it('links each recent ticket to its queue modal', function () {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'subject' => 'Needs An Admin Reply',
        ]);

        $this->actingAs($this->admin)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee($ticket->subject)
            ->assertSee(route('tickets.queue', ['ticket' => $ticket->id]), false);
    });

    it('opens the edit modal when the queue is given a ticket id', function () {
        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'priority' => 'high',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue', ['ticket' => $ticket->id])
            ->assertSet('showEditModal', true)
            ->assertSet('editingTicketId', $ticket->id)
            ->assertSet('newStatus', 'open')
            ->assertSet('newPriority', 'high');
    });

    it('does not open the edit modal without a ticket id', function () {
        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->assertSet('showEditModal', false)
            ->assertSet('editingTicketId', null);
    });

    it('ignores a ticket id that does not exist', function () {
        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue', ['ticket' => 999999])
            ->assertOk()
            ->assertSet('showEditModal', false);
    });

    it('still forbids non-admins passing a ticket id', function () {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test('tickets.admin-queue', ['ticket' => $ticket->id])
            ->assertForbidden();
    });
});
