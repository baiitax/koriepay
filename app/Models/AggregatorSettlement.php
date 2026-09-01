<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AGGREGATOR CONSOLE — Stage E (settlement center, §38–43, §66–67).
 *
 * An aggregator-scoped settlement batch. Money movement on settle() goes
 * through the ledger (DR Settlement Payable / CR Aggregator Float) and the
 * payout posting is idempotent + audited. Reconciliation compares the
 * expected amount (Σ accrued commission entries in the period) against the
 * actual amount paid — differences are shown, never silently reconciled.
 */
class AggregatorSettlement extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SETTLED = 'settled';
    public const STATUS_FAILED = 'failed';
    public const STATUS_UNDER_REVIEW = 'under_review';

    protected $fillable = [
        'reference', 'aggregator_id', 'currency_code', 'gross_amount', 'fees',
        'commission_amount', 'adjustments', 'net_amount', 'expected_amount',
        'actual_amount', 'status', 'period_start', 'period_end',
        'ledger_transaction_id', 'created_by', 'processed_by', 'processed_at', 'notes',
    ];

    protected $casts = [
        'gross_amount' => 'decimal:2',
        'fees' => 'decimal:2',
        'commission_amount' => 'decimal:2',
        'adjustments' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'period_start' => 'datetime',
        'period_end' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function aggregator(): BelongsTo
    {
        return $this->belongsTo(Aggregator::class);
    }

    /** Honest reconciliation status: matched | difference | unreconciled. */
    public function reconciliation(): array
    {
        if ($this->expected_amount === null || $this->actual_amount === null) {
            return [
                'status' => 'unreconciled',
                'delta' => null,
                'label' => 'Awaiting settlement data',
            ];
        }

        $expected = (string) $this->expected_amount;
        $actual = (string) $this->actual_amount;
        $delta = bcsub($actual, $expected, 2);

        return [
            'status' => bccomp($delta, '0', 2) === 0 ? 'matched' : 'difference',
            'delta' => $delta,
            'label' => bccomp($delta, '0', 2) === 0
                ? 'Expected matches actual'
                : 'Difference of '.number_format((float) abs($delta), 2, '.', '').' '.$this->currency_code,
        ];
    }
}
