<?php

declare(strict_types=1);

/**
 * Guards the CLI opcache contract across docker/php/php.ini, the Dockerfile and
 * the entrypoint.
 *
 * The scheduler role runs `schedule:work`, which forks `schedule:run` every
 * minute, which forks `queue:work --stop-when-empty`. Each fork is a fresh
 * short-lived process that compiles a large slice of the framework and
 * application from source. Measured on the sibling pet-adoption stack, that
 * cost ~819ms per boot and pinned CPU at 100%% for ~8s of every minute.
 *
 * Two things here are counter-intuitive enough to be worth locking down:
 *
 *  1. `opcache.enable_cli` ALONE makes this slower, not faster (819ms -> 1271ms
 *     measured there): a process that exits in under a second pays to populate a
 *     shared-memory cache it then discards. The win requires the on-disk
 *     file_cache that outlives the process (819ms -> 431ms measured).
 *
 *  2. `opcache.file_cache_only` must NOT be in docker/php/php.ini. It completes
 *     the CLI win, but php.ini has no SAPI scoping, so a line there reaches
 *     php-fpm — where it turns the shared-memory cache OFF and makes every web
 *     request do filesystem I/O for ~1,000 scripts. Measured on a sibling image
 *     on this host: 738ms median vs 24ms, ~30x. It is applied per role instead,
 *     via PHP_INI_SCAN_DIR in the entrypoint. This is the regression these
 *     tests exist to prevent recurring.
 *
 *  3. PHP treats a missing `opcache.file_cache` directory as a startup FATAL,
 *     not a warning — "must be a full path of an accessible directory". So the
 *     directory must exist before ANY php runs, or the entrypoint's migrations
 *     die and the container crash-loops. That failure is invisible to these
 *     assertions at runtime, which is exactly why it is asserted here.
 *
 * Nothing in the application surfaces a regression in any of this: the site
 * behaves identically, just slower (or, for the directory, not at all), so
 * these assertions stand in for the missing signal.
 */
beforeEach(function () {
    $this->ini = file_get_contents(base_path('docker/php/php.ini'));
    $this->dockerfile = file_get_contents(base_path('Dockerfile'));
    $this->entrypoint = file_get_contents(base_path('docker/entrypoint/entrypoint.sh'));
});

test('cli opcache is enabled', function () {
    expect($this->ini)->toMatch('/^opcache\.enable_cli\s*=\s*1$/m');
});

test('cli opcache uses an on disk file cache', function () {
    // Without this, enable_cli is a measured regression rather than a win.
    expect($this->ini)->toMatch('/^opcache\.file_cache\s*=\s*\/tmp\/opcache$/m');
});

test('the shared php ini never disables shared memory', function () {
    // THE regression guard. php.ini lands in conf.d, which php-fpm reads too,
    // so file_cache_only here costs ~30x on every web request while looking
    // like a CLI tuning line. It belongs in docker/php/cli instead.
    expect($this->ini)->not->toMatch('/^\s*opcache\.file_cache_only\s*=/m');
});

test('the cli only ini skips shared memory', function () {
    // Same measured win as before (819ms -> 431ms per forked schedule:run),
    // now scoped to the roles that actually benefit.
    $cliIni = file_get_contents(base_path('docker/php/cli/99-cli-opcache.ini'));

    expect($cliIni)->toMatch('/^opcache\.file_cache_only\s*=\s*1$/m');
});

test('the cli only ini is not installed into conf d', function () {
    // conf.d is read by BOTH SAPIs. The whole point is that nothing loads this
    // directory unless PHP_INI_SCAN_DIR names it explicitly.
    expect($this->dockerfile)->toMatch('#COPY docker/php/cli /usr/local/etc/php/cli-conf\.d#')
        ->and($this->dockerfile)->not->toMatch('#COPY docker/php/cli.*conf\.d/\d#');
});

test('only the cli roles opt into the cli ini', function () {
    // The app role serves web traffic and must never carry file_cache_only.
    $appRole = preg_match('/enable_cli_only_opcache/', $this->entrypoint);

    expect($appRole)->toBe(1, 'the helper should be defined once');

    // Called for the scheduler role (which carries the queue here -- there is
    // no separate queue container), and nowhere in the app role's path.
    expect(preg_match_all('/^\s+enable_cli_only_opcache$/m', $this->entrypoint))->toBe(1);
});

test('the cli ini scan dir appends rather than replaces', function () {
    // A PHP_INI_SCAN_DIR WITHOUT a leading colon REPLACES the default scan
    // directory, silently dropping conf.d/99-app.ini — taking memory_limit,
    // the upload limits and opcache.enable with it. The colon is the whole
    // difference between an override and a wipe.
    expect($this->entrypoint)->toMatch('/PHP_INI_SCAN_DIR="\:\/usr\/local\/etc\/php\/cli-conf\.d"/');
});

test('fpm opcache stays enabled alongside the cli settings', function () {
    // FPM is long-lived and must keep its own shared-memory opcache.
    expect($this->ini)->toMatch('/^opcache\.enable\s*=\s*1$/m');
});

test('the build asserts both ini paths', function () {
    // Build-time assertions so a regression fails the build rather than
    // quietly costing 30x in production, which no healthcheck would notice.
    expect($this->dockerfile)->toContain('opcache.file_cache_only is set for the DEFAULT ini')
        ->and($this->dockerfile)->toContain('cli-conf.d did not apply file_cache_only');
});

test('opcache directives are declared exactly once', function (string $directive) {
    // A duplicate wins for BOTH SAPIs regardless of which section it sits in,
    // so a second declaration silently reconfigures php-fpm too.
    $matches = preg_match_all('/^'.preg_quote($directive, '/').'\s*=/m', $this->ini);

    expect($matches)->toBe(1);
})->with([
    'opcache.enable',
    'opcache.enable_cli',
    'opcache.file_cache',
    'opcache.max_accelerated_files',
    'opcache.validate_timestamps',
]);

test('the accelerated file limit clears the application tree', function () {
    // ~11.7k PHP files ship in the image and the file cache holds one entry per
    // compiled file, so the limit shared with FPM needs headroom above that.
    expect($this->ini)->toMatch('/^opcache\.max_accelerated_files\s*=\s*(\d+)$/m');

    preg_match('/^opcache\.max_accelerated_files\s*=\s*(\d+)$/m', $this->ini, $m);

    expect((int) $m[1])->toBeGreaterThan(11711);
});

test('the file cache directory is created in the image', function () {
    // Must precede the COPY of the ini that references it: a `php` run between
    // the two would fatal.
    expect($this->dockerfile)->toMatch('/mkdir -p \/tmp\/opcache/');
});

test('the file cache directory is created before the ini is installed', function () {
    $mkdir = strpos($this->dockerfile, 'mkdir -p /tmp/opcache');
    $copyIni = strpos($this->dockerfile, 'COPY docker/php/php.ini');

    expect($mkdir)->not->toBeFalse()
        ->and($copyIni)->not->toBeFalse()
        ->and($mkdir)->toBeLessThan($copyIni);
});

test('the image gives the file cache directory to www-data', function () {
    // The entrypoint's artisan calls run as root, but the scheduler's forked
    // processes do not.
    expect($this->dockerfile)->toMatch('/chown www-data:www-data \/tmp\/opcache/');
});

test('the entrypoint recreates the file cache directory', function () {
    // Belt and braces for a volume or tmpfs mounted over /tmp, which would
    // wipe the directory the image created and fatal every php call below.
    expect($this->entrypoint)->toMatch('/mkdir -p \/tmp\/opcache/');
});

test('the entrypoint guard runs before any php is invoked', function () {
    // Including before the APP_KEY check, whose own failure path is a log line
    // that a dead PHP would never reach.
    //
    // Compared by LINE NUMBER over non-comment lines only: the guard's own
    // comment block says the words "php artisan", so a raw strpos for that
    // string finds the prose rather than the first real invocation.
    $lines = preg_split('/\R/', $this->entrypoint);

    $guardLine = null;
    $firstPhpLine = null;

    foreach ($lines as $number => $line) {
        if (preg_match('/^\s*#/', $line)) {
            continue;
        }

        if ($guardLine === null && str_contains($line, 'mkdir -p /tmp/opcache')) {
            $guardLine = $number;
        }

        if ($firstPhpLine === null && preg_match('/(^|[^\w`])php artisan/', $line)) {
            $firstPhpLine = $number;
        }
    }

    expect($guardLine)->not->toBeNull()
        ->and($firstPhpLine)->not->toBeNull()
        ->and($guardLine)->toBeLessThan($firstPhpLine);
});
