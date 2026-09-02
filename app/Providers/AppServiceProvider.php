<?php

namespace App\Providers;

use App\Health\Checks\EmailCheck;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;
use Spatie\Health\Checks\Checks\CacheCheck;
use Spatie\Health\Checks\Checks\DatabaseCheck;
use Spatie\Health\Checks\Checks\OptimizedAppCheck;
use Spatie\Health\Checks\Checks\ScheduleCheck;
use Spatie\Health\Facades\Health;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureTunnelledRequests();
        $this->registerHealthChecks();
        $this->configureRateLimiters();
    }

    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Register health checks.
     */
    protected function registerHealthChecks(): void
    {
        Health::checks([
            CacheCheck::new(),
            DatabaseCheck::new(),
            EmailCheck::new(),
            OptimizedAppCheck::new(),
            ScheduleCheck::new(),
        ]);
    }

    /**
     * Trust the reverse proxy in front of the application, where there is one.
     *
     * Under Dokploy, Traefik terminates TLS and forwards plain HTTP, so
     * without this the framework reads the request as insecure: URLs generate
     * as http://, and App\Http\Middleware\SecurityHeaders sends no HSTS
     * header because it gates that on $request->isSecure().
     *
     * The HestiaCP deployment proxies to php-fpm on the same host, so the
     * request arrives secure on its own and needs no trusted proxies. That is
     * why this is opt-in via TRUST_PROXIES rather than simply always on:
     * trusting X-Forwarded-* headers from an arbitrary client is spoofable,
     * so it must only be enabled where a proxy really does sit in front and
     * overwrite them.
     *
     * This lives here rather than in bootstrap/app.php's withMiddleware()
     * closure, which runs while the application is still being built — the
     * container's 'config' and 'env' bindings do not exist yet at that point,
     * so resolving either there fails to boot the application at all.
     */
    protected function configureTunnelledRequests(): void
    {
        $proxies = config('app.trust_proxies');

        if (filled($proxies)) {
            TrustProxies::at($proxies === '*'
                ? '*'
                : array_map(trim(...), explode(',', $proxies)));

            return;
        }

        /**
         * Nothing configured: trust everything in local development only, so
         * an ngrok-style tunnel serves https:// URLs without extra setup. In
         * every other environment an unset value means trust nothing.
         */
        if ($this->app->environment('local')) {
            TrustProxies::at('*');
        }
    }

    /**
     * Configure rate limiters.
     */
    protected function configureRateLimiters(): void
    {
        RateLimiter::for('invitation', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        RateLimiter::for('registration', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });
    }
}
