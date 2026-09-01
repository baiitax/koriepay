<?php

namespace App\Jobs;

use App\Models\{AdashiGroup, AdashiMember, Wallet, Transaction};
use App\Notifications\FundsReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\{DB, Log};

class AdashiRescueJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $defaulter;
    public $recipient;
    public $group;
    public $cycleNumber;

    /**
     * Create a new job instance.
     */
    public function __construct(AdashiMember $defaulter, AdashiMember $recipient, AdashiGroup $group, $cycleNumber)
    {
        $this->defaulter = $defaulter;
        $this->recipient = $recipient;
        $this->group = $group;
        $this->cycleNumber = $cycleNumber;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        DB::transaction(function () {
            
            // 1. Lock the defaulter's wallet and force an overdraft
            $defaulterWallet = Wallet::where('user_id', $this->defaulter->user_id)
                                     ->where('currency_code', $this->group->currency)
                                     ->lockForUpdate()
                                     ->first();

            // 2. Lock the recipient's wallet
            $recipientWallet = Wallet::where('user_id', $this->recipient->user_id)
                                     ->where('currency_code', $this->group->currency)
                                     ->lockForUpdate()
                                     ->first();

            $amount = $this->group->contribution_amount;

            // 3. Execute the Bailout (Drop defaulter into the negative)
            $defaulterWallet->decrement('balance', $amount);
            $recipientWallet->increment('balance', $amount);

            // 4. Record the Debt on the Immutable Ledger
            Transaction::create([
                'user_id' => $this->defaulter->user_id,
                'reference' => 'DEBT-OUT-' . strtoupper(uniqid()),
                'type' => 'overdraft_deduction',
                'amount' => $amount,
                'currency' => $this->group->currency,
                'status' => 'completed',
                'description' => "Adashi Default Penalty: Forced overdraft for {$this->group->name}",
            ]);

            // 5. Record the Bailout Credit for the Recipient
            Transaction::create([
                'user_id' => $this->recipient->user_id,
                'reference' => 'BAIL-IN-' . strtoupper(uniqid()),
                'type' => 'transfer_in',
                'amount' => $amount,
                'currency' => $this->group->currency,
                'status' => 'completed',
                'description' => "System Bailout: Covered default for {$this->group->name} (Cycle {$this->cycleNumber})",
            ]);

            // 6. Update Member Status (Mark as Debt-Locked)
            $this->defaulter->update(['status' => 'defaulted']);

            Log::critical("ADASHI BAILOUT EXECUTED: User {$this->defaulter->user_id} forced into {$amount} {$this->group->currency} overdraft to protect Pool {$this->group->id}.");
        });
    }
}