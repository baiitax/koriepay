<?php

namespace App\Domain\Accounting;

use App\Domain\Accounting\Exceptions\LedgerValidationException;
use Illuminate\Support\Facades\DB;

/**
 * CommissionEngine — commission is DATA (commission_rules), never hardcoded.
 *
 * Resolution: pick the highest-priority active rule matching the transaction
 * profile (country, type, channel, tier, segment, amount band). The rule's
 * rate (%) or flat amount is applied, and a commission_entry is accrued for
 * the beneficiary (agent, aggregator, or the platform itself).
 */
class CommissionEngine
{
    /**
     * @param  array{
     *   country_iso2?: string|null,
     *   transaction_type?: string,
     *   channel?: string,
     *   agent_tier?: string|null,
     *   customer_segment?: string|null,
     *   amount?: string,
     * }  $profile
     */
    public function resolveRule(array $profile): ?object
    {
        $query = DB::table('commission_rules')
            ->where('is_active', true)
            ->where(function ($q) use ($profile) {
                // Country match: rule may be country-specific or global (null)
                $q->whereNull('country_iso2');
                if (! empty($profile['country_iso2'])) {
                    $q->orWhere('country_iso2', $profile['country_iso2']);
                }
            });

        foreach (['transaction_type', 'channel', 'agent_tier', 'customer_segment'] as $field) {
            $value = $profile[$field] ?? null;
            $query->where(function ($q) use ($field, $value) {
                $q->whereNull($field);
                if ($value !== null && $value !== '') {
                    $q->orWhere($field, $value);
                }
            });
        }

        if (! empty($profile['amount'])) {
            $amount = (string) $profile['amount'];
            $query->where(function ($q) use ($amount) {
                $q->whereNull('min_amount')->orWhere('min_amount', '<=', $amount);
            })->where(function ($q) use ($amount) {
                $q->whereNull('max_amount')->orWhere('max_amount', '>=', $amount);
            });
        }

        return $query->orderBy('priority', 'asc')->orderBy('id', 'asc')->first();
    }

    public function compute(object $rule, string $principal): string
    {
        $principal = (string) $principal;

        // flat_amount takes precedence when set (explicit fixed fee)
        if ($rule->flat_amount !== null && (float) $rule->flat_amount > 0) {
            return bcadd('0', (string) $rule->flat_amount, 2);
        }

        if ($rule->rate !== null && (float) $rule->rate > 0) {
            // rate is a percentage → multiply and round to 2 dp (banker-safe via bcmath)
            $amount = bcmul($principal, (string) $rule->rate, 4);
            return bcdiv(bcmul($amount, '100', 4), '10000', 2);
        }

        throw new LedgerValidationException("Commission rule #{$rule->id} has no rate or flat amount.");
    }

    /**
     * Accrue commission for a beneficiary from a principal amount.
     * Returns the created commission_entry row (status=accrued).
     */
    public function accrue(
        array $profile,
        string $principal,
        string $currency,
        int $beneficiaryId,
        string $beneficiaryType,
        ?int $transactionId = null,
        ?int $ledgerTransactionId = null,
    ): ?object {
        $rule = $this->resolveRule($profile);

        if ($rule === null) {
            return null; // no rule → no commission (by design)
        }

        $amount = $this->compute($rule, $principal);

        $id = DB::table('commission_entries')->insertGetId([
            'ledger_transaction_id' => $ledgerTransactionId,
            'transaction_id' => $transactionId,
            'beneficiary_id' => $beneficiaryId,
            'beneficiary_type' => $beneficiaryType,
            'rule_id' => $rule->id,
            'currency_code' => $currency,
            'amount' => $amount,
            'status' => 'accrued',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return DB::table('commission_entries')->find($id);
    }
}
