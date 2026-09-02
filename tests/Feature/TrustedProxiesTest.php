<?php

declare(strict_types=1);

use App\Providers\AppServiceProvider;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

/**
 * Behind Traefik (Dokploy) the application receives plain HTTP with the
 * original scheme in X-Forwarded-Proto. These cover the wiring in
 * bootstrap/app.php that makes Laravel honour that header, which is what
 * App\Http\Middleware\SecurityHeaders gates its HSTS header on.
 */
function proxiedRequest(): Request
{
    $request = Request::create('http://localhost/test', 'GET');
    $request->server->set('REMOTE_ADDR', '10.0.0.5');
    $request->headers->set('X-Forwarded-Proto', 'https');

    return $request;
}

/**
 * Invoke only the proxy hook. Calling the provider's whole boot() would
 * re-register the spatie/laravel-health checks, which rejects duplicate names.
 */
function bootTunnelledRequests(): void
{
    $method = new ReflectionMethod(AppServiceProvider::class, 'configureTunnelledRequests');
    $method->invoke(new AppServiceProvider(app()));
}

test('trusted proxies is unset by default so no proxy is believed', function () {
    expect(config('app.trust_proxies'))->toBeNull();
});

/**
 * AppServiceProvider::configureTunnelledRequests() reads app.trust_proxies on
 * boot. An earlier version did that inside bootstrap/app.php's withMiddleware()
 * closure, which runs before the container's config binding exists and made
 * every request die with 'Class "config" does not exist'. Serving a real
 * request proves the provider resolves it.
 */
test('the application boots and serves a request with the proxy hook wired', function () {
    $this->get('/up')->assertOk();
});

test('an unset value trusts nothing outside local', function () {
    app()->detectEnvironment(fn () => 'production');
    config()->set('app.trust_proxies', null);

    bootTunnelledRequests();

    $request = proxiedRequest();
    (new TrustProxies)->handle($request, fn (Request $handled) => response('ok'));

    expect($request->isSecure())->toBeFalse();
});

test('an unset value trusts any proxy in local so tunnels serve https', function () {
    app()->detectEnvironment(fn () => 'local');
    config()->set('app.trust_proxies', null);

    bootTunnelledRequests();

    $request = proxiedRequest();
    (new TrustProxies)->handle($request, fn (Request $handled) => response('ok'));

    expect($request->isSecure())->toBeTrue();
});

test('a forwarded request stays insecure when no proxy is trusted', function () {
    $request = proxiedRequest();

    expect($request->isSecure())->toBeFalse();
});

test('a forwarded request is secure when any proxy is trusted', function () {
    TrustProxies::at('*');

    $request = proxiedRequest();

    (new TrustProxies)->handle($request, fn (Request $handled) => response('ok'));

    expect($request->isSecure())->toBeTrue()
        ->and($request->getScheme())->toBe('https');
});

test('a forwarded request is secure when the proxy ip is trusted', function () {
    TrustProxies::at(['10.0.0.5']);

    $request = proxiedRequest();

    (new TrustProxies)->handle($request, fn (Request $handled) => response('ok'));

    expect($request->isSecure())->toBeTrue();
});

test('a forwarded request stays insecure when a different proxy is trusted', function () {
    TrustProxies::at(['192.168.1.1']);

    $request = proxiedRequest();

    (new TrustProxies)->handle($request, fn (Request $handled) => response('ok'));

    expect($request->isSecure())->toBeFalse();
});

afterEach(function () {
    TrustProxies::at([]);
    Request::setTrustedProxies([], Request::HEADER_X_FORWARDED_FOR);
});
