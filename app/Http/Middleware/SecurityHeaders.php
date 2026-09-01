<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * KoriePay Security Headers Middleware (Phase 0 lockdown).
 *
 * Adds baseline fintech-grade response headers to every request:
 *  - HSTS (once TLS is enforced at the edge)
 *  - Clickjacking protection
 *  - MIME sniffing prevention
 *  - Referrer privacy
 *  - Permissions policy (no cross-origin sensor/geolocation abuse)
 *  - A pragmatic Content-Security-Policy compatible with Livewire + Vite assets
 *
 * NOTE: CSP is intentionally permissive for the legacy UI (inline styles + https:
 * fonts/images). Tighten `default-src` incrementally per page group.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        // Cache-Control for sensitive pages is handled by Laravel's own defaults;
        // we only add header layers here.
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        $response->headers->set('Permissions-Policy',
            'camera=(), geolocation=(), microphone=(), payment=(), usb=()'
        );

        // HSTS — enabled when running behind TLS (always in production).
        if ($request->isSecure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy',
            "default-src 'self'; ".
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://js.paystack.co; ".
            "style-src 'self' 'unsafe-inline' https://fonts.bunny.net; ".
            "font-src 'self' https://fonts.bunny.net; ".
            "img-src 'self' data: blob: https:; ".
            "connect-src 'self' https://api.paystack.co https://checkout.paystack.com wss:; ".
            "frame-src https://checkout.paystack.com; ".
            "object-src 'none'; base-uri 'self'; form-action 'self'"
        );

        return $response;
    }
}
