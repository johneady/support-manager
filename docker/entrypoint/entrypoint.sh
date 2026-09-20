#!/usr/bin/env bash
set -euo pipefail

# ---------------------------------------------------------------------------
# Container entrypoint.
#
# Everything here is RUNTIME work, deliberately not baked into the image:
# migrations need a live database, and the config/route/view caches must be
# built after the real environment variables are present, not at build time
# when they would capture placeholder values.
# ---------------------------------------------------------------------------

ROLE="${CONTAINER_ROLE:-app}"

log() { printf '[entrypoint] %s\n' "$*"; }

# Turn on opcache.file_cache_only for the CLI-ONLY roles.
#
# The scheduler forks `schedule:run` every minute, which forks `queue:work
# --stop-when-empty`. Each is a short-lived process that would otherwise
# compile ~1,500 files from source; file_cache_only skips shared-memory setup
# it will never amortise and cuts that from ~819ms to ~431ms per fork.
#
# It CANNOT go in docker/php/php.ini: that file lands in conf.d, which php-fpm
# reads too, and for a long-lived FPM worker the same directive means
# filesystem I/O for ~1,000 scripts on every single request -- measured at ~30x
# slower on a sibling image on this host. So it lives in its own directory that
# only this export reaches.
#
# The LEADING COLON is load-bearing. `PHP_INI_SCAN_DIR=/path` REPLACES the
# default scan directory, which would drop conf.d/99-app.ini entirely and take
# memory_limit, the upload limits and opcache.enable with it. `:/path` APPENDS
# to the compiled-in default instead, so 99-app.ini still loads first and this
# only overrides the one directive.
#
# Exported rather than set per-command so that the processes schedule:work
# FORKS inherit it -- they are the ones actually paying the compile cost.
enable_cli_only_opcache() {
    if [ -d /usr/local/etc/php/cli-conf.d ]; then
        export PHP_INI_SCAN_DIR=":/usr/local/etc/php/cli-conf.d"
        log "CLI opcache: file_cache_only enabled for this role."
    else
        log "WARNING: /usr/local/etc/php/cli-conf.d missing; CLI opcache not tuned."
    fi
}

# Re-assert the CLI opcache file_cache directory.
#
# The Dockerfile already creates it, so this is belt and braces for the case
# where something mounts a volume or tmpfs over /tmp and wipes it. It matters
# because php.ini sets opcache.file_cache and PHP treats a missing directory as
# a startup FATAL, not a recoverable warning — so a blank /tmp would take out
# every artisan call below, including migrations, before the app booted.
# Done first, ahead of the APP_KEY check, because that check's own failure path
# is the log line beneath it and any PHP here would already be dead.
if [ ! -d /tmp/opcache ]; then
    log "Recreating /tmp/opcache (CLI opcache file cache)."
    mkdir -p /tmp/opcache && chown www-data:www-data /tmp/opcache || \
        log "WARNING: could not create /tmp/opcache; PHP may fail to start."
fi

if [ -z "${APP_KEY:-}" ]; then
    log "FATAL: APP_KEY is not set."
    log "Sessions are encrypted (SESSION_ENCRYPT=true), so a missing or changed"
    log "APP_KEY invalidates every existing session."
    log "At cutover, use the SAME key as the live HestiaCP .env."
    exit 1
fi

# APP_DEBUG=true in production leaks environment variables — database password
# included — on any unhandled exception page. Refuse rather than warn.
if [ "${APP_DEBUG:-false}" = "true" ] && [ "${APP_ENV:-production}" = "production" ]; then
    log "FATAL: APP_DEBUG=true with APP_ENV=production."
    log "Laravel's debug handler renders the full environment on error pages."
    exit 1
fi

# Wait for the database. Compose 'depends_on: service_healthy' covers the
# common case, but Dokploy may start services in a looser order.
if [ "${WAIT_FOR_DB:-true}" = "true" ]; then
    log "Waiting for database at ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
    for i in $(seq 1 60); do
        if php -r '
            $h=getenv("DB_HOST")?:"mysql"; $p=getenv("DB_PORT")?:"3306";
            $c=@fsockopen($h,(int)$p,$e,$s,2);
            exit($c ? 0 : 1);
        '; then
            log "Database reachable."
            break
        fi
        if [ "$i" = "60" ]; then
            log "FATAL: database unreachable after 60 attempts."
            exit 1
        fi
        sleep 2
    done
fi

# Storage symlink. Recreated every boot because public/ may be a fresh layer
# while storage/ is a persistent volume.
if [ ! -L /var/www/html/public/storage ]; then
    log "Linking storage/app/public -> public/storage"
    php artisan storage:link --force || log "WARNING: storage:link failed (continuing)"
fi

# public/hot is the marker `npm run dev` leaves behind. If it reaches the
# image, @vite points every asset URL at http://localhost:5173 — the visitor's
# own machine — and the frontend silently dies. .dockerignore and the Dockerfile
# both guard it; this is the runtime backstop for a dev bind mount.
if [ -f /var/www/html/public/hot ]; then
    log "WARNING: removing stale public/hot (Vite dev-server marker)."
    rm -f /var/www/html/public/hot
fi

# Only the app role runs migrations, so parallel replicas cannot race.
if [ "$ROLE" = "app" ] && [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    log "Running migrations ..."

    # --isolated takes its lock through the cache store, and CACHE_STORE is
    # "database" — so on a FRESH database the lock needs the cache_locks table
    # that the migrations themselves have not created yet, and the run dies with
    # "Base table or view not found: cache_locks". Probe for that table first and
    # only ask for isolation once it exists; the unisolated first run is safe
    # because there is nothing yet for a second container to race over.
    if php artisan db:table cache_locks --json >/dev/null 2>&1; then
        MIGRATE_ISOLATION="--isolated"
        log "cache_locks present; migrating with --isolated."
    else
        MIGRATE_ISOLATION=""
        log "cache_locks absent (fresh database); migrating without isolation."
    fi

    # shellcheck disable=SC2086 — MIGRATE_ISOLATION is intentionally unquoted.
    php artisan migrate --force $MIGRATE_ISOLATION || {
        log "FATAL: migrations failed."
        exit 1
    }
fi

# The scheduler must not start until the app role has FINISHED migrating.
# routes/console.php calls Setting::get() at SCHEDULE-BUILD time, so this role
# reads the `settings` table just to construct its task list; booting against a
# half-migrated schema crash-loops on "Base table or view not found".
#
# Wait on pending migrations, NOT on the existence of the `migrations` table:
# MigrateCommand::prepareDatabase() runs `migrate:install` to create that table
# BEFORE it applies anything, so a presence check passes within seconds of the
# app role starting and lets this role race ahead into an empty schema.
#
# `migrate:status --pending` exits 0 only when nothing is left to apply, and
# non-zero while migrations are pending OR the migrations table is still absent
# — which is exactly the wait condition wanted here.
if [ "$ROLE" != "app" ]; then
    log "Waiting for the app role to finish migrating ..."
    for i in $(seq 1 90); do
        if php artisan migrate:status --pending >/dev/null 2>&1; then
            log "Schema fully migrated."
            break
        fi
        if [ "$i" = "90" ]; then
            log "FATAL: migrations still pending after 90 attempts."
            exit 1
        fi
        sleep 2
    done
fi

log "Rebuilding caches for the live environment ..."

# Clear the COMPILED-FILE caches only, one command at a time.
#
# Deliberately NOT `optimize:clear`: that also runs cache:clear, which with
# CACHE_STORE=database DELETEs the whole `cache` table — and that table is
# SHARED by every container. The scheduler restarts on its own (redeploy,
# `restart: unless-stopped`, OOM), so an optimize:clear here silently flushes
# the live app's cache mid-traffic: the registration/invitation rate-limiter
# counters reset, briefly reopening the abuse window they exist to close, and
# the cached settings and health-alert throttle go with them.
#
# Sessions are unaffected — SESSION_DRIVER=database uses its own `sessions`
# table, which cache:clear does not touch.
#
# Each is tolerated individually: on a first boot the compiled files may simply
# not exist yet, and there is nothing stale to clear anyway.
for cache_type in config route view event; do
    php artisan "${cache_type}:clear" \
        || log "WARNING: ${cache_type}:clear failed (continuing)."
done

# Rebuilds config, routes, views and events — and, unlike optimize:clear, its
# counterpart `optimize` writes only compiled files and never touches the
# shared cache table, so it is safe to run from every role.
php artisan optimize

# The steps above run as root, so every file they emit is root-owned. php-fpm
# serves as www-data, and Blade still writes to storage/framework/views at
# runtime for any view the cache misses — that write fails with "Permission
# denied" on a root-owned directory. Hand the trees back before dropping into
# the long-running process.
chown -R www-data:www-data \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage/framework \
    /var/www/html/storage/logs 2>/dev/null || \
    log "WARNING: could not reset cache ownership (continuing)."

# The trees above are the CACHES, which are rebuilt from the image on every
# boot and so are safe to walk recursively. storage/app is different: it is a
# persistent volume holding ticket attachments, and nothing here was handing it
# back at all.
#
# That went unnoticed until the move from Debian to Alpine changed www-data
# from uid 33 to uid 82. The volume survived the rebuild with storage/app,
# app/private and app/public still owned by uid 33, and php-fpm is no longer
# that owner — so attaching a file to a ticket fails below the application,
# where Filament reports only "There was an error while attempting to load this
# page" and nothing reaches laravel.log.
#
# The mount points are fixed directly; what is INSIDE them is repaired with a
# filter rather than another `chown -R`, because this volume grows with every
# attachment and this code runs before supervisord starts nginx — a recursive
# walk over it would delay readiness while the container is unreachable and
# Traefik sees no backend. `! -user www-data` means a correctly-owned tree
# matches nothing and costs one stat per directory, and -maxdepth 2 stops above
# the attachments themselves, which www-data writes at runtime and which
# already have the right owner.
#
# The test is `-user www-data`, never a hardcoded 82. Pinning the number would
# just trade uid 33 for the next base-image surprise; resolving the NAME means
# this repairs itself on any future uid change.
for d in /var/www/html/storage/app \
         /var/www/html/storage/app/private \
         /var/www/html/storage/app/public; do
    mkdir -p "$d" 2>/dev/null || true
    chown www-data:www-data "$d" 2>/dev/null || \
        log "WARNING: could not set ownership on $d (continuing)."
done

for root in /var/www/html/storage/app/private \
            /var/www/html/storage/app/public; do
    [ -d "$root" ] || continue
    find "$root" -maxdepth 2 -mindepth 1 -type d ! -user www-data \
        -exec chown www-data:www-data {} + 2>/dev/null || \
        log "WARNING: could not repair ownership under $root (continuing)."
done

case "$ROLE" in
    app)
        log "Starting web role (nginx + php-fpm)."
        exec "$@"
        ;;
    scheduler)
        # This role carries the QUEUE as well as the timed work: routes/console.php
        # line 7 schedules `queue:work --stop-when-empty` every minute, so there is
        # deliberately no separate queue container. Every ShouldQueue notification
        # in app/Notifications — ticket replies, invitations, password resets —
        # drains here. Without this role they queue up and are never sent, while
        # the site itself looks perfectly healthy.
        #
        # It also runs tickets:close-inactive and the spatie/laravel-health checks.
        log "Starting scheduler role (schedule:work; carries the queue)."
        enable_cli_only_opcache
        exec php artisan schedule:work
        ;;
    *)
        log "Unknown CONTAINER_ROLE '$ROLE'; running command as given."
        exec "$@"
        ;;
esac
