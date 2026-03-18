<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('tickets:close-inactive')->everySixHours()->withoutOverlapping();

Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyMinute();

try {
    $healthCheckInterval = (int) Setting::get('health_check_interval', '5');
} catch (\Exception) {
    $healthCheckInterval = 5;
}

$healthCheckSchedule = Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class);

match ($healthCheckInterval) {
    1 => $healthCheckSchedule->everyMinute(),
    5 => $healthCheckSchedule->everyFiveMinutes(),
    10 => $healthCheckSchedule->everyTenMinutes(),
    15 => $healthCheckSchedule->everyFifteenMinutes(),
    30 => $healthCheckSchedule->everyThirtyMinutes(),
    default => $healthCheckSchedule->cron("*/{$healthCheckInterval} * * * *"),
};
