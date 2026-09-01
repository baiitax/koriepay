<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdmin
{
    /**
     * Handle an incoming request.
     * * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role  <- This allows us to pass 'superadmin' from web.php
     */
    public function handle(Request $request, Closure $next, string $role = 'admin'): Response
    {
        if (auth()->check() && (auth()->user()->role === $role || auth()->user()->role === 'superadmin')) {
            return $next($request);
        }

        abort(403, "Unauthorized: Access restricted to KoriePay Command Center.");
    }
}