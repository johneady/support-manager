<?php

declare(strict_types=1);

/**
 * Guards the nginx gzip contract in docker/nginx/default.conf.
 *
 * Livewire answers every Filament table sort, filter and pagination click with
 * an application/json body of Filament's row markup (~18kB/row, ~65% Blade
 * indentation whitespace). Debian's nginx.conf ships `gzip on` with gzip_types
 * commented out, and nginx's built-in default covers text/html ONLY, so those
 * responses went out uncompressed: a 50-row sort shipped ~790kB and took ~7s to
 * download while TTFB stayed ~230ms.
 *
 * Nothing in the application surfaces a regression here — the page still renders
 * correctly, just slowly — so these assertions stand in for the missing signal.
 */
beforeEach(function () {
    $this->config = file_get_contents(base_path('docker/nginx/default.conf'));
});

test('gzip is enabled', function () {
    expect($this->config)->toMatch('/^\s*gzip\s+on;/m');
});

test('gzip covers the livewire json content type', function () {
    expect($this->config)->toMatch('/^\s*application\/json;?$/m');
});

test('gzip applies to proxied requests', function () {
    // This nginx always sits behind Traefik, so every request arrives proxied.
    // nginx defaults to `gzip_proxied off`, which skips those entirely — without
    // this directive the gzip_types list never fires in production.
    expect($this->config)->toMatch('/^\s*gzip_proxied\s+any;/m');
});

test('gzip covers the other text based response types', function (string $type) {
    // The final entry in the gzip_types list carries the trailing semicolon.
    expect($this->config)->toMatch('/^\s*'.preg_quote($type, '/').';?$/m');
})->with([
    'application/javascript',
    'application/xml',
    'image/svg+xml',
    'text/css',
    'text/javascript',
    'text/plain',
    'text/xml',
]);

test('gzip skips responses too small to benefit', function () {
    expect($this->config)->toMatch('/^\s*gzip_min_length\s+\d+;/m');
});

test('responses vary on accept encoding so proxies cache both forms', function () {
    expect($this->config)->toMatch('/^\s*gzip_vary\s+on;/m');
});
