<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {

        // ── Global security headers on every response (Phase 0) ──────────────
        $middleware->prepend(\App\Http\Middleware\SecurityHeaders::class);

        // ── Trust all proxies (Vercel / serverless edge) ─────────────────────
        // Required so Laravel detects HTTPS and generates secure URLs behind
        // Vercel's load balancer. Safe on localhost (no forwarded headers).
        $middleware->trustProxies(at: '*');

        // ── Role aliases ──────────────────────────────────────────────────────
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckAdmin::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);

        // ── Locale negotiation (EN/FR/HA) ─────────────────────────────────────
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
        ]);

        // ── API throttle for the public webhook surface ───────────────────────
        $middleware->throttleApi('60,1');

    })

    ->withMiddleware(function (Middleware $middleware) {
        // Signed/verified webhooks bypass CSRF (they are authenticated by HMAC signature)
        $middleware->validateCsrfTokens(except: [
            'webhook/paystack',
            'api/webhooks/*',
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        // Surface exception class on /up so we can diagnose Vercel 500s without
        // APP_DEBUG (never include the message — it can contain DSNs).
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('up') || $request->header('X-KoriePay-Debug') === '1') {
                return response()->json([
                    'status' => 'error',
                    'exception' => $e::class,
                    'at' => basename($e->getFile()).':'.$e->getLine(),
                ], 500);
            }
        });
    })
    ->create();
