<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

function scheduledEventMatching(string $needle): ?Event
{
    foreach (app(Schedule::class)->events() as $event) {
        if (str_contains((string) $event->command, $needle)) {
            return $event;
        }
    }

    return null;
}

/**
 * The heartbeat writes a cache timestamp that ScheduleCheck reads back to prove
 * the scheduler is alive. It does no other work, so it runs every five minutes
 * rather than every minute to avoid paying a framework boot per minute.
 */
test('the schedule heartbeat runs every five minutes', function (): void {
    expect(scheduledEventMatching('health:schedule-check-heartbeat')->expression)
        ->toBe('*/5 * * * *');
});

/**
 * The heartbeat interval and ScheduleCheck's tolerance are a pair: if the
 * tolerance is not greater than the interval, the check reports a failure in
 * the gap between every heartbeat write. Spatie's default tolerance is 1
 * minute, so slowing the heartbeat without raising the tolerance would report a
 * perfectly healthy scheduler as down.
 */
test('the schedule check tolerates a longer gap than the heartbeat interval', function (): void {
    $check = collect(Health::registeredChecks())
        ->first(fn ($check): bool => $check instanceof ScheduleCheck);

    expect($check)->not->toBeNull();

    $tolerance = (new ReflectionProperty($check, 'heartbeatMaxAgeInMinutes'))
        ->getValue($check);

    expect($tolerance)->toBeGreaterThan(5);
});

/**
 * Queued password resets and user invitations are user-facing: someone is
 * waiting on the mail. The drain stays at every minute even though the
 * heartbeat was slowed.
 */
test('the queue drain still runs every minute', function (): void {
    expect(scheduledEventMatching('queue:work')->expression)->toBe('* * * * *');
});
