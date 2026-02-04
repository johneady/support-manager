<?php

namespace App\Http\Controllers;

use App\Models\Ticket;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();

        $openTickets = Ticket::query()
            ->forUser($user->id)
            ->open()
            ->count();

        $inProgressTickets = Ticket::query()
            ->forUser($user->id)
            ->open()
            ->needsResponse()
            ->count();

        $resolvedTickets = Ticket::query()
            ->forUser($user->id)
            ->closed()
            ->whereMonth('closed_at', now()->month)
            ->whereYear('closed_at', now()->year)
            ->count();

        return view('dashboard', [
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'resolvedTickets' => $resolvedTickets,
        ]);
    }
}
