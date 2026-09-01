<?php

namespace App\Domain\Agency;

use App\Domain\Accounting\LedgerAccount;
use App\Domain\Accounting\LedgerService;
use App\Models\CommissionEntry;
use App\Models\CommissionRule;
use Illuminate\Support\Str;

/**
 * PHASE 6 — Commission engine.
 *
 * Resolves the highest-priority matching commission rule for an operation
 * profile and accrues the split through the LEDGER (never a balance field):
 *
 *   DR Commission Expense (expense, system) / CR Agent Commission Accrual (liability)
 *
 * commission_entries + commission_accruals record the audit trail; payout is a
 * separate later step (accrued → paid → reversed).
 */
class CommissionEngine
{
    public function __construct(private readonly LedgerService $ledger)
    {
    }

    /**
     * Resolve a matching active rule (lower priority wins; flat preferred
     * over rate, per DATABASE.md §6).
     */
    public function resolve(
        string $countryIso2,
        string $transactionType,
        string $channel,
        ?string $agentTier,
        string $amount,
    ): ?CommissionRule {
        $query = CommissionRule::query()
            ->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('country_iso2')->orWhere('country_iso2', $countryIso2))
            ->where(fn ($q) => $q->whereNull('transaction_type')->orWhere('transaction_type', $transactionType))
            ->where(fn ($q) => $q->whereNull('channel')->orWhere('channel', $channel));

        if ($agentTier !== null) {
            $query->where(fn ($q) => $q->whereNull('agent_tier')->orWhere('agent_tier', $agentTier));
        }

        $rules = $query->orderBy('priority')->orderBy('id')->get();

        foreach ($rules as $rule) {
            if ($rule->min_amount !== null && bccomp((string) $amount, (string) $rule->min_amount) < 0) {
                continue;
            }
            if ($rule->max_amount !== null && bccomp((string) $amount, (string) $rule->max_amount) > 0) {
                continue;
            }

            return $rule;
        }

        return null;
    }

    /**
     * Amount payable under a rule. Flat amount is preferred over rate.
     */
    public function amountFor(CommissionRule $rule, string $amount): string
    {
        if ($rule->flat_amount !== null) {
            return (string) $rule->flat_amount;
        }

        if ($rule->rate !== null) {
            return bcdiv(bcmul((string) $amount, (string) $rule->rate, 4), '100', 2);
        }

        return '0';
    }

    /**
     * Accrue a commission split to a beneficiary through the ledger.
     *
     * @return CommissionEntry|null null when the computed amount is zero
     */
    public function accrue(
        CommissionRule $rule,
        string $beneficiaryType, // agent | aggregator
        int $beneficiaryId,
        string $currencyCode,
        string $amount,
        ?int $relatedTransactionId = null,
        ?string $idempotencyKey = null,
    ): ?CommissionEntry {
        $commission = $this->amountFor($rule, $amount);

        if (bccomp($commission, '0') <= 0) {
            return null;
        }

        // Platform's own chart-of-accounts — provision idempotently.
        $expense = LedgerAccount::firstOrCreate(
            [
                'account_type' => 'expense',
                'name' => 'Commission Expense',
                'currency_code' => $currencyCode,
            ],
            ['is_system' => true, 'balance' => '0'],
        );

        $accrual = LedgerAccount::query()
            ->where('owner_type', $beneficiaryType)
            ->where('owner_id', $beneficiaryId)
            ->where('account_type', 'liability')
            ->where('name', 'Agent Commission Accrual')
            ->where('currency_code', $currencyCode)
            ->first();

        if ($accrual === null) {
            $accrual = LedgerAccount::create([
                'account_type' => 'liability',
                'name' => 'Agent Commission Accrual',
                'currency_code' => $currencyCode,
                'owner_type' => $beneficiaryType,
                'owner_id' => $beneficiaryId,
                'balance' => '0',
            ]);
        }

        $ledgerTx = $this->ledger->post(
            [
                ['account_id' => $expense->id, 'side' => 'debit', 'amount' => $commission],
                ['account_id' => $accrual->id, 'side' => 'credit', 'amount' => $commission],
            ],
            type: 'commission',
            description: 'Commission accrual for '.$beneficiaryType.' #'.$beneficiaryId,
            idempotencyKey: $idempotencyKey,
            relatedTransactionId: $relatedTransactionId,
        );

        $entry = CommissionEntry::create([
            'ledger_transaction_id' => $ledgerTx->id,
            'transaction_id' => $relatedTransactionId,
            'beneficiary_id' => $beneficiaryId,
            'beneficiary_type' => $beneficiaryType,
            'rule_id' => $rule->id,
            'currency_code' => $currencyCode,
            'amount' => $commission,
            'status' => 'accrued',
        ]);

        \Illuminate\Support\Facades\DB::table('commission_accruals')->insert([
            'commission_entry_id' => $entry->id,
            'ledger_account_id' => $accrual->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $entry;
    }

    public static function generateCommissionKey(string $operationKey): string
    {
        return 'COMM-'.$operationKey.'-'.Str::random(6);
    }
}
