<?php

namespace App\Http\Controllers;

use App\Models\Ticket;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        if ($isAdmin) {
            // Admin-specific stats
            $openTicketsCount = Ticket::query()->open()->count();

            $needsResponseCount = Ticket::query()
                ->open()
                ->needsResponse()
                ->count();

            $recentlyResolvedCount = Ticket::query()
                ->closed()
                ->where('closed_at', '>=', now()->subDays(7))
                ->count();

            // Get 3 most recent tickets (descending by creation datetime)
            $recentTickets = Ticket::query()
                ->with('user')
                ->latest('created_at')
                ->limit(3)
                ->get();

            return view('dashboard', [
                'isAdmin' => $isAdmin,
                'openTicketsCount' => $openTicketsCount,
                'needsResponseCount' => $needsResponseCount,
                'recentlyResolvedCount' => $recentlyResolvedCount,
                'recentTickets' => $recentTickets,
            ]);
        }

        // Non-admin stats (existing behavior)
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
            'isAdmin' => $isAdmin,
            'openTickets' => $openTickets,
            'inProgressTickets' => $inProgressTickets,
            'resolvedTickets' => $resolvedTickets,
        ]);
    }
}
