<?php

declare(strict_types=1);

/**
 * Vercel serverless entrypoint (vercel-php runtime).
 *
 * Vercel has no persistent PHP-FPM; every request is handled by this single
 * serverless function which boots the full Laravel kernel. The `vercel.json`
 * route table sends all non-asset traffic here.
 */

use Illuminate\Http\Request;

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';

// Build the request from $_SERVER instead of Request::capture(): capture()
// relies on real SAPI input and can return null inside the serverless PHP
// runtime (there is no php-fpm/Apache). Constructing from globals works the
// same on Vercel and locally.
$request = Request::createFromGlobals();

// handleRequest() boots the kernel, handles the request, SENDS the response
// to the client and terminates the kernel (returns void).
$app->handleRequest($request);
