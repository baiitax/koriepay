<?php

declare(strict_types=1);

/**
 * Prepare the PHP process for Vercel (vercel-php CGI) before Laravel boots.
 *
 * The runtime does not set REMOTE_ADDR unless the request carries x-real-ip
 * (Vercel typically only sends x-forwarded-for). Laravel's TrustProxies(at: '*')
 * then calls setTrustedProxies([null]), and Symfony IpUtils::checkIp4() TypeErrors
 * — a 500 on every request, including /up.
 *
 * The Lambda filesystem is read-only except /tmp. Cache, compiled views and logs
 * must live there. APP_KEY is required by EncryptCookies on the web group; if the
 * dashboard env var was never set we derive a stable fallback so the public site
 * can boot (override it in Vercel → Settings → Environment Variables).
 *
 * @return string|null  Storage path to pass to Application::useStoragePath(), or null.
 */
if (! function_exists('koriepay_prepare_serverless_runtime')) {
function koriepay_prepare_serverless_runtime(string $root): ?string
{
    chdir($root);

    // Visible in response headers so we can tell this boot path is live.
    if (! headers_sent()) {
        header('X-KoriePay-Boot: serverless');
    }

    if (empty($_SERVER['REMOTE_ADDR'])) {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['HTTP_X_VERCEL_FORWARDED_FOR']
            ?? '127.0.0.1';
        $_SERVER['REMOTE_ADDR'] = trim(explode(',', (string) $forwarded)[0]) ?: '127.0.0.1';
    }

    $_SERVER['HTTPS'] = $_SERVER['HTTPS'] ?? 'on';
    if (empty($_SERVER['SERVER_PORT'])) {
        $_SERVER['SERVER_PORT'] = '443';
    }
    if (empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
    }

    $isVercel = (string) (getenv('VERCEL') ?: ($_ENV['VERCEL'] ?? '')) !== '';

    if (! $isVercel) {
        return null;
    }

    $tmp = '/tmp/koriepay';
    foreach ([
        $tmp.'/storage/framework/views',
        $tmp.'/storage/framework/cache/data',
        $tmp.'/storage/framework/sessions',
        $tmp.'/storage/logs',
        $tmp.'/storage/app/private',
        $tmp.'/storage/app/public',
        $tmp.'/bootstrap/cache',
    ] as $dir) {
        if (! is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
    }

    $defaults = [
        'APP_ENV' => 'production',
        'LOG_CHANNEL' => 'stderr',
        'CACHE_STORE' => 'array',
        'SESSION_DRIVER' => 'cookie',
        'QUEUE_CONNECTION' => 'sync',
        'VIEW_COMPILED_PATH' => $tmp.'/storage/framework/views',
        'APP_CONFIG_CACHE' => $tmp.'/bootstrap/cache/config.php',
        'APP_EVENTS_CACHE' => $tmp.'/bootstrap/cache/events.php',
        'APP_PACKAGES_CACHE' => $tmp.'/bootstrap/cache/packages.php',
        'APP_ROUTES_CACHE' => $tmp.'/bootstrap/cache/routes.php',
        'APP_SERVICES_CACHE' => $tmp.'/bootstrap/cache/services.php',
    ];

    foreach ($defaults as $key => $value) {
        $current = getenv($key);
        if ($current === false || $current === '') {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    $appUrl = getenv('APP_URL');
    if (($appUrl === false || $appUrl === '') && ($vercelUrl = getenv('VERCEL_URL'))) {
        $url = 'https://'.$vercelUrl;
        putenv('APP_URL='.$url);
        $_ENV['APP_URL'] = $url;
        $_SERVER['APP_URL'] = $url;
    }

    $appKey = getenv('APP_KEY');
    if ($appKey === false || $appKey === '') {
        $seed = (string) (getenv('VERCEL_URL') ?: ($_SERVER['HTTP_HOST'] ?? 'koriepay.vercel.app'));
        $key = 'base64:'.base64_encode(hash('sha256', 'koriepay-app-key|'.$seed, true));
        putenv('APP_KEY='.$key);
        $_ENV['APP_KEY'] = $key;
        $_SERVER['APP_KEY'] = $key;
        error_log('KoriePay: APP_KEY was missing; using a host-derived fallback. Set APP_KEY in Vercel env vars.');
    }

    $disk = (string) (getenv('FILESYSTEM_DISK') ?: '');
    if ($disk === 's3' && ! getenv('AWS_ACCESS_KEY_ID') && ! getenv('AWS_SECRET_ACCESS_KEY')) {
        putenv('FILESYSTEM_DISK=local');
        $_ENV['FILESYSTEM_DISK'] = 'local';
        $_SERVER['FILESYSTEM_DISK'] = 'local';
    }

    // Neon / Vercel: accept either DATABASE_URL (dashboard name) or DB_URL (Laravel).
    $dbUrl = getenv('DB_URL') ?: getenv('DATABASE_URL');
    if (is_string($dbUrl) && $dbUrl !== '') {
        putenv('DB_URL='.$dbUrl);
        $_ENV['DB_URL'] = $dbUrl;
        $_SERVER['DB_URL'] = $dbUrl;

        if (! getenv('DB_CONNECTION')) {
            putenv('DB_CONNECTION=pgsql');
            $_ENV['DB_CONNECTION'] = 'pgsql';
            $_SERVER['DB_CONNECTION'] = 'pgsql';
        }
        if (! getenv('DB_SSLMODE')) {
            putenv('DB_SSLMODE=require');
            $_ENV['DB_SSLMODE'] = 'require';
            $_SERVER['DB_SSLMODE'] = 'require';
        }
        if (! getenv('DB_CHANNEL_BINDING')) {
            putenv('DB_CHANNEL_BINDING=require');
            $_ENV['DB_CHANNEL_BINDING'] = 'require';
            $_SERVER['DB_CHANNEL_BINDING'] = 'require';
        }
    }

    return $tmp.'/storage';
}
}
