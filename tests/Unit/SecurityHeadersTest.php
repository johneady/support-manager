<?php

declare(strict_types=1);

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

test('security headers are set correctly', function () {
    $middleware = new SecurityHeaders;
    $request = Request::create('/test', 'GET');
    $response = $middleware->handle($request, fn ($req) => new JsonResponse(['message' => 'test']));

    // 1. Prevent Clickjacking
    expect($response->headers->get('X-Frame-Options'))->toBe('DENY');

    // 2. Prevent MIME-sniffing
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');

    // 3. Control Referrer Information
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');

    // 4. Force HTTPS (HSTS) - Not set on non-secure requests
    expect($response->headers->get('Strict-Transport-Security'))->toBeNull();

    // 5. Disable unused browser features
    $expectedPermissions = 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)';
    expect($response->headers->get('Permissions-Policy'))->toBe($expectedPermissions);

    // 6. Basic XSS protection for older browsers
    expect($response->headers->get('X-XSS-Protection'))->toBe('1; mode=block');
});

test('hsts header is set on secure requests', function () {
    $middleware = new SecurityHeaders;
    $request = Request::create('/test', 'GET');
    $request->server->set('HTTPS', 'on');
    $response = $middleware->handle($request, fn ($req) => new JsonResponse(['message' => 'test']));

    expect($response->headers->get('Strict-Transport-Security'))->toBe('max-age=31536000; includeSubDomains; preload');
});
