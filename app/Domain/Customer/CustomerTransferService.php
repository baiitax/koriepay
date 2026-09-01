<?php

namespace App\Domain\Customer;

use App\Domain\Customer\Exceptions\CustomerBankingException;
use App\Domain\Payments\PaymentOrchestrator;
use App\Domain\Accounting\TransactionStateMachine;
use App\Models\Country;
use App\Models\Currency;
use App\Models\CustomerWallet;
use App\Models\CustomerWalletConfig;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * CUSTOMER BANKING — Stage 2 (Money movement).
 *
 * CustomerTransferService — the customer-facing send/receive layer:
 *   - recipient resolution by KoriePay ID or phone (server-authoritative);
 *   - fee transparency: fee = sender's country+currency config (flat + %),
 *     debited from the sender, credited to Platform Revenue, in the SAME
 *     atomic ledger posting as the principal (never two postings);
 *   - idempotent execution through PaymentOrchestrator (Phase 5 state
 *     machine: INITIATED → … → SETTLED | FAILED);
 *   - every guard (ownership, self-transfer, KYC/eligibility, balance,
 *     daily send limit, amount format) is enforced here — never the client.
 */
class CustomerTransferService
{
    public function __construct(
        private readonly PaymentOrchestrator $orchestrator,
        private readonly CustomerWalletService $wallets,
    ) {
    }

    // ── Recipient resolution ───────────────────────────────────────────────

    /**
     * Resolve a recipient by KoriePay ID (case-insensitive) or phone number
     * (digits-only compare, so +22790000001 == 22790000001 == 090000001? No —
     * national-format is NOT assumed; only E.164-style digit strings match).
     */
    public function resolveRecipient(string $ref): User
    {
        $ref = trim($ref);
        if ($ref === '') {
            throw new CustomerBankingException('Enter a KoriePay ID or phone number.');
        }

        $user = strtoupper(substr($ref, 0, 3)) === 'KP-'
            ? User::query()->whereRaw('UPPER(koriepay_id) = ?', [strtoupper($ref)])->first()
            : $this->findByPhone($ref);

        if ($user === null) {
            throw new CustomerBankingException('No KoriePay account found for that ID or number.');
        }

        if (! (bool) $user->is_active) {
            throw new CustomerBankingException('That account is not active.');
        }

        return $user;
    }

    private function findByPhone(string $ref): ?User
    {
        $digits = preg_replace('/\D/', '', $ref);
        if ($digits === '' || strlen($digits) < 7) {
            return null;
        }

        return User::query()
            ->get(['id', 'phone_number', 'is_active', 'name', 'country_code', 'koriepay_id'])
            ->first(fn (User $u) => preg_replace('/\D/', '', (string) $u->phone_number) === $digits);
    }

    /**
     * Masked recipient identity for the confirmation step — the app never
     * shows a full number before the user confirms.
     */
    public function recipientPreview(User $recipient): array
    {
        return [
            'koriepay_id' => $recipient->koriepay_id,
            'name' => $recipient->name,
            'phone_masked' => $this->wallets->maskPhone((string) $recipient->phone_number),
            'country' => strtoupper((string) $recipient->country_code),
        ];
    }

    // ── Preview (no money moves) ───────────────────────────────────────────

    /**
     * Server-side preview: resolves the recipient, validates the amount and
     * computes the fee + total debit. Nothing is persisted.
     */
    public function preview(User $sender, CustomerWallet $from, string $recipientRef, string $amount): array
    {
        $this->assertOwned($sender, $from);
        $this->assertActiveWallet($from);

        $recipient = $this->resolveRecipient($recipientRef);
        $this->guardSelfTransfer($sender, $recipient);
        $this->guardAmountFormat($amount, $from->currency_code);
        $this->guardRecipientWallet($recipient, $from->currency_code);
        $this->guardBalance($sender, $from, $amount);

        $fee = $this->normalize($this->feeFor($sender, $from->currency_code, $amount), $from->currency_code);

        return [
            'recipient' => $this->recipientPreview($recipient),
            'recipient_koriepay_id' => $recipient->koriepay_id,
            'from_wallet_id' => $from->wallet_id,
            'currency' => $from->currency_code,
            'amount' => (string) $amount,
            'fee' => $fee,
            'total_debit' => $this->normalize(bcadd((string) $amount, $fee, 2), $from->currency_code),
            'daily_send_limit_remaining' => $this->dailySendRemaining($sender, $from->currency_code),
        ];
    }

    // ── Execute (idempotent, through the state machine) ────────────────────

    /**
     * Send money to another KoriePay customer. Idempotency key ⇒ exactly one
     * transaction + one ledger posting; replays return the original row.
     *
     * @return Transaction (status SETTLED or FAILED; INITIATED only on
     *                      exceptional replay paths)
     */
    public function send(
        User $sender,
        CustomerWallet $from,
        string $recipientRef,
        string $amount,
        string $idempotencyKey,
        ?string $note = null,
    ): Transaction {
        $this->assertOwned($sender, $from);
        $this->assertActiveWallet($from);

        $recipient = $this->resolveRecipient($recipientRef);
        $this->guardSelfTransfer($sender, $recipient);
        $this->guardAmountFormat($amount, $from->currency_code);
        $this->guardRecipientWallet($recipient, $from->currency_code);
        $this->guardBalance($sender, $from, $amount);
        $this->guardDailySendLimit($sender, $from->currency_code, $amount);

        $fee = $this->normalize($this->feeFor($sender, $from->currency_code, $amount), $from->currency_code);
        $iso2 = $this->wallets->iso2For($sender);

        return DB::transaction(function () use ($sender, $recipient, $from, $amount, $fee, $iso2, $idempotencyKey, $note) {
            return $this->orchestrator->transfer(
                senderId: $sender->id,
                receiverId: $recipient->id,
                amount: (string) $amount,
                currency: $from->currency_code,
                countryIso2: $iso2,
                idempotencyKey: $idempotencyKey,
                description: $note,
                meta: ['transfer_fee' => $fee],
            );
        });
    }

    // ── Status / outcome ───────────────────────────────────────────────────

    /** Ownership-checked status lookup. */
    public function status(User $user, string $reference): Transaction
    {
        $transaction = Transaction::query()->where('reference', $reference)->first();

        if ($transaction === null) {
            throw new CustomerBankingException('No transaction with that reference.');
        }

        if ((int) $transaction->sender_id !== (int) $user->id
            && (int) $transaction->receiver_id !== (int) $user->id) {
            throw new CustomerBankingException('You do not have access to this transaction.', 403);
        }

        return $transaction;
    }

    /**
     * Customer-facing outcome mapping — the brief's honest four states:
     * success | failed | processing | unknown.
     */
    public function outcomeFor(Transaction $transaction): string
    {
        return match (strtoupper((string) $transaction->status)) {
            TransactionStateMachine::SETTLED, 'COMPLETED' => 'success',
            TransactionStateMachine::FAILED,
            TransactionStateMachine::REVERSED,
            TransactionStateMachine::REFUNDED,
            TransactionStateMachine::CANCELLED,
            TransactionStateMachine::EXPIRED => 'failed',
            TransactionStateMachine::INITIATED,
            TransactionStateMachine::PENDING,
            TransactionStateMachine::PROCESSING,
            TransactionStateMachine::AUTHORIZED,
            TransactionStateMachine::POSTED,
            TransactionStateMachine::HELD => 'processing',
            default => 'unknown',
        };
    }

    public function transactionPayload(Transaction $transaction): array
    {
        return [
            'reference' => $transaction->reference,
            'type' => $transaction->type,
            'status' => strtolower((string) $transaction->status),
            'outcome' => $this->outcomeFor($transaction),
            'direction' => 'out',
            'amount' => (string) $transaction->source_amount,
            'currency' => $transaction->source_currency,
            'fee' => $this->normalize((string) ($transaction->fee_charged ?? '0.00'), (string) $transaction->source_currency),
            'total_debit' => $this->normalize(bcadd((string) $transaction->source_amount, (string) ($transaction->fee_charged ?? '0.00'), 2), (string) $transaction->source_currency),
            'recipient' => $transaction->receiver_id !== null
                ? $this->recipientPreview(User::find($transaction->receiver_id))
                : null,
            'note' => $transaction->description,
            'provider' => $transaction->provider,
            'provider_reference' => $transaction->provider_reference,
            'error_reason' => $transaction->error_reason,
            'created_at' => $transaction->created_at?->toIso8601String(),
        ];
    }

    // ── Receive identity (§128 receive journey) ────────────────────────────

    public function receiveIdentity(User $user): array
    {
        $this->wallets->provision($user);

        $koriepayId = (string) $user->koriepay_id;
        if ($koriepayId === '') {
            throw new CustomerBankingException('Your KoriePay ID is not set up yet.');
        }

        return [
            'koriepay_id' => $koriepayId,
            'name' => $user->name,
            'phone_masked' => $this->wallets->maskPhone((string) $user->phone_number),
            'country' => strtoupper((string) $user->country_code),
            'qr_payload' => 'koriepay://pay/'.$koriepayId,
            'wallets' => collect($this->wallets->walletsFor($user))
                ->map(fn (CustomerWallet $w) => [
                    'wallet_id' => $w->wallet_id,
                    'currency' => $w->currency_code,
                    'is_primary' => (bool) $w->is_primary,
                ])
                ->values()
                ->all(),
        ];
    }

    // ── Guards ─────────────────────────────────────────────────────────────

    protected function assertOwned(User $user, CustomerWallet $wallet): void
    {
        if ((int) $wallet->user_id !== (int) $user->id) {
            throw new CustomerBankingException('Wallet not found.');
        }
    }

    protected function assertActiveWallet(CustomerWallet $wallet): void
    {
        if ($wallet->status !== CustomerWallet::STATUS_ACTIVE) {
            throw new CustomerBankingException('This wallet is not available for sending.');
        }
    }

    protected function guardSelfTransfer(User $sender, User $recipient): void
    {
        if ((int) $sender->id === (int) $recipient->id) {
            throw new CustomerBankingException('You cannot send money to yourself.');
        }
    }

    protected function guardAmountFormat(string $amount, string $currency): void
    {
        $minorUnits = (int) (Currency::where('code', $currency)->value('minor_units') ?? 2);
        $pattern = $minorUnits === 0
            ? '/^-?\d+$/'
            : '/^-?\d+(\.\d{1,'.$minorUnits.'})?$/';

        if (! preg_match($pattern, $amount) || bccomp($amount, '0') <= 0) {
            throw new CustomerBankingException("Enter a valid amount in {$currency}.");
        }
    }

    /** The recipient must be able to HOLD this currency (§75). */
    protected function guardRecipientWallet(User $recipient, string $currency): void
    {
        $this->wallets->provision($recipient);

        $wallet = CustomerWallet::query()
            ->where('user_id', $recipient->id)
            ->where('currency_code', $currency)
            ->where('status', CustomerWallet::STATUS_ACTIVE)
            ->first();

        if ($wallet === null) {
            $iso2 = $this->wallets->iso2For($recipient);
            throw new CustomerBankingException(
                "This recipient cannot receive {$currency}. Their country (".$iso2.') supports different wallets.'
            );
        }
    }

    protected function guardBalance(User $sender, CustomerWallet $from, string $amount): void
    {
        $fee = $this->normalize($this->feeFor($sender, $from->currency_code, $amount), $from->currency_code);
        $available = (string) ($from->ledgerAccount?->balance ?? '0');

        if (bccomp($available, bcadd($amount, $fee, 2), 2) < 0) {
            throw new CustomerBankingException('Insufficient balance for this transfer including fees.');
        }
    }

    protected function guardDailySendLimit(User $sender, string $currency, string $amount): void
    {
        $remaining = $this->dailySendRemaining($sender, $currency);

        if (bccomp($amount, $remaining, 2) > 0) {
            throw new CustomerBankingException(
                'This transfer exceeds your remaining daily send limit ('.$remaining.' '.$currency.').'
            );
        }
    }

    protected function dailySendRemaining(User $sender, string $currency): string
    {
        $iso2 = $this->wallets->iso2For($sender);
        $config = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)
            ->where('currency_code', $currency)
            ->first();

        $limit = $config?->daily_send_limit;
        if ($limit === null) {
            return (string) PHP_INT_MAX; // no limit configured
        }

        $sent = (string) Transaction::query()
            ->where('sender_id', $sender->id)
            ->where('source_currency', $currency)
            ->whereNotIn('status', [TransactionStateMachine::FAILED, TransactionStateMachine::REVERSED, TransactionStateMachine::REFUNDED, TransactionStateMachine::CANCELLED, TransactionStateMachine::EXPIRED])
            ->where('created_at', '>=', now()->startOfDay())
            ->sum('source_amount');

        return bcsub((string) $limit, $sent, 2);
    }

    /** Zero-minor-currency safe decimal normalization (XOF = whole units). */
    protected function normalize(string $amount, string $currency): string
    {
        return $this->wallets->normalizeDecimal($amount, $currency);
    }

    /** Fee = flat + rate% of amount, from the SENDER's currency config. */
    protected function feeFor(User $sender, string $currency, string $amount): string
    {
        $iso2 = $this->wallets->iso2For($sender);
        $config = CustomerWalletConfig::query()
            ->where('country_iso2', $iso2)
            ->where('currency_code', $currency)
            ->first();

        $flat = (string) ($config?->transfer_fee_flat ?? '0');
        $rate = (string) ($config?->transfer_fee_rate ?? '0');

        $fee = bcadd($flat, bcmul($amount, bcdiv($rate, '100', 6), 2), 2);

        return $fee;
    }
}
