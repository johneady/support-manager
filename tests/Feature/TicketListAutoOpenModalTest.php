<?php

use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

test('dashboard link opens create modal on tickets page', function () {
    $user = User::factory()->create();

    $response = actingAs($user)
        ->get(route('tickets.index', ['create' => 'true']));

    $response->assertStatus(200);
});

test('ticket list component opens create modal when create query param is true', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('tickets.ticket-list', ['create' => 'true'])
        ->assertSet('showCreateModal', true);
});

test('ticket list component does not open create modal when create query param is not set', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('tickets.ticket-list')
        ->assertSet('showCreateModal', false);
});

test('ticket list component does not open create modal when create query param is false', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('tickets.ticket-list', ['create' => 'false'])
        ->assertSet('showCreateModal', false);
});
