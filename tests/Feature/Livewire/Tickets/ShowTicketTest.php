<?php

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketReplyNotification;
use Database\Seeders\TicketCategorySeeder;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
    $this->customer = User::factory()->create(['is_admin' => false]);
    $this->ticket = Ticket::factory()->create([
        'user_id' => $this->customer->id,
        'status' => 'open',
    ]);
});

describe('ShowTicket', function () {
    it('allows customer to view their own ticket', function () {
        $this->actingAs($this->customer)
            ->get("/tickets/{$this->ticket->id}")
            ->assertSuccessful();
    });

    it('prevents customer from viewing other users tickets', function () {
        $otherUser = User::factory()->create();
        $otherTicket = Ticket::factory()->create(['user_id' => $otherUser->id]);

        $this->actingAs($this->customer)
            ->get("/tickets/{$otherTicket->id}")
            ->assertForbidden();
    });

    it('sends notification to all admins when customer replies', function () {
        Notification::fake();

        $admin1 = User::factory()->create(['is_admin' => true]);
        $admin2 = User::factory()->create(['is_admin' => true]);
        $nonAdmin = User::factory()->create(['is_admin' => false]);

        $this->actingAs($this->customer);

        Livewire::test('tickets.show-ticket', ['ticket' => $this->ticket])
            ->set('replyBody', 'This is a customer reply')
            ->call('submitReply');

        Notification::assertSentTo([$admin1, $admin2], TicketReplyNotification::class);
        Notification::assertNotSentTo($nonAdmin, TicketReplyNotification::class);
    });

    it('creates reply with is_from_admin as false when customer replies', function () {
        Notification::fake();

        User::factory()->create(['is_admin' => true]);

        $this->actingAs($this->customer);

        Livewire::test('tickets.show-ticket', ['ticket' => $this->ticket])
            ->set('replyBody', 'Customer reply here')
            ->call('submitReply');

        $reply = $this->ticket->fresh()->replies->first();

        expect($reply->is_from_admin)->toBeFalse()
            ->and($reply->user_id)->toBe($this->customer->id);
    });
});
