<?php

use App\Models\Ticket;
use App\Models\TicketReply;
use App\Models\User;
use Database\Seeders\TicketCategorySeeder;

beforeEach(function () {
    $this->seed(TicketCategorySeeder::class);
});

test('admin sees ticket queue badge when tickets need a response', function () {
    $admin = User::factory()->admin()->create();

    Ticket::factory()->count(3)->open()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ticket Queue')
        ->assertSeeInOrder(['Ticket Queue', '3']);
});

test('admin does not see ticket queue badge when no tickets need a response', function () {
    $admin = User::factory()->admin()->create();

    $ticket = Ticket::factory()->open()->create();
    TicketReply::factory()->fromAdmin()->for($ticket)->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ticket Queue');
});

test('admin does not see ticket queue badge for closed tickets', function () {
    $admin = User::factory()->admin()->create();

    Ticket::factory()->count(2)->closed()->create();

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Ticket Queue');
});

test('non-admin does not see ticket queue menu item', function () {
    $user = User::factory()->create();

    Ticket::factory()->count(3)->open()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertDontSee('Ticket Queue');
});
