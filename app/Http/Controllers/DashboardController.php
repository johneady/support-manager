<?php

namespace App\Http\Controllers;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Models\Ticket;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $user = auth()->user() ?? abort(401);
        $isAdmin = $user->isAdmin();

        if ($isAdmin) {
            $openTicketsCount = Ticket::query()->where('status', TicketStatus::Open)->count();
            $recentlyResolvedCount = Ticket::query()
                ->where('status', TicketStatus::Closed)
                ->where('closed_at', '>=', now()->subDays(7))
                ->count();

            $needsResponseCount = Ticket::query()
                ->open()
                ->needsResponse()
                ->count();

            $recentTickets = Ticket::query()
                ->open()
                ->needsResponse()
                ->with(['user', 'latestReply'])
                ->orderByRaw(TicketPriority::orderBySql())
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

        $openTickets = Ticket::query()
            ->forUser($user->id)
            ->where('status', TicketStatus::Open)
            ->count();
        $resolvedTickets = Ticket::query()
            ->forUser($user->id)
            ->where('status', TicketStatus::Closed)
            ->where('closed_at', '>=', now()->startOfMonth())
            ->count();

        $awaitingResponseCount = Ticket::query()
            ->forUser($user->id)
            ->open()
            ->awaitingUserResponse()
            ->count();

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'openTickets' => $openTickets,
            'awaitingResponseCount' => $awaitingResponseCount,
            'resolvedTickets' => $resolvedTickets,
        ]);
    }
}
