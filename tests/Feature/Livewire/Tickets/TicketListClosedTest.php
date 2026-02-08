<?php

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketClosedNotification;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
    $this->user = User::factory()->create();
});

describe('ticket closing', function () {
    it('shows close button for open tickets', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->assertSee('Close');
    });

    it('does not show close button for closed tickets', function () {
        $ticket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        $html = Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->html();

        // Check that the close button is not in the table for closed tickets
        expect($html)->not->toContain('wire:click.stop="openCloseModal('.$ticket->id.')"');
    });

    it('opens close confirmation modal when close button is clicked', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->assertSet('showCloseModal', true)
            ->assertSet('closingTicketId', $ticket->id)
            ->assertSee('Close Ticket')
            ->assertSee('Are you sure you want to close this ticket?')
            ->assertSee('Warning')
            ->assertSee('This action cannot be undone');
    });

    it('closes close modal when close button is clicked', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->assertSet('showCloseModal', true)
            ->call('closeCloseModal')
            ->assertSet('showCloseModal', false)
            ->assertSet('closingTicketId', null);
    });

    it('closes ticket and adds system reply', function () {
        Notification::fake();

        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->call('closeTicket');

        $ticket->refresh();

        expect($ticket->status->value)->toBe('closed');
        expect($ticket->closed_at)->not->toBeNull();

        $systemReply = $ticket->replies()->latest()->first();
        expect($systemReply)->not->toBeNull();
        expect($systemReply->user_id)->toBeNull();
        expect($systemReply->body)->toBe("Closed by {$this->user->name}");
        expect($systemReply->is_from_admin)->toBeTrue();
    });

    it('sends closing notification to user', function () {
        Notification::fake();

        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->call('closeTicket');

        Notification::assertSentTo(
            $this->user,
            TicketClosedNotification::class,
            function (TicketClosedNotification $notification) use ($ticket) {
                return $notification->ticket->id === $ticket->id
                    && $notification->closedByName === $this->user->name;
            }
        );
    });

    it('prevents user from closing another users ticket', function () {
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $otherUser->id,
            'subject' => 'Other User Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->assertForbidden();
    });

    it('redirects to tickets index after closing', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->call('closeTicket')
            ->assertRedirect(route('tickets.index'));
    });

    it('flashes success message after closing', function () {
        $ticket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Test Ticket',
        ]);

        Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->call('openCloseModal', $ticket)
            ->call('closeTicket');

        expect(session('success'))->toBe('Your ticket has been closed successfully.');
    });

    it('does not show close button for tickets with status other than open', function () {
        $closedTicket = Ticket::factory()->closed()->create([
            'user_id' => $this->user->id,
            'subject' => 'Closed Ticket',
        ]);

        $html = Livewire::actingAs($this->user)
            ->test('tickets.ticket-list')
            ->html();

        // Check that the close button is not in the table for closed tickets
        expect($html)->not->toContain('wire:click.stop="openCloseModal('.$closedTicket->id.')"');
    });
});
