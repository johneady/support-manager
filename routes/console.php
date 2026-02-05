<?php

use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('tickets:close-inactive')->everySixHours()->withoutOverlapping();

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();

Schedule::command(DispatchQueueCheckJobsCommand::class)->everyMinute();

Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class)->everyMinute();
