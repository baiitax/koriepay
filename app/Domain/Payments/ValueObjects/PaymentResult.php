<?php

namespace App\Domain\Payments\ValueObjects;

/**
 * Outcome of a provider operation. Success is never implied — it is explicit.
 */
final class PaymentResult
{
    public const STATUS_SUCCESS = 'success';
    public const STATUS_PENDING = 'pending';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNKNOWN = 'unknown';

    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $providerReference = null,
        public readonly ?string $message = null,
        public readonly array $data = [],
    ) {
    }

    public static function success(?string $reference = null, ?string $message = null, array $data = []): self
    {
        return new self(true, self::STATUS_SUCCESS, $reference, $message, $data);
    }

    public static function pending(?string $reference = null, ?string $message = null, array $data = []): self
    {
        return new self(false, self::STATUS_PENDING, $reference, $message, $data);
    }

    public static function failed(string $message, array $data = []): self
    {
        return new self(false, self::STATUS_FAILED, null, $message, $data);
    }
}
