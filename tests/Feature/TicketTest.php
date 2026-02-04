<?php

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\NewTicketNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
});

describe('ticket listing', function () {
    it('requires authentication', function () {
        $this->get(route('tickets.index'))
            ->assertRedirect(route('login'));
    });

    it('shows only user tickets', function () {
        $myTicket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'subject' => 'My Test Ticket Subject',
        ]);
        $otherTicket = Ticket::factory()->create([
            'subject' => 'Other User Ticket Subject',
        ]);

        $this->actingAs($this->user)
            ->get(route('tickets.index'))
            ->assertSuccessful()
            ->assertSee('My Test Ticket Subject')
            ->assertDontSee('Other User Ticket Subject');
    });
});

describe('ticket creation', function () {
    it('shows the create form', function () {
        $this->actingAs($this->user)
            ->get(route('tickets.create'))
            ->assertSuccessful();
    });

    it('creates a ticket and notifies admins', function () {
        Notification::fake();

        config(['support.admin_emails' => [$this->user->email]]);

        Livewire::actingAs($this->user)
            ->test('tickets.create-ticket')
            ->set('subject', 'Test Support Request')
            ->set('description', 'This is a detailed description of the issue I am experiencing.')
            ->set('priority', 'high')
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->user->id,
            'subject' => 'Test Support Request',
            'priority' => 'high',
            'status' => 'open',
        ]);

        Notification::assertSentTo($this->user, NewTicketNotification::class);
    });

    it('validates required fields', function () {
        Livewire::actingAs($this->user)
            ->test('tickets.create-ticket')
            ->set('subject', '')
            ->set('description', '')
            ->call('submit')
            ->assertHasErrors(['subject', 'description']);
    });

    it('validates minimum description length', function () {
        Livewire::actingAs($this->user)
            ->test('tickets.create-ticket')
            ->set('subject', 'Test')
            ->set('description', 'Short')
            ->call('submit')
            ->assertHasErrors(['description']);
    });
});

describe('ticket viewing', function () {
    it('shows ticket details to owner', function () {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->get(route('tickets.show', $ticket))
            ->assertSuccessful();
    });

    it('denies access to non-owner', function () {
        $ticket = Ticket::factory()->create();

        $this->actingAs($this->user)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    });

    it('shows conversation replies', function () {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);
        $reply = TicketReply::factory()->create([
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'This is my reply content',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.show-ticket', ['ticket' => $ticket])
            ->assertSee('This is my reply content');
    });
});

describe('ticket replies', function () {
    it('allows owner to reply to open tickets', function () {
        Notification::fake();

        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test('tickets.show-ticket', ['ticket' => $ticket])
            ->set('replyBody', 'This is my reply to the ticket.')
            ->call('submitReply')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'user_id' => $this->user->id,
            'body' => 'This is my reply to the ticket.',
            'is_from_admin' => false,
        ]);
    });

    it('validates reply body', function () {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        Livewire::actingAs($this->user)
            ->test('tickets.show-ticket', ['ticket' => $ticket])
            ->set('replyBody', 'Hi')
            ->call('submitReply')
            ->assertHasErrors(['replyBody']);
    });
});

describe('ticket model', function () {
    it('casts status to enum', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        expect($ticket->status)->toBeInstanceOf(TicketStatus::class)
            ->and($ticket->status)->toBe(TicketStatus::Open);
    });

    it('casts priority to enum', function () {
        $ticket = Ticket::factory()->create(['priority' => 'high']);

        expect($ticket->priority)->toBeInstanceOf(TicketPriority::class)
            ->and($ticket->priority)->toBe(TicketPriority::High);
    });

    it('can close and reopen', function () {
        $ticket = Ticket::factory()->create();

        $ticket->close();

        expect($ticket->fresh()->status)->toBe(TicketStatus::Closed)
            ->and($ticket->fresh()->closed_at)->not->toBeNull();

        $ticket->reopen();

        expect($ticket->fresh()->status)->toBe(TicketStatus::Open)
            ->and($ticket->fresh()->closed_at)->toBeNull();
    });

    it('has scopes for filtering', function () {
        $openTicket = Ticket::factory()->create(['status' => 'open']);
        $closedTicket = Ticket::factory()->closed()->create();

        expect(Ticket::open()->count())->toBe(1)
            ->and(Ticket::closed()->count())->toBe(1);
    });
});
