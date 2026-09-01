<?php

namespace App\Domain\Payments\ValueObjects;

/**
 * Live provider health snapshot. availability is never assumed.
 */
final class ProviderHealth
{
    public const STATUS_OPERATIONAL = 'operational';
    public const STATUS_DEGRADED = 'degraded';
    public const STATUS_DOWN = 'down';
    public const STATUS_UNCONFIGURED = 'unconfigured';

    public function __construct(
        public readonly string $status,
        public readonly int $score = 0,          // 0–100
        public readonly ?int $latencyMs = null,  // P95 or last measured
        public readonly ?string $message = null,
    ) {
    }

    public function isOperational(): bool
    {
        return $this->status === self::STATUS_OPERATIONAL;
    }

    public function isAvailable(): bool
    {
        return in_array($this->status, [self::STATUS_OPERATIONAL, self::STATUS_DEGRADED], true);
    }

    public static function unconfigured(): self
    {
        return new self(self::STATUS_UNCONFIGURED, 0, null, 'Provider has no live configuration.');
    }
}
