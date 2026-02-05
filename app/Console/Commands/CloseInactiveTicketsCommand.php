<?php

namespace App\Console\Commands;

use App\Jobs\CloseInactiveTickets;
use Illuminate\Console\Command;

class CloseInactiveTicketsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'tickets:close-inactive';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Close inactive tickets that have no customer response after 7 days of admin reply';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Dispatching job to close inactive tickets...');

        CloseInactiveTickets::dispatch();

        $this->info('Job dispatched successfully!');

        return Command::SUCCESS;
    }
}
