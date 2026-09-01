<?php

namespace App\Domain\Payments;

use App\Domain\Payments\ValueObjects\PaymentRequest;
use App\Domain\Payments\ValueObjects\PaymentResult;
use App\Domain\Payments\ValueObjects\ProviderHealth;
use Illuminate\Http\Request;

/**
 * PaymentProviderInterface — the provider abstraction (mandate: never hardcode
 * provider logic; new markets/providers are additive implementations).
 *
 * A provider is country- and currency-aware, reports live health, verifies its
 * own webhook signatures, and executes money movement through the domain.
 *
 * Real-world note: we never fabricate a provider's API. The internal ledger
 * rail (App\Domain\Payments\Providers\InternalLedgerProvider) is genuinely
 * operational — it moves money through LedgerService. External providers
 * (Paystack, DusuPay, MTN MoMo, …) implement this interface against their real
 * documented APIs with real credentials, registered via configuration.
 */
interface PaymentProviderInterface
{
    /** Stable provider code (matches payment_providers.code). */
    public function code(): string;

    public function name(): string;

    /** ISO-3166-1 alpha-2 codes this provider serves. */
    public function supportedCountries(): array;

    /** ISO-4217 codes this provider can move. */
    public function supportedCurrencies(): array;

    /** Payment rail codes this provider can execute (WALLET_NG, WALLET_NE, …). */
    public function supportedRails(): array;

    /** e.g. ['transfer', 'deposit', 'withdraw', 'resolve_account']. */
    public function capabilities(): array;

    /** Current live health — never assumed; always measured or reported. */
    public function health(): ProviderHealth;

    /** True only when the provider is configured, active and healthy. */
    public function isAvailable(): bool;

    /**
     * Execute the money movement. Must be idempotent for a given
     * request idempotency key (the orchestrator enforces the store-level
     * idempotency; providers must tolerate replays).
     */
    public function execute(PaymentRequest $request): PaymentResult;

    /** Query the provider for the current status of a previous reference. */
    public function verify(string $providerReference): PaymentResult;

    /**
     * Verify an inbound webhook request is genuinely from this provider.
     * Must FAIL CLOSED when no secret is configured.
     */
    public function verifyWebhookSignature(Request $request): bool;
}
