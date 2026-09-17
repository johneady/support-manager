# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Support Manager container image.
#
# ALPINE (musl) throughout. Was Debian bookworm until the image size became the
# problem. Almost all of the saving is the BASE, not anything this app ships:
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
FROM php:8.5-cli-alpine@sha256:dae77e6aa4934d22b903da93e0e506c34032f5d8f8f91693d2cbf6e2724ddf73 AS vendor

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
# php:8.5-fpm-alpine @ PHP 8.5.10
FROM php:8.5-fpm-alpine@sha256:630c234abe38c0e9e4726ff59d5af6fc8f573e35939b143580129f2405ea8a74 AS runtime

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
# opcache is NOT installed here: Zend OPcache is compiled into the php base
# image already. Running docker-php-ext-install opcache against it configures,
# compiles nothing, and then dies on "cp: cannot stat 'modules/*'". It only
# needs enabling, which docker/php/php.ini does.
#
# Each extension is installed in its OWN invocation, with no -j parallel flag.
# docker-php-ext-install builds in a shared /usr/src/php/ext tree, so a parallel
# batch races on the per-extension .libs/ and modules/ directories, failing with
# "mkdir: cannot create directory 'collator/.libs': File exists".
# ctype, dom, fileinfo, filter, hash, iconv, json, libxml, mbstring, openssl,
# pcre, session and tokenizer are already compiled into the base image.
#
# The -dev packages are needed only to COMPILE the extensions above. They go in
# as a `.build-deps` VIRTUAL package and come out in the SAME RUN, so the
# toolchain is never written to a stored layer -- removing it in a later RUN
# would not shrink anything (see point 2 in the header).
#
# The scanelf pass is what keeps the image working: it asks the freshly built
# .so files which shared libraries they link against and re-adds those as real
# runtime deps (so:libicuuc.so.78, so:libzip.so.5 and friends) BEFORE
# `apk del .build-deps` runs. Without it the del takes icu's and libzip's
# runtime libraries with it and the image starts with "Unable to load dynamic
# library 'zip'" and no ZipArchive class -- the same failure the old explicit
# libzip4/libicu72 line existed to prevent, solved generically.
#
# bash is not in the Alpine base (busybox ash only) and entrypoint.sh is
# #!/usr/bin/env bash. mariadb-client replaces Debian's default-mysql-client.
RUN apk add --no-cache nginx supervisor curl bash mariadb-client

RUN set -eux; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS icu-dev libzip-dev; \
    docker-php-ext-install intl; \
    docker-php-ext-install zip; \
    docker-php-ext-install pdo_mysql; \
    runDeps="$(scanelf --needed --nobanner --format '%n#p' --recursive /usr/local/lib/php/extensions \
        | tr ',' '\n' | sort -u | awk 'system("[ -e /usr/local/lib/" $1 " ]") == 0 { next } { print "so:" $1 }')"; \
    apk add --no-cache $runDeps; \
    apk del --no-network .build-deps

# Alpine and Debian disagree on where supervisor keeps its config, and the
# difference is silent until the container crash-loops:
#   Alpine: /etc/supervisord.conf            + [include] /etc/supervisor.d/*.ini
#   Debian: /etc/supervisor/supervisord.conf + [include] /etc/supervisor/conf.d/*.conf
# The CMD and the supervisord.conf COPY below both speak the Debian layout, so
# rebuild that layout here rather than rewriting them. The trailing grep is the
# guard: if an Alpine update moves that [include] line the sed matches nothing
# and the container would boot with NO programs while looking healthy.
RUN set -eux; \
    mkdir -p /etc/supervisor/conf.d; \
    sed -e 's#^files = /etc/supervisor.d/\*\.ini#files = /etc/supervisor/conf.d/*.conf#' \
        /etc/supervisord.conf > /etc/supervisor/supervisord.conf; \
    grep -q '^files = /etc/supervisor/conf.d/\*\.conf$' /etc/supervisor/supervisord.conf

# The CLI opcache file_cache directory, created BEFORE the ini that references
# it is in place. PHP treats a missing or unwritable opcache.file_cache as a
# startup FATAL ("must be a full path of an accessible directory") rather than
# a warning it can degrade past, so every `php` invocation in the entrypoint —
# migrations included — would die before the application ever booted.
# www-data owns it because artisan runs as root in the entrypoint but the
# scheduler's forked processes do not.
RUN mkdir -p /tmp/opcache && chown www-data:www-data /tmp/opcache

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
# Alpine's nginx includes /etc/nginx/http.d/*.conf and has NO sites-available /
# sites-enabled pair. Writing to http.d/default.conf also OVERWRITES Alpine's
# stock default server, which is what removes it -- copying to sites-available/
# instead leaves the vhost never included and Alpine's default answering :80,
# so the container serves 404s while supervisor reports everything RUNNING.
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf
COPY docker/entrypoint/supervisord.conf /etc/supervisor/conf.d/app.conf

# Fail the BUILD on a broken vhost or a missing extension rather than in
# production. Every trap here is silent at build time and loud much later: a
# missing pdo_mysql surfaces at the first query, a missing intl at the first
# formatted date, an ini that never reached conf.d by serving every request
# uncompiled. `nginx -t` additionally turns a future Alpine layout change into
# a failed build instead of a 404.
#
# Placed AFTER the php.ini COPY above so it validates the shipped config too.
#
# OPcache must be spelled "Zend OPcache": it is a Zend extension, so BOTH
# extension_loaded("opcache") and `php -m | grep -ix opcache` report it missing
# on an image where it is loaded and enabled. Do not "tidy" that to lowercase.
RUN nginx -t

RUN set -eu; \
    php -r 'foreach (["intl", "zip", "pdo_mysql", "Zend OPcache"] as $e) { if (! extension_loaded($e)) { fwrite(STDERR, "FATAL: php extension \"$e\" missing from image\n"); exit(1); } } \
        if (! ini_get("opcache.enable")) { fwrite(STDERR, "FATAL: opcache present but not enabled -- check docker/php/php.ini reached conf.d\n"); exit(1); } \
        echo "extension check passed: intl zip pdo_mysql opcache(enabled)\n";'

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
CMD ["supervisord", "-c", "/etc/supervisor/supervisord.conf", "-n"]
