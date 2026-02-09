<?php

use App\Enums\TicketStatus;
use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
});

describe('needsResponse scope', function () {
    it('includes tickets with no replies', function () {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        expect(Ticket::open()->needsResponse()->pluck('id'))
            ->toContain($ticket->id);
    });

    it('includes tickets where last reply is from customer', function () {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create(['status' => TicketStatus::Open]);

        TicketReply::factory()->for($ticket)->create([
            'is_from_admin' => true,
            'created_at' => now()->subHour(),
        ]);

        TicketReply::factory()->for($ticket)->create([
            'user_id' => $user->id,
            'is_from_admin' => false,
            'created_at' => now(),
        ]);

        expect(Ticket::open()->needsResponse()->pluck('id'))
            ->toContain($ticket->id);
    });

    it('excludes tickets where last reply is from admin', function () {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create(['status' => TicketStatus::Open]);

        TicketReply::factory()->for($ticket)->create([
            'user_id' => $user->id,
            'is_from_admin' => false,
            'created_at' => now()->subHour(),
        ]);

        TicketReply::factory()->for($ticket)->create([
            'is_from_admin' => true,
            'created_at' => now(),
        ]);

        expect(Ticket::open()->needsResponse()->pluck('id'))
            ->not->toContain($ticket->id);
    });
});

describe('awaitingUserResponse scope', function () {
    it('includes tickets where last reply is from admin', function () {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create(['status' => TicketStatus::Open]);

        TicketReply::factory()->for($ticket)->create([
            'is_from_admin' => true,
            'created_at' => now(),
        ]);

        expect(Ticket::open()->awaitingUserResponse()->pluck('id'))
            ->toContain($ticket->id);
    });

    it('excludes tickets where last reply is from customer', function () {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create(['status' => TicketStatus::Open]);

        TicketReply::factory()->for($ticket)->create([
            'is_from_admin' => true,
            'created_at' => now()->subHour(),
        ]);

        TicketReply::factory()->for($ticket)->create([
            'user_id' => $user->id,
            'is_from_admin' => false,
            'created_at' => now(),
        ]);

        expect(Ticket::open()->awaitingUserResponse()->pluck('id'))
            ->not->toContain($ticket->id);
    });

    it('excludes tickets with no replies', function () {
        $ticket = Ticket::factory()->create(['status' => TicketStatus::Open]);

        expect(Ticket::open()->awaitingUserResponse()->pluck('id'))
            ->not->toContain($ticket->id);
    });
});

describe('needsResponse instance method', function () {
    it('returns true for tickets with no replies', function () {
        $ticket = Ticket::factory()->create();
        $ticket->load('latestReply');

        expect($ticket->needsResponse())->toBeTrue();
    });

    it('returns true when last reply is from customer', function () {
        $user = User::factory()->create();
        $ticket = Ticket::factory()->for($user)->create();

        TicketReply::factory()->for($ticket)->create([
            'user_id' => $user->id,
            'is_from_admin' => false,
        ]);

        $ticket->load('latestReply');

        expect($ticket->needsResponse())->toBeTrue();
    });

    it('returns false when last reply is from admin', function () {
        $ticket = Ticket::factory()->create();

        TicketReply::factory()->for($ticket)->create([
            'is_from_admin' => true,
        ]);

        $ticket->load('latestReply');

        expect($ticket->needsResponse())->toBeFalse();
    });
});
