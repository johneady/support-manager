<?php

declare(strict_types=1);

/**
 * Keeps config('app.version') in step with the latest CHANGELOG.md heading.
 *
 * The version lives in two places by necessity: the config is what the running
 * application can read, and the changelog is what humans read. Nothing but this
 * test stops a release from bumping one and forgetting the other.
 */
it('exposes a semantic version', function (): void {
    expect(config('app.version'))->toMatch('/^\d+\.\d+\.\d+$/');
});

it('matches the most recent changelog heading', function (): void {
    $changelog = file_get_contents(base_path('CHANGELOG.md'));

    preg_match('/^## Version - (\d+\.\d+\.\d+)$/m', $changelog, $matches);

    expect($matches[1] ?? null)->toBe(config('app.version'));
});
