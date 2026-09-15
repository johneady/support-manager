# syntax=docker/dockerfile:1.7

# ---------------------------------------------------------------------------
# Support Manager container image.
#
# Debian bookworm throughout, deliberately NOT Alpine: package.json pins
# @rollup/rollup-linux-x64-gnu, @tailwindcss/oxide-linux-x64-gnu and
# lightningcss-linux-x64-gnu as optionalDependencies. Those are glibc (gnu)
# builds; on Alpine's musl the ABI does not match and the Vite build either
# fails outright or silently emits nothing.
# ---------------------------------------------------------------------------

# --- Stage 1: PHP dependencies ---------------------------------------------
FROM php:8.5-cli-bookworm AS vendor

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libzip-dev libicu-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

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
FROM node:26-bookworm-slim AS assets

WORKDIR /app

COPY package.json package-lock.json ./

# npm ci honours the lock file exactly. The linux-x64-gnu optional deps resolve
# correctly here because this base is glibc.
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
FROM php:8.5-fpm-bookworm AS runtime

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
# The -dev packages are needed only to COMPILE the extensions above, so they
# are purged afterwards; a bare `apt-get purge -y --auto-remove` with no
# package list is a no-op ("0 to remove") and would leave them in the image.
#
# libzip4 and libicu72 are the RUNTIME libraries the built .so files link
# against, and they are installed explicitly: as mere dependencies of the -dev
# packages, --auto-remove takes them out along with the headers, and the image
# then starts with "Unable to load dynamic library 'zip' ... libzip.so.4:
# cannot open shared object file" and no ZipArchive class.
RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx supervisor curl \
        libzip4 libicu72 \
        default-mysql-client \
    && apt-get install -y --no-install-recommends libzip-dev libicu-dev \
    && docker-php-ext-install intl \
    && docker-php-ext-install zip \
    && docker-php-ext-install pdo_mysql \
    && apt-get purge -y --auto-remove libzip-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

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
COPY docker/nginx/default.conf /etc/nginx/sites-available/default
COPY docker/entrypoint/supervisord.conf /etc/supervisor/conf.d/app.conf

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
