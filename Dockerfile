# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Support Manager container image.
#
# The BUILD stages are Alpine (musl). The RUNTIME stage is FrankenPHP on Debian
# trixie -- see the note on stage 3 for why that one is glibc. The runtime was
# Alpine php-fpm before the move to Octane; what follows is why the build
# stages went Alpine, and why a Debian php base carries so much dead weight.
# Almost all of the saving is the BASE, not anything this app ships:
#
#   1. php:8.5-*-bookworm keeps the C toolchain (gcc, g++, cpp, binutils,
#      libc6-dev -- ~190MB) PERMANENTLY, in one 316MB layer, deliberately, so
#      `docker-php-ext-install` and `pecl install` keep working later. The
#      Alpine images put the same toolchain in a `.build-deps` virtual package
#      and `apk del` it in the SAME RUN, so it never reaches a stored layer.
#
#   2. Those 190MB CANNOT be reclaimed on Debian by removing the packages: a
#      delete in a child layer cannot shrink a parent layer, it only writes
#      whiteout entries, so the bytes still ship AND the delete costs ~1.3MB
#      more. Measured. The only Debian escape is flattening onto scratch,
#      which discards layer sharing and every piece of image metadata.
#
#   3. musl + busybox rather than glibc + GNU coreutils, and no perl (29MB) or
#      python3 (14MB) in the base at all. Neither was used here.
#
# The old note here claimed @rollup/rollup-linux-x64-gnu, @tailwindcss/oxide
# and lightningcss pinned glibc-only builds. That is stale twice over: rollup
# is no longer a dependency at all (the bundler is vite-plus), and vite-plus,
# @tailwindcss/oxide, lightningcss, oxlint, oxfmt and the yuku packages ALL
# publish -musl builds, all already resolved in package-lock.json.
#
# What musl costs: a different allocator and much smaller default thread
# stacks, which is the classic source of "fine on Debian, segfaults in
# production" for PHP extensions -- low exposure while this stays on the
# standard extensions below, higher the moment an exotic PECL one is added.
# ICU also jumps (78.1 here vs bookworm's 72.1); an ICU major CAN change
# collation ordering, so re-check if user-facing lists are ever sorted through
# Collator.
# ---------------------------------------------------------------------------

# --- Stage 1: PHP dependencies ---------------------------------------------
# php:8.5-cli-alpine @ PHP 8.5.10
FROM php:8.5-cli-alpine@sha256:93684051146ec037620855feb77f278090bde45ddc030801cd3f2a7685bc4deb AS vendor

# The extension set here must not drift BELOW the runtime stage's: composer
# validates composer.lock's platform requirements against the extensions in
# THIS image, so a thinner set means the lock is validated against something
# production does not run.
RUN set -eux; \
    apk add --no-cache git unzip; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev; \
    docker-php-ext-install intl zip; \
    runDeps="$(scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
        | tr ',' '\n' | sort -u | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }')"; \
    apk add --no-cache $runDeps; \
    apk del --no-network .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy only the manifests first so this layer caches until dependencies change.
COPY composer.json composer.lock ./

# --no-scripts: artisan is not present yet, so package:discover cannot run here.
# --no-dev: production install. The test suite is not run from this image;
# .dockerignore excludes tests/ and dev dependencies are absent.
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-scripts \
        --no-interaction \
        --prefer-dist \
        --optimize-autoloader

# --- Stage 2: frontend assets ----------------------------------------------
# node:26-alpine @ Node v26.9.0
FROM node:26-alpine@sha256:2c45bdcbf63561a54da9549612084b43ca309854a4110c87857d609ddeb61c9e AS assets

WORKDIR /app

COPY package.json package-lock.json ./

# npm ci honours the lock file exactly. The -musl optional deps resolve here
# because this base is musl; every native dep in the lock publishes one.
RUN --mount=type=cache,target=/root/.npm \
    npm ci --no-audit --no-fund

# Vite reads the Blade/PHP sources for Tailwind class detection.
COPY resources ./resources
COPY vite.config.js ./
COPY app ./app
COPY config ./config
COPY routes ./routes

# resources/css/app.css imports vendor/livewire/flux/dist/flux.css and declares
# @source over the Flux stubs and the framework's pagination views. Every vendor
# tree those directives name must be present or Tailwind scans nothing there and
# silently emits a stylesheet missing those utilities.
#
# (app.css also @sources vendor/livewire/flux-pro, which this project does not
# have — free edition. A @source glob matching nothing is a harmless no-op.)
COPY --from=vendor /app/vendor/livewire ./vendor/livewire
COPY --from=vendor /app/vendor/laravel/framework ./vendor/laravel/framework

# vite.config.js calls loadEnv(mode, process.cwd(), 'APP_') to derive the HMR
# host from APP_URL. That is dev-server-only — HMR is irrelevant to `vite build`
# — and loadEnv simply returns nothing when no .env is present, which is the
# case here because .dockerignore excludes them. The fallback warning it prints
# during the build is expected and harmless.
RUN npm run build

# --- Stage 3: runtime -------------------------------------------------------
# dunglas/frankenphp:1.12.7-php8.5.11-trixie
#
# Served by Laravel Octane in FrankenPHP worker mode: each worker boots the
# framework ONCE and then serves requests from memory, instead of php-fpm
# re-bootstrapping Laravel on every hit. One binary (Caddy + embedded PHP)
# replaces nginx, php-fpm and supervisord.
#
# Debian (glibc) for THIS stage, unlike the two build stages above. FrankenPHP
# embeds a thread-safe (ZTS) PHP and runs every request on a thread, which is
# precisely where musl's small default thread stacks and slower allocator hurt
# -- the "segfaults in production" risk the header warns about stops being
# theoretical. FrankenPHP's own docs recommend the glibc images. The vendor and
# assets stages only produce files, so they stay on Alpine.
FROM dunglas/frankenphp:1.12.7-php8.5.11-trixie@sha256:81231b570830952baa3e62db06707996a886ff0a61ca64c77d032c8a110e6bc1 AS runtime

# Extensions this application actually requires:
#   intl      — Laravel formatting, league/commonmark
#   zip       — composer//artisan tooling paths that unpack archives
#   pdo_mysql — production database driver (MariaDB too: the mysql PDO driver
#               is what MariaDB uses; there is no pdo_mariadb)
#
# Deliberately NOT installed: gd and bcmath. Neither the application code nor
# any production dependency's hard `require` uses them — this app has no image
# processing and no PDF rendering. (They appear under ext-* in composer.lock
# only as transitive `suggest` entries.)
#
# Zend OPcache is compiled into the base image already; it only needs enabling,
# which docker/php/php.ini does. install-php-extensions ships with the base and
# removes its own build dependencies in the same step, so no toolchain reaches
# a stored layer.
RUN install-php-extensions intl zip pdo_mysql

# FrankenPHP runs as www-data: the entrypoint does its root-only setup, then drops
# privileges before starting the server. Caddy writes its autosave config and
# state under XDG_CONFIG_HOME / XDG_DATA_HOME (/config and /data in this base
# image), so those go to www-data.
#
# Binding :80 as www-data needs no file capability: Docker (20.10+) sets
# net.ipv4.ip_unprivileged_port_start=0 in every container network namespace.
# `setcap` on the binary would also work but rewrites it into a new 57MB layer.
#
# No mariadb-client (the php-fpm image had one): nothing in the application
# shells out to it, and on Debian it drags in perl for ~115MB. Run mariadb /
# mariadb-dump from the database container instead.
RUN set -eux; \
    mkdir -p /config/caddy /data/caddy; \
    chown -R www-data:www-data /config/caddy /data/caddy

# The CLI opcache file_cache directory, created BEFORE the ini that references
# it is in place. PHP treats a missing or unwritable opcache.file_cache as a
# startup FATAL ("must be a full path of an accessible directory") rather than
# a warning it can degrade past, so every `php` invocation in the entrypoint —
# migrations included — would die before the application ever booted.
# www-data owns it because artisan runs as root in the entrypoint but the
# scheduler's forked processes do not.
RUN mkdir -p /tmp/opcache && chown www-data:www-data /tmp/opcache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini

# CLI-only opcache overrides, deliberately NOT in conf.d -- nothing loads this
# directory unless PHP_INI_SCAN_DIR names it, which the entrypoint does for the
# scheduler and queue roles only. This is what keeps opcache.file_cache_only
# off the Octane workers, where it costs ~30x on every request. See the file.
COPY docker/php/cli /usr/local/etc/php/cli-conf.d
# Replaces the base image's own Caddyfile, which its default command reads.
COPY docker/frankenphp/Caddyfile /etc/frankenphp/Caddyfile

# Fail the BUILD on a broken Caddyfile or a missing extension rather than in
# production. Every trap here is silent at build time and loud much later: a
# missing pdo_mysql surfaces at the first query, a missing intl at the first
# formatted date, an ini that never reached conf.d by serving every request
# uncompiled.
#
# Placed AFTER the php.ini COPY above so it validates the shipped config too.
#
# OPcache must be spelled "Zend OPcache": it is a Zend extension, so BOTH
# extension_loaded("opcache") and `php -m | grep -ix opcache` report it missing
# on an image where it is loaded and enabled. Do not "tidy" that to lowercase.
#
# `adapt` parses the Caddyfile without starting anything.
RUN frankenphp adapt --config /etc/frankenphp/Caddyfile --adapter caddyfile >/dev/null

RUN set -eu; \
    php -r 'foreach (["intl", "zip", "pdo_mysql", "Zend OPcache"] as $e) { if (! extension_loaded($e)) { fwrite(STDERR, "FATAL: php extension \"$e\" missing from image\n"); exit(1); } } \
        if (! ini_get("opcache.enable")) { fwrite(STDERR, "FATAL: opcache present but not enabled -- check docker/php/php.ini reached conf.d\n"); exit(1); } \
        if (ini_get("opcache.file_cache_only")) { fwrite(STDERR, "opcache.file_cache_only is set for the DEFAULT ini -- it would reach the Octane workers and cost ~30x per request; it belongs only in docker/php/cli\n"); exit(1); } \
        echo "extension check passed: intl zip pdo_mysql opcache(enabled)\n";'
# The CLI-only overrides must load when PHP_INI_SCAN_DIR names them AND must
# not have cost us the base ini -- a PHP_INI_SCAN_DIR without its leading colon
# REPLACES conf.d rather than appending, silently dropping memory_limit and the
# upload limits. Assert both halves here so that regression fails the build.
RUN set -eux; \
    PHP_INI_SCAN_DIR=":/usr/local/etc/php/cli-conf.d" php -r '\
        if (! ini_get("opcache.file_cache_only")) { fwrite(STDERR, "cli-conf.d did not apply file_cache_only\n"); exit(1); } \
        if (ini_get("memory_limit") !== "256M") { fwrite(STDERR, "base ini lost under PHP_INI_SCAN_DIR (missing leading colon?): memory_limit=" . ini_get("memory_limit") . "\n"); exit(1); } \
        echo "cli opcache ini OK\n";'

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor ./vendor
COPY --from=assets --chown=www-data:www-data /app/public/build ./public/build

# Belt and braces alongside .dockerignore: if public/hot ever slips into the
# build context it dies here, rather than pointing every asset URL at the
# visitor's own localhost:5173 in production.
RUN rm -f public/hot

# Discard any package/service cache that came in with the build context. These
# are generated locally WITH dev dependencies, so they name providers absent
# from a --no-dev vendor tree — laravel/boost and fruitcake/laravel-debugbar
# among them — and every artisan call then dies with "Class ... not found".
# .dockerignore already excludes them; this is the second line of defence.
# The entrypoint regenerates them at runtime against the real environment.
RUN rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
          bootstrap/cache/config.php bootstrap/cache/routes-*.php

# database/database.sqlite is the local development database and must never
# reach a production image; .dockerignore excludes it, this is the backstop.
RUN rm -f database/database.sqlite

# The worker script FrankenPHP keeps resident (see docker/frankenphp/Caddyfile).
# `octane:frankenphp` would copy it in on first start, but that command is not
# used here, so the build puts it in place from the installed Octane version.
RUN cp vendor/laravel/octane/src/Commands/stubs/frankenphp-worker.php public/frankenphp-worker.php

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

COPY docker/entrypoint/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD curl -fsS http://127.0.0.1/up || exit 1

ENTRYPOINT ["/usr/local/bin/entrypoint"]

# The entrypoint drops to www-data before exec'ing this for the app role.
# Worker count, recycling and the Octane environment are all in the Caddyfile.
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
