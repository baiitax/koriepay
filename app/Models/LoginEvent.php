<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Login history record — success/failure/lockout events powering the
 * Security Center and session intelligence (directive §44/§91/§92).
 */
class LoginEvent extends Model
{
    public const EVENT_SUCCESS = 'login_success';
    public const EVENT_FAILED = 'login_failed';
    public const EVENT_LOGOUT = 'logout';
    public const EVENT_LOCKOUT = 'lockout';

    public $timestamps = false;

    protected $fillable = [
        'user_id', 'event', 'ip_address', 'user_agent', 'device_id', 'meta', 'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an event if the table exists (safe on pre-migration boot).
     */
    public static function record(
        string $event,
        ?int $userId = null,
        ?string $ip = null,
        ?string $userAgent = null,
        ?string $deviceId = null,
        array $meta = [],
    ): ?self {
        if (! \Illuminate\Support\Facades\Schema::hasTable('login_events')) {
            return null;
        }

        return static::query()->create([
            'user_id' => $userId,
            'event' => $event,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'device_id' => $deviceId,
            'meta' => $meta ?: null,
            'created_at' => now(),
        ]);
    }
}
