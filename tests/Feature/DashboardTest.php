<?php

use App\Models\Ticket;
use App\Models\User;

test('guests are redirected to login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('admin users see admin-specific stats', function () {
    $admin = User::factory()->admin()->create();

    Ticket::factory()->count(5)->open()->create();
    Ticket::factory()->count(3)->closed(['closed_at' => now()->subDays(2)])->create();

    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertViewHas('isAdmin', true)
        ->assertViewHas('openTicketsCount')
        ->assertViewHas('needsResponseCount')
        ->assertViewHas('recentlyResolvedCount')
        ->assertViewHas('recentTickets')
        ->assertSee('Open Tickets')
        ->assertSee('Needs Response')
        ->assertSee('Recently Resolved')
        ->assertSee('Tickets Requiring a Response');
});

test('admin users see recent tickets table instead of quick actions', function () {
    $admin = User::factory()->admin()->create();

    $user = User::factory()->create();
    Ticket::factory()->count(3)->for($user)->create();

    $this->actingAs($admin);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertSee('Tickets Requiring a Response')
        ->assertDontSee('Quick Actions');
});

test('non-admin users see original stats and quick actions', function () {
    $user = User::factory()->create();

    Ticket::factory()->count(2)->for($user)->open()->create();
    Ticket::factory()->for($user)->closed(['closed_at' => now()])->create();

    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk()
        ->assertViewHas('isAdmin', false)
        ->assertViewHas('openTickets')
        ->assertViewHas('inProgressTickets')
        ->assertViewHas('resolvedTickets')
        ->assertSee('Open Tickets')
        ->assertSee('In Progress')
        ->assertSee('Resolved')
        ->assertSee('Quick Actions')
        ->assertDontSee('Tickets Requiring a Response');
});
