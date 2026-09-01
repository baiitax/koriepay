<?php

namespace App\Traits;

use App\Models\Transaction;
use Illuminate\Support\Facades\Cache;

trait EnforcesVelocityLimits
{
    /**
     * Checks if the transfer exceeds the daily KYC limit.
     */
    public function validateDailyLimit($user, $amount, $currency)
    {
        // 1. Define Tier Limits (Stored in NGN Base)
        $dailyLimitNgn = $user->kyc_status === 'verified' ? 500000 : 50000;

        // 2. Normalize the current transaction amount to NGN for limit checking
        $ngnValue = $amount;
        if ($currency === 'XOF') {
            $xofToNgnRate = Cache::get('FX_XOF_NGN', 2.38); // Fallback to 2.38 if cache misses
            $ngnValue = $amount * $xofToNgnRate;
        }

        // 3. Calculate today's existing transfer total 
        // FIXED: Using 'sender_id' instead of 'user_id'
        $todayTotalNgn = Transaction::where('sender_id', $user->id) // <-- FIXED
            ->where('type', 'transfer_out')
            ->whereDate('created_at', now()->toDateString())
            ->where('source_currency', 'NGN') 
            ->sum('source_amount');           

        // 4. Check the Threshold
        if (($todayTotalNgn + $ngnValue) > $dailyLimitNgn) {
            $remaining = max(0, $dailyLimitNgn - $todayTotalNgn);
            $this->addError('amount', "Daily limit exceeded. Your Tier allows ₦" . number_format($dailyLimitNgn) . "/day. Remaining: ₦" . number_format($remaining));
            return false;
        }

        return true;
    }
}