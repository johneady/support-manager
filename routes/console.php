<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\DispatchQueueCheckJobsCommand;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('tickets:close-inactive')->everySixHours()->withoutOverlapping();

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();

$healthCheckInterval = (int) Setting::get('health_check_interval', '5');

// Dispatch queue check jobs at half the health check interval so results are
// fresh when RunHealthChecksCommand evaluates them. Minimum of 1 minute.
$queueDispatchInterval = max(1, (int) floor($healthCheckInterval / 2));
Schedule::command(DispatchQueueCheckJobsCommand::class)->cron("*/{$queueDispatchInterval} * * * *");

$healthCheckSchedule = Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class);

match ($healthCheckInterval) {
    1 => $healthCheckSchedule->everyMinute(),
    5 => $healthCheckSchedule->everyFiveMinutes(),
    10 => $healthCheckSchedule->everyTenMinutes(),
    15 => $healthCheckSchedule->everyFifteenMinutes(),
    30 => $healthCheckSchedule->everyThirtyMinutes(),
    default => $healthCheckSchedule->cron("*/{$healthCheckInterval} * * * *"),
};
