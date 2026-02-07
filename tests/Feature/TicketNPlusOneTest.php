<?php

declare(strict_types=1);

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
});

test('ticket list does not have n+1 query issue', function () {
    $user = User::factory()->create();

    // Create multiple tickets with replies
    Ticket::factory()->count(10)->for($user)->create()->each(function ($ticket) {
        TicketReply::factory()->for($ticket)->create(['is_from_admin' => false]);
    });

    // Enable query logging
    DB::enableQueryLog();

    // Load tickets with eager loading (simulating the ticket-list component)
    $tickets = Ticket::query()
        ->forUser($user->id)
        ->with(['replies' => fn ($query) => $query->latest()->limit(1)])
        ->latest()
        ->get();

    // Call needsResponse() on each ticket (simulating the view)
    foreach ($tickets as $ticket) {
        $ticket->needsResponse();
    }

    $queries = DB::getQueryLog();

    // We should have exactly 2 queries:
    // 1. SELECT * FROM tickets WHERE user_id = ? ORDER BY created_at DESC
    // 2. SELECT * FROM ticket_replies WHERE ticket_id IN (...) ORDER BY created_at DESC LIMIT 1
    expect($queries)->toHaveCount(2);
});

test('admin queue does not have n+1 query issue', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    // Create multiple tickets with replies
    Ticket::factory()->count(10)->for($user)->create()->each(function ($ticket) {
        TicketReply::factory()->for($ticket)->create(['is_from_admin' => false]);
    });

    // Enable query logging
    DB::enableQueryLog();

    // Load tickets with eager loading (simulating the admin-queue component)
    $tickets = Ticket::query()
        ->with(['user', 'replies' => fn ($q) => $q->latest()->limit(1)])
        ->open()
        ->latest()
        ->get();

    // Call needsResponse() on each ticket (simulating the view)
    foreach ($tickets as $ticket) {
        $ticket->needsResponse();
    }

    $queries = DB::getQueryLog();

    // We should have exactly 3 queries:
    // 1. SELECT * FROM tickets WHERE status = 'open' ORDER BY created_at DESC
    // 2. SELECT * FROM users WHERE id IN (...)
    // 3. SELECT * FROM ticket_replies WHERE ticket_id IN (...) ORDER BY created_at DESC LIMIT 1
    expect($queries)->toHaveCount(3);
});

test('needsResponse works correctly with eager loaded replies', function () {
    $user = User::factory()->create();

    // Ticket with no replies - should need response
    $ticket1 = Ticket::factory()->for($user)->create();

    // Ticket with customer reply - should need response
    $ticket2 = Ticket::factory()->for($user)->create();
    TicketReply::factory()->for($ticket2)->create(['is_from_admin' => false]);

    // Ticket with admin reply - should not need response
    $ticket3 = Ticket::factory()->for($user)->create();
    TicketReply::factory()->for($ticket3)->create(['is_from_admin' => true]);

    // Load tickets with eager loading
    $tickets = Ticket::query()
        ->with(['replies' => fn ($query) => $query->latest()->limit(1)])
        ->whereIn('id', [$ticket1->id, $ticket2->id, $ticket3->id])
        ->get();

    expect($tickets->find($ticket1->id)->needsResponse())->toBeTrue()
        ->and($tickets->find($ticket2->id)->needsResponse())->toBeTrue()
        ->and($tickets->find($ticket3->id)->needsResponse())->toBeFalse();
});

test('needsResponse works correctly without eager loading', function () {
    $user = User::factory()->create();

    // Ticket with no replies - should need response
    $ticket1 = Ticket::factory()->for($user)->create();

    // Ticket with customer reply - should need response
    $ticket2 = Ticket::factory()->for($user)->create();
    TicketReply::factory()->for($ticket2)->create(['is_from_admin' => false]);

    // Ticket with admin reply - should not need response
    $ticket3 = Ticket::factory()->for($user)->create();
    TicketReply::factory()->for($ticket3)->create(['is_from_admin' => true]);

    expect($ticket1->needsResponse())->toBeTrue()
        ->and($ticket2->needsResponse())->toBeTrue()
        ->and($ticket3->needsResponse())->toBeFalse();
});
