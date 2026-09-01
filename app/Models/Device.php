<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Device trust record — powers device verification, session intelligence
 * and the Security Center (directive §44/§61/§92).
 */
class Device extends Model
{
    protected $fillable = [
        'user_id', 'device_id', 'platform', 'browser', 'ip_address',
        'user_agent', 'is_trusted', 'is_current', 'last_seen_at',
    ];

    protected $casts = [
        'is_trusted' => 'boolean',
        'is_current' => 'boolean',
        'last_seen_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Stable fingerprint from request context — no client-supplied value is
     * trusted as a device identity on its own.
     */
    public static function fingerprint(?string $ip, ?string $userAgent): string
    {
        return hash('sha256', trim((string) $ip).'|'.trim((string) $userAgent));
    }

    /**
     * Register (or refresh) a device for a user from request context.
     * Returns the Device; trusts only after an explicit trust action.
     */
    public static function register(User $user, ?string $ip, ?string $userAgent): self
    {
        $fingerprint = static::fingerprint($ip, $userAgent);

        $device = static::query()
            ->where('user_id', $user->id)
            ->where('device_id', $fingerprint)
            ->first();

        if ($device === null) {
            $device = static::query()->create([
                'user_id' => $user->id,
                'device_id' => $fingerprint,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'platform' => static::platformFrom($userAgent),
                'browser' => static::browserFrom($userAgent),
                'last_seen_at' => now(),
            ]);
        } else {
            $device->forceFill([
                'ip_address' => $ip,
                'user_agent' => $userAgent,
                'last_seen_at' => now(),
            ])->save();
        }

        return $device;
    }

    public static function platformFrom(?string $ua): ?string
    {
        $ua = strtolower((string) $ua);
        return match (true) {
            str_contains($ua, 'android') => 'android',
            str_contains($ua, 'iphone'), str_contains($ua, 'ipad') => 'ios',
            str_contains($ua, 'windows') => 'windows',
            str_contains($ua, 'mac') => 'macos',
            str_contains($ua, 'linux') => 'linux',
            default => null,
        };
    }

    public static function browserFrom(?string $ua): ?string
    {
        $ua = strtolower((string) $ua);
        return match (true) {
            str_contains($ua, 'edg') => 'edge',
            str_contains($ua, 'chrome') => 'chrome',
            str_contains($ua, 'firefox') => 'firefox',
            str_contains($ua, 'safari') => 'safari',
            default => null,
        };
    }
}
