<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use App\Models\{RevenueLog, Transaction, AuditLog};
use Carbon\Carbon;

class CircuitBreaker
{
    const CRITICAL_THRESHOLD = 1.5; // 1.5%

    /**
     * Check the state. Runs in O(1) time.
     */
    public static function isTripped(): bool
    {
        return Cache::get('circuit_breaker_tripped', false);
    }

    /**
     * Evaluates the margin and triggers the breaker if necessary.
     */
    public static function evaluateSystemHealth(): void
    {
        if (self::isTripped()) return; // Already halted

        $volume = Transaction::whereDate('created_at', Carbon::today())->sum('amount');
        if ($volume == 0) return;

        $revenue = RevenueLog::whereDate('created_at', Carbon::today())->sum('amount');
        $margin = ($revenue / $volume) * 100;

        if ($margin < self::CRITICAL_THRESHOLD) {
            // TRIP THE BREAKER
            Cache::forever('circuit_breaker_tripped', true);

            // Log the catastrophic event securely
            AuditLog::create([
                'user_id' => 1, // Assuming ID 1 is the primary System Admin
                'target_id' => 0, // System-wide event
                'action' => 'CIRCUIT_BREAKER_TRIPPED',
                'payload' => [
                    'margin_at_failure' => $margin,
                    'threshold' => self::CRITICAL_THRESHOLD
                ],
                'ip_address' => '127.0.0.1',
            ]);
        }
    }

    /**
     * Super Admin Manual Override
     */
    public static function reset(int $adminId, string $ip): void
    {
        Cache::forget('circuit_breaker_tripped');

        AuditLog::create([
            'user_id' => $adminId,
            'target_id' => 0,
            'action' => 'CIRCUIT_BREAKER_RESET',
            'payload' => ['status' => 'System Restored'],
            'ip_address' => $ip,
        ]);
    }
}