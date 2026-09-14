<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;
use Livewire\Livewire;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
    $this->admin = User::factory()->admin()->create();
    $this->user = User::factory()->create();
});

describe('admin queue search', function () {
    it('search filters by subject', function () {
        $matchingTicket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Specific Search Term',
        ]);
        $nonMatchingTicket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Different Content',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', 'Specific Search')
            ->assertSee('Specific Search Term')
            ->assertDontSee('Different Content');
    });

    it('search filters by user name', function () {
        $searchUser = User::factory()->create(['name' => 'John Doe']);
        $otherUser = User::factory()->create(['name' => 'Jane Smith']);

        $matchingTicket = Ticket::factory()->open()->create([
            'user_id' => $searchUser->id,
            'subject' => 'Matching Name Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->open()->create([
            'user_id' => $otherUser->id,
            'subject' => 'Non Matching Name Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', 'John Doe')
            ->assertSee('Matching Name Ticket')
            ->assertDontSee('Non Matching Name Ticket');
    });

    it('search filters by user email', function () {
        $searchUser = User::factory()->create(['email' => 'john@example.com']);
        $otherUser = User::factory()->create(['email' => 'jane@example.com']);

        $matchingTicket = Ticket::factory()->open()->create([
            'user_id' => $searchUser->id,
            'subject' => 'Matching Email Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->open()->create([
            'user_id' => $otherUser->id,
            'subject' => 'Non Matching Email Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', 'john@example.com')
            ->assertSee('Matching Email Ticket')
            ->assertDontSee('Non Matching Email Ticket');
    });

    it('search filters by full ticket reference number', function () {
        $matchingTicket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Full Reference Ticket',
        ]);
        $nonMatchingTicket = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Non Full Reference Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', $matchingTicket->ticket_reference_number)
            ->assertSee('Full Reference Ticket')
            ->assertDontSee('Non Full Reference Ticket');
    });

    it('search filters by partial ticket reference number', function () {
        $ticket1 = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Ticket One',
        ]);
        $ticket2 = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'subject' => 'Ticket Two',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', substr($ticket1->ticket_reference_number, -6))
            ->assertSee('Ticket One')
            ->assertDontSee('Ticket Two');
    });

    it('shows empty state when no tickets match search', function () {
        Ticket::factory()->open()->create(['user_id' => $this->user->id, 'subject' => 'Test Ticket']);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('search', 'NonExistent')
            ->assertSee('No tickets found');
    });
});

describe('admin queue category filter', function () {
    it('category filter works correctly', function () {
        $category1 = \App\Models\TicketCategory::where('slug', 'technical-support')->first();
        $category2 = \App\Models\TicketCategory::where('slug', 'general-inquiry')->first();

        $ticket1 = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'ticket_category_id' => $category1->id,
            'subject' => 'Technical Support Ticket',
        ]);
        $ticket2 = Ticket::factory()->open()->create([
            'user_id' => $this->user->id,
            'ticket_category_id' => $category2->id,
            'subject' => 'General Inquiry Ticket',
        ]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->set('categoryFilter', (string) $category1->id)
            ->assertSee('Technical Support Ticket')
            ->assertDontSee('General Inquiry Ticket');
    });
});

describe('admin queue access', function () {
    it('requires admin access', function () {
        Livewire::actingAs($this->user)
            ->test('tickets.admin-queue')
            ->assertForbidden();
    });

    it('allows admin access', function () {
        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->assertStatus(200);
    });
});

describe('admin queue edit modal', function () {
    it('shows replies in chronological back-and-forth order', function () {
        $ticket = Ticket::factory()->open()->create(['user_id' => $this->user->id]);

        TicketReply::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id, 'body' => 'customer first message', 'is_from_admin' => false, 'created_at' => now()->subMinutes(40)]);
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id, 'body' => 'admin second message', 'created_at' => now()->subMinutes(30)]);
        TicketReply::factory()->create(['ticket_id' => $ticket->id, 'user_id' => $this->user->id, 'body' => 'customer third message', 'is_from_admin' => false, 'created_at' => now()->subMinutes(20)]);
        TicketReply::factory()->fromAdmin()->create(['ticket_id' => $ticket->id, 'user_id' => $this->admin->id, 'body' => 'admin fourth message', 'created_at' => now()->subMinutes(10)]);

        Livewire::actingAs($this->admin)
            ->test('tickets.admin-queue')
            ->call('openEditModal', $ticket)
            ->assertSeeInOrder([
                'customer first message',
                'admin second message',
                'customer third message',
                'admin fourth message',
            ]);
    });
});
