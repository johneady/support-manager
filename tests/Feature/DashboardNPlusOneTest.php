<?php

declare(strict_types=1);

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;

test('dashboard does not have n+1 query issue for admin', function () {
    $admin = User::factory()->admin()->create();
    $user = User::factory()->create();

    // Create multiple tickets with replies
    Ticket::factory()->count(10)->for($user)->create()->each(function ($ticket) {
        TicketReply::factory()->for($ticket)->create(['is_from_admin' => false]);
    });

    // Enable query logging
    DB::enableQueryLog();

    // Simulate the dashboard controller logic for admin
    $recentTickets = Ticket::query()
        ->with(['user', 'replies' => fn ($query) => $query->latest()->limit(1)])
        ->latest('created_at')
        ->limit(3)
        ->get();

    // Call needsResponse() on each ticket (simulating the view)
    foreach ($recentTickets as $ticket) {
        $ticket->needsResponse();
    }

    $queries = DB::getQueryLog();

    // We should have exactly 3 queries:
    // 1. SELECT * FROM tickets ORDER BY created_at DESC LIMIT 3
    // 2. SELECT * FROM users WHERE id IN (...)
    // 3. SELECT * FROM ticket_replies WHERE ticket_id IN (...) ORDER BY created_at DESC LIMIT 1
    expect($queries)->toHaveCount(3);
});

test('dashboard does not have n+1 query issue for non-admin', function () {
    $user = User::factory()->create();

    // Create multiple tickets
    Ticket::factory()->count(10)->for($user)->create();

    // Enable query logging
    DB::enableQueryLog();

    // Simulate the dashboard controller logic for non-admin
    $openTickets = Ticket::query()
        ->forUser($user->id)
        ->open()
        ->count();

    $inProgressTickets = Ticket::query()
        ->forUser($user->id)
        ->open()
        ->needsResponse()
        ->count();

    $resolvedTickets = Ticket::query()
        ->forUser($user->id)
        ->closed()
        ->whereMonth('closed_at', now()->month)
        ->whereYear('closed_at', now()->year)
        ->count();

    $queries = DB::getQueryLog();

    // We should have exactly 3 queries (one for each count)
    expect($queries)->toHaveCount(3);
});
