<?php

declare(strict_types=1);

/**
 * Vercel serverless entrypoint (vercel-php runtime).
 *
 * vercel-php CGI sets cwd to the PHP runtime directory (not the Laravel root)
 * and omits REMOTE_ADDR unless x-real-ip is present. bootstrap/serverless.php
 * normalises that before the kernel boots.
 */

$root = dirname(__DIR__);

require_once $root.'/bootstrap/serverless.php';

$storagePath = koriepay_prepare_serverless_runtime($root);

require $root.'/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $root.'/bootstrap/app.php';

if (is_string($storagePath) && $storagePath !== '') {
    $app->useStoragePath($storagePath);
}

$app->handleRequest(\Illuminate\Http\Request::createFromGlobals());
