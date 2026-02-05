<?php

use Illuminate\Support\Facades\Schedule;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('tickets:close-inactive')->everySixHours()->withoutOverlapping();

Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class)->everyMinute();
