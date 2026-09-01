<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

/**
 * KoriePay permission middleware (RBAC Phase 4 foundation).
 *
 * Usage:  ->middleware('permission:transaction.reverse')
 *
 * Resolves permissions against the role→permission mapping stored in the
 * `role_permissions` table (seeded) until the Spatie RBAC integration is
 * wired to user accounts in Phase 4.
 */
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        $allowed = Gate::forUser($user)->allows($permission);

        if (! $allowed) {
            abort(403, "Unauthorized: missing permission [{$permission}].");
        }

        return $next($request);
    }
}
