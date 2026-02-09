<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // 1. Prevent Clickjacking
        $response->headers->set('X-Frame-Options', 'DENY');

        // 2. Prevent MIME-sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // 3. Control Referrer Information
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // 4. Force HTTPS (HSTS) - Only active if the request is secure
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains; preload');
        }

        // 5. Disable unused browser features
        // Note: Add 'geolocation=(self)' if you actually need the user's location.
        $permissions = [
            'camera=()',
            'microphone=()',
            'geolocation=()',
            'payment=()',
            'usb=()',
            'fullscreen=(self)',
        ];
        $response->headers->set('Permissions-Policy', implode(', ', $permissions));

        // 6. Basic XSS protection for older browsers
        $response->headers->set('X-XSS-Protection', '1; mode=block');

        return $response;
    }
}
