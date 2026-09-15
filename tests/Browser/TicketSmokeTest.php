<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;

/*
 * Browser coverage for the ticket pages, which are the application: Livewire
 * tables and modals, the Flux component library, and the built Vite bundle
 * they all depend on. The feature suite drives the components directly and
 * cannot see a page that renders but throws in the browser -- which is how
 * these break on a frontend dependency bump.
 */

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
});

test('the customer dashboard renders without javascript errors', function () {
    $this->actingAs(User::factory()->create());

    visit('/dashboard')
        ->assertSee('Awaiting Your Response')
        ->assertNoJavaScriptErrors();
});

test('the ticket list shows a customers ticket', function () {
    $user = User::factory()->create();
    Ticket::factory()->create([
        'user_id' => $user->id,
        'subject' => 'Printer catches fire',
    ]);

    $this->actingAs($user);

    visit('/tickets')
        ->assertSee('My Support Tickets')
        ->assertSee('Printer catches fire')
        ->assertNoJavaScriptErrors();
});

test('the ticket conversation renders in the browser', function () {
    $user = User::factory()->create();
    $admin = User::factory()->admin()->create();
    $ticket = Ticket::factory()->create([
        'user_id' => $user->id,
        'description' => 'The office printer started smoking and then caught fire.',
    ]);
    TicketReply::factory()->fromAdmin()->create([
        'ticket_id' => $ticket->id,
        'user_id' => $admin->id,
        'body' => 'Please stop using the printer immediately.',
    ]);

    $this->actingAs($user);

    visit('/tickets/'.$ticket->id)
        ->assertSee('Conversation')
        ->assertSee('The office printer started smoking and then caught fire.')
        ->assertSee('Please stop using the printer immediately.')
        ->assertNoJavaScriptErrors();
});

test('the admin queue renders without javascript errors', function () {
    $this->actingAs(User::factory()->admin()->create());

    visit('/tickets/queue')
        ->assertSee('Ticket Queue')
        ->assertNoJavaScriptErrors();
});
