<?php

use Livewire\Livewire;

it('renders successfully', function () {
    Livewire::test('ticket-queue-badge')
        ->assertStatus(200);
});
