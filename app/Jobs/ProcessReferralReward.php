<?php

namespace App\Jobs;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class ProcessReferralReward implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $user;
    protected $transactionAmount;

    public function __construct(User $user, $transactionAmount)
    {
        $this->user = $user;
        $this->transactionAmount = $transactionAmount;
    }

    public function handle()
    {
        // 1. Is the transfer >= 5000?
        if ($this->transactionAmount < 5000) return;

        // 2. Was this user referred by someone?
        if (!$this->user->referred_by) return;

        $referrer = User::find($this->user->referred_by);
        if (!$referrer) return;

        // 3. Has the bonus already been paid? (Check if a referral_bonus transaction exists for this user)
        $alreadyPaid = Transaction::where('receiver_id', $this->user->id)
                                  ->where('type', 'referral_bonus')
                                  ->exists();
        
        if ($alreadyPaid) return;

        // 4. Pay out ₦500 to BOTH parties
        DB::transaction(function () use ($referrer) {
            $rewardAmount = 500.00;

            // Reward New User
            $userWallet = Wallet::where('user_id', $this->user->id)->where('currency_code', 'NGN')->first();
            $userWallet->increment('balance', $rewardAmount);
            Transaction::create([
                'receiver_id' => $this->user->id,
                'type' => 'referral_bonus',
                'destination_currency' => 'NGN',
                'destination_amount' => $rewardAmount,
                'status' => 'completed',
                'description' => 'Welcome Bonus: Referral Goal Met',
                'reference' => 'REF-BONUS-' . uniqid(),
            ]);

            // Reward Referrer
            $referrerWallet = Wallet::where('user_id', $referrer->id)->where('currency_code', 'NGN')->first();
            $referrerWallet->increment('balance', $rewardAmount);
            Transaction::create([
                'receiver_id' => $referrer->id,
                'type' => 'referral_bonus',
                'destination_currency' => 'NGN',
                'destination_amount' => $rewardAmount,
                'status' => 'completed',
                'description' => 'Referral Bonus for inviting ' . $this->user->name,
                'reference' => 'REF-BONUS-' . uniqid(),
            ]);
        });
    }
}