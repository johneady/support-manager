<?php

use App\Models\Ticket;

it('generates correct reference number format', function () {
    $ticket = new Ticket;
    $ticket->id = 1;

    expect($ticket->reference_number)->toBe('TX-1138-000001');
});

it('generates zero-padded reference numbers', function () {
    $ticket = new Ticket;
    $ticket->id = 42;

    expect($ticket->reference_number)->toBe('TX-1138-000042');
});

it('generates reference numbers for large IDs', function () {
    $ticket = new Ticket;
    $ticket->id = 123456;

    expect($ticket->reference_number)->toBe('TX-1138-123456');
});

it('generates reference numbers for six-digit IDs', function () {
    $ticket = new Ticket;
    $ticket->id = 999999;

    expect($ticket->reference_number)->toBe('TX-1138-999999');
});
