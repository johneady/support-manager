<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin();
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        return $user->isAdmin() || $user->id === $ticket->user_id;
    }
}
