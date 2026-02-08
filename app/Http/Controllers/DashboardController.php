<?php

namespace App\Http\Controllers;

use App\Enums\TicketStatus;
use App\Models\Ticket;

class DashboardController extends Controller
{
    public function __invoke()
    {
        $user = auth()->user();
        $isAdmin = $user->isAdmin();

        if ($isAdmin) {
            $stats = Ticket::query()
                ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as open_count', [TicketStatus::Open->value])
                ->selectRaw('COUNT(CASE WHEN status = ? AND closed_at >= ? THEN 1 END) as resolved_count', [TicketStatus::Closed->value, now()->subDays(7)])
                ->first();

            $needsResponseCount = Ticket::query()
                ->open()
                ->needsResponse()
                ->count();

            $recentTickets = Ticket::query()
                ->open()
                ->needsResponse()
                ->with(['user', 'latestReply'])
                ->orderByRaw("CASE priority WHEN 'high' THEN 1 WHEN 'medium' THEN 2 WHEN 'low' THEN 3 END")
                ->limit(3)
                ->get();

            return view('dashboard', [
                'isAdmin' => $isAdmin,
                'openTicketsCount' => $stats->open_count,
                'needsResponseCount' => $needsResponseCount,
                'recentlyResolvedCount' => $stats->resolved_count,
                'recentTickets' => $recentTickets,
            ]);
        }

        $stats = Ticket::query()
            ->forUser($user->id)
            ->selectRaw('COUNT(CASE WHEN status = ? THEN 1 END) as open_count', [TicketStatus::Open->value])
            ->selectRaw('COUNT(CASE WHEN status = ? AND closed_at >= ? THEN 1 END) as resolved_count', [TicketStatus::Closed->value, now()->startOfMonth()])
            ->first();

        $awaitingResponseCount = Ticket::query()
            ->forUser($user->id)
            ->open()
            ->awaitingUserResponse()
            ->count();

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'openTickets' => $stats->open_count,
            'awaitingResponseCount' => $awaitingResponseCount,
            'resolvedTickets' => $stats->resolved_count,
        ]);
    }
}
