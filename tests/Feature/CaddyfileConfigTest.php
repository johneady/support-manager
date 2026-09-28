<?php

declare(strict_types=1);

/**
 * Guards the FrankenPHP Caddyfile contract in docker/frankenphp/Caddyfile.
 *
 * Livewire answers every table sort, filter and pagination click with an
 * application/json body of server-rendered markup (~65% Blade indentation
 * whitespace). Under the old nginx image those responses once went out
 * uncompressed: a 50-row sort shipped ~790kB and took ~7s to download while
 * TTFB stayed ~230ms. Caddy's `encode` covers JSON by default, so the guard is
 * simply that the directive stays.
 *
 * FrankenPHP also runs Octane's worker script directly, so the environment the
 * `octane:frankenphp` launcher would have passed lives in this file instead.
 *
 * Nothing in the application surfaces a regression here — the page still renders
 * correctly, just slowly — so these assertions stand in for the missing signal.
 */
beforeEach(function () {
    $this->config = file_get_contents(base_path('docker/frankenphp/Caddyfile'));
});

test('responses are compressed', function () {
    expect($this->config)->toMatch('/^\s*encode\s+.*\bgzip\b/m');
});

test('the request body cap matches the php upload limits', function () {
    $ini = file_get_contents(base_path('docker/php/php.ini'));

    expect($ini)->toMatch('/^post_max_size\s*=\s*8M$/m')
        ->and($this->config)->toMatch('/^\s*max_size\s+8MB$/m');
});

test('built assets are cached as immutable', function () {
    expect($this->config)->toMatch('/@build\s+path\s+\/build\/\*/')
        ->and($this->config)->toContain('Cache-Control "public, max-age=31536000, immutable"');
});

test('dotfiles are never served', function () {
    expect($this->config)->toMatch('/respond\s+@dotfiles\s+404/');
});

test('requests are routed through the octane worker', function () {
    expect($this->config)->toContain('file /var/www/html/public/frankenphp-worker.php')
        ->and($this->config)->toContain('try_files {path} frankenphp-worker.php');
});

test('the worker gets the environment octane would have passed it', function (string $line) {
    // FrankenPHP runs the worker directly rather than through
    // `octane:frankenphp`, so these are the launcher's variables written out.
    // LARAVEL_OCTANE in particular is read by the framework itself.
    expect($this->config)->toMatch('/^\s*'.preg_quote($line, '/').'$/m');
})->with([
    'env APP_BASE_PATH /var/www/html',
    'env APP_PUBLIC_PATH /var/www/html/public',
    'env LARAVEL_OCTANE 1',
    'env MAX_REQUESTS 500',
]);

test('the worker execution limit matches php', function () {
    $ini = file_get_contents(base_path('docker/php/php.ini'));

    preg_match('/^max_execution_time\s*=\s*(\d+)$/m', $ini, $iniLimit);
    preg_match('/^\s*env REQUEST_MAX_EXECUTION_TIME (\d+)$/m', $this->config, $workerLimit);

    expect($workerLimit[1] ?? null)->toBe($iniLimit[1] ?? 'missing');
});

test('the image serves with this caddyfile', function () {
    $dockerfile = file_get_contents(base_path('Dockerfile'));

    expect($dockerfile)->toContain('COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile')
        ->and($dockerfile)->toContain('CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]');
});
