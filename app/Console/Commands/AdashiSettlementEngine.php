<?php

namespace App\Console\Commands;

use App\Models\{AdashiGroup, AdashiMember, Wallet, Transaction};
use App\Notifications\FundsReceived;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AdashiSettlementEngine extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'adashi:settle';

    /**
     * The console command description.
     */
    protected $description = 'Process automated Adashi pool deductions and payouts';

    public function handle()
    {
        $this->info("Starting Adashi Settlement Engine at " . now());

        // 1. Find all active groups
        $activeGroups = AdashiGroup::where('status', 'active')->get();
        $processedCount = 0;

        foreach ($activeGroups as $group) {
            // Determine the current cycle number based on how many members have been paid
            $completedCycles = AdashiMember::where('adashi_group_id', $group->id)
                                           ->where('has_received_payout', true)
                                           ->count();
            
            $currentCycleNumber = $completedCycles + 1;

            // Check if this pool is due today based on frequency and start date
            if (!$this->isDueToday($group->start_date, $group->frequency, $currentCycleNumber)) {
                continue; // Skip, not due today
            }

            $this->info("Processing Group: {$group->name} (Cycle #{$currentCycleNumber})");

            DB::beginTransaction();
            try {
                // Lock the group and its active members for processing
                $members = AdashiMember::where('adashi_group_id', $group->id)
                                       ->where('status', 'active')
                                       ->lockForUpdate()
                                       ->get();

                $recipientMember = $members->where('payout_order', $currentCycleNumber)->first();
                
                if (!$recipientMember) {
                    throw new \Exception("Recipient for cycle {$currentCycleNumber} not found.");
                }

                $recipientWallet = Wallet::where('user_id', $recipientMember->user_id)
                                         ->where('currency_code', $group->currency)
                                         ->lockForUpdate()
                                         ->first();

                $totalCollected = 0;

                // 2. DEDUCT FROM ALL MEMBERS
                foreach ($members as $member) {
                    $wallet = Wallet::where('user_id', $member->user_id)
                                    ->where('currency_code', $group->currency)
                                    ->lockForUpdate()
                                    ->first();

                    if ($wallet && $wallet->balance >= $group->contribution_amount) {
                        // Successful Deduction
                        $wallet->decrement('balance', $group->contribution_amount);
                        $totalCollected += $group->contribution_amount;

                        Transaction::create([ /* ... normal out transfer ... */ ]);
                    } else {
                        // 🚨 DEFAULT PROTOCOL TRIGGERED: Member doesn't have enough money
                        Log::warning("Adashi Default Detected: User {$member->user_id}. Triggering System Bailout.");
                        
                        // Execute the Rescue Job synchronously so the recipient gets the money in this exact cycle
                        \App\Jobs\AdashiRescueJob::dispatchSync(
                            $member,          // The defaulter
                            $recipientMember, // The person who needs the money today
                            $group,           // The pool
                            $currentCycleNumber
                        );

                        // Because the Rescue Job handles the recipient's credit for this specific share, 
                        // we DO NOT add it to the $totalCollected sum (which handles the healthy payers).
                    }
                }

                // 3. PAYOUT TO THE RECIPIENT
                if ($totalCollected > 0) {
                    $recipientWallet->increment('balance', $totalCollected);
                    $recipientMember->update(['has_received_payout' => true]);

                    Transaction::create([
                        'user_id' => $recipientMember->user_id,
                        'reference' => 'ADASHI-IN-' . strtoupper(uniqid()),
                        'type' => 'transfer_in',
                        'amount' => $totalCollected,
                        'currency' => $group->currency,
                        'status' => 'completed',
                        'description' => "Adashi Payout: {$group->name} (Cycle {$currentCycleNumber})",
                    ]);

                    // Send the Dopamine Ping
                    $recipientMember->user->notify(new FundsReceived(
                        $totalCollected, 
                        $group->currency, 
                        "{$group->name} Community Pool"
                    ));
                }

                // 4. CHECK IF GROUP IS COMPLETED
                if ($currentCycleNumber >= $group->max_members) {
                    $group->update(['status' => 'completed']);
                    $this->info("Group {$group->name} has completed all cycles.");
                }

                DB::commit();
                $processedCount++;
                $this->info("Cycle {$currentCycleNumber} for {$group->name} settled successfully. Pot: {$totalCollected}");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Adashi Engine Failed on Group {$group->id}: " . $e->getMessage());
                $this->error("Failed to process {$group->name}. Transaction rolled back.");
            }
        }

        $this->info("Adashi Settlement Engine complete. Processed {$processedCount} pools.");
    }

    /**
     * Calculates if the pool is due for deduction today.
     */
    private function isDueToday($startDate, $frequency, $currentCycleNumber)
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $today = now()->startOfDay();

        // If the start date is in the future, it's not due.
        if ($today->lt($start)) {
            return false;
        }

        $expectedDueDate = clone $start;

        if ($frequency === 'daily') {
            $expectedDueDate->addDays($currentCycleNumber - 1);
        } elseif ($frequency === 'weekly') {
            $expectedDueDate->addWeeks($currentCycleNumber - 1);
        } elseif ($frequency === 'monthly') {
            $expectedDueDate->addMonths($currentCycleNumber - 1);
        }

        // Return true if today matches the expected due date exactly
        return $today->isSameDay($expectedDueDate);
    }
}