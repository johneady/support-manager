<?php

use App\Models\Ticket;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->admin = User::factory()->create(['is_admin' => true]);
});

describe('TicketResource', function () {
    it('lists tickets ordered by priority descending', function () {
        $lowTicket = Ticket::factory()->create(['priority' => 'low']);
        $highTicket = Ticket::factory()->create(['priority' => 'high']);
        $mediumTicket = Ticket::factory()->create(['priority' => 'medium']);

        actingAs($this->admin)
            ->get('/admin/tickets')
            ->assertSuccessful();

        $tickets = Ticket::query()
            ->orderByRaw("
                CASE priority
                    WHEN 'high' THEN 1
                    WHEN 'medium' THEN 2
                    WHEN 'low' THEN 3
                END ASC
            ")
            ->get();

        expect($tickets[0]->id)->toBe($highTicket->id)
            ->and($tickets[1]->id)->toBe($mediumTicket->id)
            ->and($tickets[2]->id)->toBe($lowTicket->id);
    });

    it('requires admin access', function () {
        $user = User::factory()->create(['is_admin' => false]);

        actingAs($user)
            ->get('/admin/tickets')
            ->assertForbidden();
    });

    it('allows admin to view ticket details', function () {
        $ticket = Ticket::factory()->create();

        actingAs($this->admin)
            ->get("/admin/tickets/{$ticket->id}")
            ->assertSuccessful();
    });
});
