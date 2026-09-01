<?php

namespace App\Domain\Payments\ValueObjects;

use App\Domain\Payments\Exceptions\InvalidPaymentRequestException;

/**
 * Immutable description of one payment operation.
 * Money amounts are decimal STRINGS (never floats).
 */
final class PaymentRequest
{
    public function __construct(
        public readonly string $type,          // deposit | withdraw | transfer
        public readonly string $amount,        // decimal string, minor-unit safe
        public readonly string $currency,      // ISO-4217 (NGN, XOF)
        public readonly string $countryIso2,   // NG | NE
        public readonly string $rail,          // WALLET_NG, WALLET_NE, …
        public readonly ?int $senderId = null,
        public readonly ?int $receiverId = null,
        public readonly ?string $description = null,
        public readonly string $idempotencyKey = '',
        public readonly array $meta = [],
        public readonly ?string $destinationCurrency = null,
        public readonly ?string $destinationAmount = null,
    ) {
        if ($this->type === '' || ! in_array($this->type, ['deposit', 'withdraw', 'transfer', 'exchange'], true)) {
            throw new InvalidPaymentRequestException("Invalid payment type [{$this->type}].");
        }
        if ($this->amount === '' || (float) $this->amount <= 0) {
            throw new InvalidPaymentRequestException("Amount must be positive decimal string, got [{$this->amount}].");
        }
        if (strlen($this->currency) !== 3) {
            throw new InvalidPaymentRequestException("Currency must be a 3-letter ISO code, got [{$this->currency}].");
        }
        if ($this->destinationCurrency !== null && strlen($this->destinationCurrency) !== 3) {
            throw new InvalidPaymentRequestException("Destination currency must be a 3-letter ISO code, got [{$this->destinationCurrency}].");
        }
        if (strlen($this->countryIso2) !== 2) {
            throw new InvalidPaymentRequestException("Country must be a 2-letter ISO code, got [{$this->countryIso2}].");
        }
        if ($this->rail === '') {
            throw new InvalidPaymentRequestException('Rail code is required.');
        }
        if ($this->idempotencyKey === '' || strlen($this->idempotencyKey) > 64) {
            throw new InvalidPaymentRequestException('Idempotency key is required (1–64 chars).');
        }
    }

    public function amountString(): string
    {
        return $this->amount;
    }
}
