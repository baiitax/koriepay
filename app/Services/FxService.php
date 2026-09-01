<?php

namespace App\Services;
use App\Services\CircuitBreaker;
use Exception;
use App\Models\FxRate;


class FxService
{
    protected float $defaultSpread = 0.02; // 2% Profit Margin

    /**
     * Calculate the effective payout amount.
     */
    public function getTransferDetails(string $baseCurrency, string $targetCurrency, float $amount): array
    {
        if (CircuitBreaker::isTripped()) {
            throw new Exception("SYSTEM HALT: Cross-border gateway is currently suspended. Error Code: CB-994.");
        }    
        $fx = FxRate::where('base_currency', $baseCurrency)
            ->where('target_currency', $targetCurrency)
            ->where('is_active', true)
            ->first();

        if (!$fx) {
            throw new Exception("Currency pair {$baseCurrency}/{$targetCurrency} not supported.");
        }

        $baseRate = (float) $fx->rate;
        $effectiveRate = $baseRate * (1 - $this->defaultSpread);
        $payoutAmount = $amount * $effectiveRate;
        $feeCollected = ($amount * $baseRate) - $payoutAmount;

        return [
            'base_rate'      => $baseRate,
            'effective_rate' => $effectiveRate,
            'payout_amount'  => $payoutAmount,
            'spread_fee'     => $feeCollected,
            'margin_pct'     => ($this->defaultSpread * 100) . '%'
        ];
    }
}