<?php

namespace App\Providers;

use App\Models\Device;
use App\Models\FxRate;
use App\Models\LoginEvent;
use App\Models\User;
use App\Observers\FxRateObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Engage the Blackbox wiretap
        FxRate::observe(FxRateObserver::class);

        $this->wireRbacGates();

        $this->listenToIdentityEvents();

        $this->configureApiRateLimiter();
    }

    /**
     * Named API rate limiter (used by throttle:api on the v1 payment surface).
     * Keyed by authenticated user id, else IP. 120 requests/min default —
     * tighten per-endpoint where needed.
     */
    protected function configureApiRateLimiter(): void
    {
        RateLimiter::for('api', fn (Request $request) => Limit::perMinute(120)
            ->by($request->user()?->id ?: $request->ip()));
    }

    /**
     * Wire the RBAC gate against the `role_permissions` matrix (seeded by the
     * Phase 2 migration set). `superadmin` carries the wildcard `*`; other
     * roles resolve their permission set per-request.
     *
     * Guarded by Schema::hasTable so the app boots even before the Phase 2
     * migrations have run (fresh checkouts, CI without migrated DB).
     *
     * This is the authorization FOUNDATION for the dashboard; it is not a
     * substitute for route middleware — routes keep `auth` + `role` +
     * `permission` server-side checks (never trust frontend visibility).
     */
    protected function wireRbacGates(): void
    {
        Gate::before(function ($user, $ability) {
            // Super admin is the highest clearance — wildcard.
            if (($user->role ?? null) === 'superadmin') {
                return true;
            }

            if (! Schema::hasTable('role_permissions')) {
                return false;
            }

            return \Illuminate\Support\Facades\DB::table('role_permissions')
                ->where('role', $user->role ?? '')
                ->where('permission', $ability)
                ->exists() ?: null;
        });
    }

    /**
     * Identity telemetry (Phase 4): record login success/failure/lockout
     * events, refresh the device fingerprint, and stamp last_login_at.
     *
     * Listeners are ALWAYS registered (boot may run before migrations on a
     * fresh checkout); each handler guards against missing tables at
     * dispatch time, so they are safe no-ops on a pre-migration database.
     */
    protected function listenToIdentityEvents(): void
    {
        Event::listen(Login::class, function (Login $event) {
            $user = $event->user;
            if (! $user instanceof User || ! Schema::hasTable('login_events')) {
                return;
            }

            $ip = request()->ip();
            $ua = request()->userAgent();

            Device::register($user, $ip, $ua);
            $device = Device::query()
                ->where('user_id', $user->id)
                ->where('device_id', Device::fingerprint($ip, $ua))
                ->first();

            $user->forceFill(['last_login_at' => now()])->save();

            LoginEvent::record(
                LoginEvent::EVENT_SUCCESS,
                $user->id,
                $ip,
                $ua,
                $device?->device_id,
                ['guard' => $event->guard],
            );
        });

        Event::listen(Failed::class, function (Failed $event) {
            if (! Schema::hasTable('login_events')) {
                return;
            }

            // NEVER store the attempted credentials — only that a failure
            // occurred and against which identifier (for lockout forensics).
            LoginEvent::record(
                LoginEvent::EVENT_FAILED,
                $event->user?->id,
                request()->ip(),
                request()->userAgent(),
                null,
                ['identifier' => $event->credentials['phone'] ?? $event->credentials['email'] ?? 'unknown'],
            );
        });

        Event::listen(Logout::class, function (Logout $event) {
            if (! Schema::hasTable('login_events')) {
                return;
            }

            LoginEvent::record(
                LoginEvent::EVENT_LOGOUT,
                $event->user?->id,
                request()->ip(),
                request()->userAgent(),
            );
        });
    }
}
