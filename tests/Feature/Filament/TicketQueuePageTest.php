<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

describe('Admin Ticket Queue Page', function () {
    it('is accessible to admins', function () {
        $this->actingAs($this->admin)
            ->get('/tickets/queue')
            ->assertSuccessful();
    });

    it('is not accessible to non-admins', function () {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/tickets/queue')
            ->assertForbidden();
    });

    it('shows only open tickets', function () {
        $openTicket = Ticket::factory()->create(['status' => 'open']);
        $closedTicket = Ticket::factory()->create(['status' => 'closed']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->assertSee($openTicket->subject)
            ->assertDontSee($closedTicket->subject);
    });

    it('displays tickets needing response first', function () {
        $ticketWithAdminReply = Ticket::factory()->create(['status' => 'open', 'priority' => 'high']);
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticketWithAdminReply->id]);

        $ticketNeedingResponse = Ticket::factory()->create(['status' => 'open', 'priority' => 'high']);

        $this->actingAs($this->admin);

        // Ticket needing response should appear first in the list
        Livewire::test('tickets.admin-queue')
            ->assertSeeInOrder([$ticketNeedingResponse->subject, $ticketWithAdminReply->subject]);
    });

    it('displays tickets by priority', function () {
        $lowTicket = Ticket::factory()->create(['status' => 'open', 'priority' => 'low']);
        $highTicket = Ticket::factory()->create(['status' => 'open', 'priority' => 'high']);
        $mediumTicket = Ticket::factory()->create(['status' => 'open', 'priority' => 'medium']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->assertSeeInOrder([$highTicket->subject, $mediumTicket->subject, $lowTicket->subject]);
    });

    it('allows admin to reply to a ticket', function () {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->call('startReply', $ticket->id)
            ->set('replyBody', 'This is a test reply from admin')
            ->set('newStatus', 'open')
            ->set('newPriority', 'medium')
            ->call('submitReply')
            ->assertHasNoErrors();

        expect($ticket->fresh()->replies)->toHaveCount(1)
            ->and($ticket->fresh()->replies->first()->body)->toBe('This is a test reply from admin')
            ->and($ticket->fresh()->replies->first()->is_from_admin)->toBeTrue();

        Notification::assertSentTo($ticket->user, TicketReplyNotification::class);
    });

    it('updates ticket status when replying', function () {
        Notification::fake();

        $ticket = Ticket::factory()->create(['status' => 'open', 'priority' => 'high']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->call('startReply', $ticket->id)
            ->set('replyBody', 'Closing this ticket now')
            ->set('newStatus', 'closed')
            ->set('newPriority', 'low')
            ->call('submitReply');

        $ticket->refresh();

        expect($ticket->status->value)->toBe('closed')
            ->and($ticket->priority->value)->toBe('low')
            ->and($ticket->closed_at)->not->toBeNull();
    });

    it('validates reply body is required', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->call('startReply', $ticket->id)
            ->set('replyBody', '')
            ->set('newStatus', 'open')
            ->set('newPriority', 'medium')
            ->call('submitReply')
            ->assertHasErrors(['replyBody']);
    });

    it('validates reply body minimum length', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->call('startReply', $ticket->id)
            ->set('replyBody', 'Hi')
            ->set('newStatus', 'open')
            ->set('newPriority', 'medium')
            ->call('submitReply')
            ->assertHasErrors(['replyBody']);
    });

    it('can cancel reply', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->call('startReply', $ticket->id)
            ->assertSet('replyingToTicketId', $ticket->id)
            ->call('cancelReply')
            ->assertSet('replyingToTicketId', null)
            ->assertSet('replyBody', '');
    });

    it('shows count of tickets needing response', function () {
        Ticket::factory()->create(['status' => 'open']);
        Ticket::factory()->create(['status' => 'open']);
        $ticketWithAdminReply = Ticket::factory()->create(['status' => 'open']);
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticketWithAdminReply->id]);

        $this->actingAs($this->admin);

        Livewire::test('tickets.admin-queue')
            ->assertSee('2')
            ->assertSee('tickets need a response');
    });
});

describe('Ticket needsResponse scope', function () {
    it('includes tickets with no replies', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);

        $needsResponse = Ticket::query()->open()->needsResponse()->get();

        expect($needsResponse)->toHaveCount(1)
            ->and($needsResponse->first()->id)->toBe($ticket->id);
    });

    it('includes tickets where last reply is from customer', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);
        TicketReply::factory()->fromCustomer()->create(['ticket_id' => $ticket->id]);

        $needsResponse = Ticket::query()->open()->needsResponse()->get();

        expect($needsResponse)->toHaveCount(1);
    });

    it('excludes tickets where last reply is from admin', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticket->id]);

        $needsResponse = Ticket::query()->open()->needsResponse()->get();

        expect($needsResponse)->toHaveCount(0);
    });

    it('includes ticket if customer replied after admin', function () {
        $ticket = Ticket::factory()->create(['status' => 'open']);
        TicketReply::factory()->fromAdmin()->create([
            'ticket_id' => $ticket->id,
            'created_at' => now()->subHour(),
        ]);
        TicketReply::factory()->fromCustomer()->create([
            'ticket_id' => $ticket->id,
            'created_at' => now(),
        ]);

        $needsResponse = Ticket::query()->open()->needsResponse()->get();

        expect($needsResponse)->toHaveCount(1);
    });
});

describe('Ticket needsResponse method', function () {
    it('returns true for ticket with no replies', function () {
        $ticket = Ticket::factory()->create();

        expect($ticket->needsResponse())->toBeTrue();
    });

    it('returns true when last reply is from customer', function () {
        $ticket = Ticket::factory()->create();
        TicketReply::factory()->fromCustomer()->create(['ticket_id' => $ticket->id]);

        expect($ticket->needsResponse())->toBeTrue();
    });

    it('returns false when last reply is from admin', function () {
        $ticket = Ticket::factory()->create();
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticket->id]);

        expect($ticket->needsResponse())->toBeFalse();
    });
});
