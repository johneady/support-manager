<?php

namespace Database\Seeders;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();
        $categories = TicketCategory::active()->ordered()->get();

        if ($categories->isEmpty()) {
            return;
        }

        $technicalSupport = $categories->firstWhere('slug', 'technical-support');
        $salesSupport = $categories->firstWhere('slug', 'sales-support');
        $generalInquiry = $categories->firstWhere('slug', 'general-inquiry');

        // Create open tickets with various priorities and categories
        Ticket::factory(5)
            ->open()
            ->lowPriority()
            ->create()
            ->each(function (Ticket $ticket) use ($users) {
                $replyCount = fake()->numberBetween(0, 3);
                for ($i = 0; $i < $replyCount; $i++) {
                    TicketReply::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $users->random()->id,
                    ]);
                }
            });

        Ticket::factory(8)
            ->open()
            ->mediumPriority()
            ->create()
            ->each(function (Ticket $ticket) use ($users) {
                $replyCount = fake()->numberBetween(0, 5);
                for ($i = 0; $i < $replyCount; $i++) {
                    TicketReply::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $users->random()->id,
                    ]);
                }
            });

        Ticket::factory(4)
            ->open()
            ->highPriority()
            ->create()
            ->each(function (Ticket $ticket) use ($users) {
                $replyCount = fake()->numberBetween(1, 8);
                for ($i = 0; $i < $replyCount; $i++) {
                    TicketReply::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $users->random()->id,
                    ]);
                }
            });

        // Create closed tickets
        Ticket::factory(25)
            ->closed()
            ->create()
            ->each(function (Ticket $ticket) use ($users) {
                $replyCount = fake()->numberBetween(2, 10);
                for ($i = 0; $i < $replyCount; $i++) {
                    TicketReply::factory()->create([
                        'ticket_id' => $ticket->id,
                        'user_id' => $users->random()->id,
                    ]);
                }
            });
    }
}
