<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Spatie\Health\Models\HealthCheckResultHistoryItem;

/**
 * spatie/laravel-health writes one row per check per run and never prunes on
 * its own: the model is MassPrunable, so keep_history_for_days in
 * config/health.php only takes effect when `model:prune` runs. Without the
 * schedule entry in routes/console.php the table grows without limit.
 */
test('health history older than the retention window is pruned', function () {
    $days = config('health.result_stores.'.
        Spatie\Health\ResultStores\EloquentHealthResultStore::class.
        '.keep_history_for_days');

    $stale = HealthCheckResultHistoryItem::create([
        'check_name' => 'Cache',
        'check_label' => 'Cache',
        'status' => 'ok',
        'meta' => [],
        'ended_at' => now()->subDays($days + 1),
        'batch' => (string) Str::uuid(),
        'created_at' => now()->subDays($days + 1),
    ]);

    $fresh = HealthCheckResultHistoryItem::create([
        'check_name' => 'Cache',
        'check_label' => 'Cache',
        'status' => 'ok',
        'meta' => [],
        'ended_at' => now(),
        'batch' => (string) Str::uuid(),
        'created_at' => now(),
    ]);

    Artisan::call('model:prune', ['--model' => [HealthCheckResultHistoryItem::class]]);

    expect(HealthCheckResultHistoryItem::find($stale->id))->toBeNull()
        ->and(HealthCheckResultHistoryItem::find($fresh->id))->not->toBeNull();
});

test('the prune command is scheduled', function () {
    $events = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())
        ->map(fn ($event) => $event->command);

    expect($events->filter(fn ($c) => str_contains((string) $c, 'model:prune')))
        ->not->toBeEmpty();
});
