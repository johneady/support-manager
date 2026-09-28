<?php

/**
 * Guards the entrypoint's handling of the attachment volume's ownership.
 *
 * The entrypoint hands the CACHE trees (bootstrap/cache, storage/framework,
 * storage/logs) back to www-data with a recursive chown, which is safe because
 * those are rebuilt from the image on every boot. storage/app was not handled
 * at all — and it is different in kind: a persistent volume holding ticket
 * attachments.
 *
 * That gap went unnoticed until the move from Debian to Alpine changed
 * www-data from uid 33 to uid 82. The volume survived the rebuild with
 * storage/app, app/private and app/public still owned by uid 33, and php-fpm
 * no longer runs as that owner, so attaching a file to a ticket fails below
 * the application: Filament reports only its generic "There was an error while
 * attempting to load this page", and laravel.log stays empty, because the
 * application never sees the filesystem error to log it. Nothing in the app
 * surfaces a regression here, which is why these assertions stand in for the
 * missing signal.
 *
 * Two properties of the fix are load-bearing and easy to "tidy" away:
 *
 *  1. The test must be `-user www-data`, NEVER a hardcoded uid. Pinning 82
 *     would trade uid 33 for the next base-image surprise; resolving the name
 *     is what makes this self-heal on any future uid change.
 *
 *  2. storage/app must NOT be folded into the recursive chown above it. That
 *     volume grows with every attachment, and this runs before supervisord
 *     starts nginx — a recursive walk leaves the container unreachable with
 *     Traefik seeing no backend. The filtered form costs one stat per
 *     directory when everything is already correct.
 */
beforeEach(function () {
    $this->entrypoint = file_get_contents(base_path('docker/entrypoint/entrypoint.sh'));
});

test('the entrypoint hands the attachment volume mount points to www-data', function () {
    expect($this->entrypoint)->toMatch('/for d in \/var\/www\/html\/storage\/app\b/');
});

test('the entrypoint repairs ownership inside the attachment volume', function () {
    expect($this->entrypoint)->toMatch('/find\s+"\$root"\s+-mindepth 1\s+!\s*-user www-data/');
});

test('the web server drops to www-data before serving', function () {
    // php-fpm used to do this in its pool config. FrankenPHP has no such
    // setting, so the entrypoint must: without it the server runs as root and
    // every attachment it writes is root-owned.
    expect($this->entrypoint)->toMatch('/exec setpriv --reuid=www-data --regid=www-data --init-groups "\$@"/');
});

test('the scheduler drops to www-data before running', function () {
    // It shares the log and attachment volumes with the web role, so a
    // root-owned laravel.log from here would lock the web server out of it.
    expect($this->entrypoint)->toMatch('/exec setpriv --reuid=www-data --regid=www-data --init-groups php artisan schedule:work/');
});

test('the opcache file cache is handed to www-data after the root artisan calls', function () {
    // opcache creates its file_cache directories 0700, so the ones root just
    // made are unusable to the www-data roles unless they are handed back.
    expect($this->entrypoint)->toMatch('/chown -R www-data:www-data \\\\\s*\n\s*\/tmp\/opcache/');
});

test('the ownership repair covers both the public and private roots', function () {
    expect($this->entrypoint)
        ->toContain('/var/www/html/storage/app/private')
        ->toContain('/var/www/html/storage/app/public');
});

test('the ownership repair matches on the user name rather than a hardcoded uid', function () {
    expect($this->entrypoint)->toMatch('/!\s*-user www-data/');
    expect($this->entrypoint)->not->toMatch('/-uid\s+82\b/');
});

test('the attachment volume is never added to the recursive cache chown', function () {
    // THE regression guard. storage/app looks like it belongs in the chown -R
    // next to storage/framework, but it is a growing volume rather than a
    // rebuilt cache, and walking it stalls boot before nginx is listening.
    expect($this->entrypoint)->not->toMatch('/chown -R\s+www-data:www-data[^\n]*\n(?:[^\n]*\n){0,4}?\s*\/var\/www\/html\/storage\/app\b/');
});

test('the ownership repair does not descend into the attachments themselves', function () {
    expect($this->entrypoint)->toMatch('/find\s+"\$root"\s+-mindepth 1\s+!\s*-user www-data/');
});
