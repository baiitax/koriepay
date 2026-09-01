<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     * The ...$roles syntax allows us to pass multiple allowed roles to a single route.
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if the user is logged in
        if (!Auth::check()) {
            return redirect('/login');
        }

        // 2. Check if their role is in the list of allowed roles for this route
        if (!in_array(Auth::user()->role, $roles)) {
            abort(403, 'Unauthorized: You do not have permission to access this SahelPay Portal.');
        }

        return $next($request);
    }
}
