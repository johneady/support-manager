<?php

declare(strict_types=1);

use App\Enums\TicketStatus;
use App\Jobs\CloseInactiveTickets;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use App\Notifications\TicketAutoClosedNotification;
use Illuminate\Support\Facades\Notification;

beforeEach(function () {
    Notification::fake();
});

it('closes tickets with no customer response after 7 days of admin reply', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    // Create an admin reply 8 days ago
    TicketReply::factory()->for($ticket)->create([
        'is_from_admin' => true,
        'created_at' => now()->subDays(8),
    ]);

    (new CloseInactiveTickets)->handle();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Closed);
    expect($ticket->closed_at)->not->toBeNull();
});

it('does not close tickets with recent customer response', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    // Create an admin reply 8 days ago
    TicketReply::factory()->for($ticket)->create([
        'is_from_admin' => true,
        'created_at' => now()->subDays(8),
    ]);

    // Create a customer reply 5 days ago
    TicketReply::factory()->for($ticket)->create([
        'user_id' => $user->id,
        'is_from_admin' => false,
        'created_at' => now()->subDays(5),
    ]);

    (new CloseInactiveTickets)->handle();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Open);
});

it('does not close tickets with admin reply less than 7 days old', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    // Create an admin reply 5 days ago
    TicketReply::factory()->for($ticket)->create([
        'is_from_admin' => true,
        'created_at' => now()->subDays(5),
    ]);

    (new CloseInactiveTickets)->handle();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Open);
});

it('adds automated reply when closing ticket', function () {
    $user = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    // Create an admin reply 8 days ago
    TicketReply::factory()->for($ticket)->create([
        'user_id' => $admin->id,
        'is_from_admin' => true,
        'created_at' => now()->subDays(8),
    ]);

    (new CloseInactiveTickets)->handle();

    $ticket->refresh();
    $lastReply = $ticket->replies()->latest()->first();

    expect($lastReply->user_id)->toBe($admin->id);
    expect($lastReply->is_from_admin)->toBeTrue();
    expect($lastReply->body)->toContain('automatically closed');
});

it('sends notification to customer when closing ticket', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    // Create an admin reply 8 days ago
    TicketReply::factory()->for($ticket)->create([
        'is_from_admin' => true,
        'created_at' => now()->subDays(8),
    ]);

    (new CloseInactiveTickets)->handle();

    Notification::assertSentTo(
        $user,
        TicketAutoClosedNotification::class,
        function ($notification) use ($ticket) {
            return $notification->ticket->id === $ticket->id;
        }
    );
});

it('does not close already closed tickets', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Closed,
    ]);

    // Create an admin reply 8 days ago
    TicketReply::factory()->for($ticket)->create([
        'is_from_admin' => true,
        'created_at' => now()->subDays(8),
    ]);

    (new CloseInactiveTickets)->handle();

    Notification::assertNotSentTo($user, TicketAutoClosedNotification::class);
});

it('handles tickets with no replies', function () {
    $user = User::factory()->create();
    $ticket = Ticket::factory()->for($user)->create([
        'status' => TicketStatus::Open,
    ]);

    (new CloseInactiveTickets)->handle();

    $ticket->refresh();
    expect($ticket->status)->toBe(TicketStatus::Open);
});
