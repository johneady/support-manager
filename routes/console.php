<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Schedule;
use Spatie\Health\Commands\ScheduleCheckHeartbeatCommand;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

Schedule::command('queue:work --stop-when-empty')->everyMinute()->withoutOverlapping();

Schedule::command('tickets:close-inactive')->everySixHours()->withoutOverlapping();

/*
 * Heartbeat every five minutes, not every minute.
 *
 * This command exists only to write a cache timestamp that ScheduleCheck reads
 * back to prove the scheduler process is alive. It does no work of its own, but
 * it still pays a full framework boot (~390ms) per fire, and it was the last
 * remaining everyMinute() command on a host running several of these apps.
 *
 * The heartbeat interval and ScheduleCheck's heartbeatMaxAgeInMinutes are a
 * PAIR: the tolerance must exceed the interval or the check reports a failure
 * between every write. Spatie's default tolerance is 1 minute, so raising this
 * without also raising heartbeatMaxAgeInMinutes in AppServiceProvider would
 * mark a perfectly healthy scheduler as down.
 */
Schedule::command(ScheduleCheckHeartbeatCommand::class)->everyFiveMinutes();

/**
 * Prune health-check history.
 *
 * spatie/laravel-health writes one row per check per run — five checks every
 * five minutes is ~1,440 rows a day — and its model is MassPrunable, so the
 * keep_history_for_days setting in config/health.php does nothing on its own:
 * only `model:prune` acts on it. Without this the table grows without limit
 * (it had reached 292,820 rows, ~48MB, before this was scheduled).
 */
Schedule::command('model:prune', [
    '--model' => [HealthCheckResultHistoryItem::class],
])->daily();

try {
    $healthCheckInterval = (int) Setting::get('health_check_interval', '60');
} catch (\Exception) {
    $healthCheckInterval = 60;
}

$healthCheckSchedule = Schedule::command(\Spatie\Health\Commands\RunHealthChecksCommand::class);

match ($healthCheckInterval) {
    1 => $healthCheckSchedule->everyMinute(),
    5 => $healthCheckSchedule->everyFiveMinutes(),
    10 => $healthCheckSchedule->everyTenMinutes(),
    15 => $healthCheckSchedule->everyFifteenMinutes(),
    30 => $healthCheckSchedule->everyThirtyMinutes(),
    60 => $healthCheckSchedule->hourly(),
    /**
     * Only reachable if the setting is written outside the admin UI, which
     * offers just the values above. A cron step that does not divide 60
     * misfires: a 45-minute step fires at :45 and then again at :00, a
     * 15-minute gap, so the interval select deliberately omits those.
     */
    default => $healthCheckSchedule->cron("*/{$healthCheckInterval} * * * *"),
};
