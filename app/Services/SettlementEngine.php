<?php

namespace App\Services;

use App\Models\{Transaction, BankNode, Setting, RevenueLog, FxRate};
use App\Events\TransactionProcessed;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class SettlementEngine
{
    /**
     * Executes an Atomic Cross-Border P2P Transfer
     * * @param int $senderId
     * @param int $receiverId
     * @param int $sourceNodeId (Sender's Wallet/Bank Node)
     * @param int $destNodeId (Receiver's Wallet/Bank Node)
     * @param string $destCurrency (e.g., 'XOF')
     * @param float $amount (The gross amount in source currency)
     */
    public function execute($senderId, $receiverId, $sourceNodeId, $destNodeId, $destCurrency, $amount)
    {
        // The Sovereign Shield: Everything inside here succeeds, or EVERYTHING rolls back.
        return DB::transaction(function () use ($senderId, $receiverId, $sourceNodeId, $destNodeId, $destCurrency, $amount) {
            
            // 1. PESSIMISTIC LOCK WITH DEADLOCK PREVENTION
            // We must lock BOTH the sender and receiver nodes.
            // Sorting the IDs ensures MySQL always locks rows in the same order, preventing deadlocks.
            $nodeIds = [$sourceNodeId, $destNodeId];
            sort($nodeIds);
            
            // Lock the nodes for update
            $nodes = BankNode::whereIn('id', $nodeIds)->lockForUpdate()->get()->keyBy('id');
            
            $sourceBank = $nodes->get($sourceNodeId);
            $destBank = $nodes->get($destNodeId);
            
            if (!$sourceBank || !$destBank) {
                throw new Exception("Critical: Settlement node or Destination node offline/missing.");
            }

            // 2. LIQUIDITY VERIFICATION
            if ($sourceBank->balance < $amount) {
                throw new Exception("Liquidity Alert: Insufficient capital in {$sourceBank->bank_name}.");
            }

            // 3. FETCH ORACLE DATA (Rates & Fees)
            $pair = "{$sourceBank->currency}/{$destCurrency}";
            $rate = FxRate::where('pair', $pair)->first();
            $feePercent = Setting::where('key', 'platformFee')->value('value') ?? 1.5;
            
            // Fetch dynamic USD rate for accurate revenue logging
            $usdPair = "{$sourceBank->currency}/USD";
            $usdRate = FxRate::where('pair', $usdPair)->first();
            
            if (!$rate) {
                throw new Exception("FX Oracle Error: Pair {$pair} is not supported.");
            }
            if (!$usdRate) {
                throw new Exception("FX Oracle Error: Revenue pair {$usdPair} is not configured.");
            }

            // 4. THE SETTLEMENT MATH (Bank-Grade Precision)
            $feeAmount = round($amount * ($feePercent / 100), 2);
            $principal = $amount - $feeAmount;
            $settlementAmount = round($principal * $rate->effective_rate, 2);

            // 5. EXECUTE DOUBLE-ENTRY DEBITS & CREDITS (Fixed!)
            // A. Debit Sender
            $sourceBank->balance -= $amount;
            $sourceBank->save();

            // B. Credit Receiver
            $destBank->balance += $settlementAmount;
            $destBank->save();

            // 6. GENERATE IMMUTABLE LEDGER RECORD
            $tx = Transaction::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'type' => 'transfer_out', // Required for your daily limit queries
                'source_currency' => $sourceBank->currency,
                'destination_currency' => $destCurrency,
                'source_amount' => $amount,
                'exchange_rate' => $rate->effective_rate,
                'destination_amount' => $settlementAmount,
                'fee_charged' => $feeAmount,
                'status' => 'completed',
                'reference' => 'KP-SETTLE-' . strtoupper(Str::random(8))
            ]);

            // 7. CAPTURE REVENUE (Dynamic FX Fix)
            RevenueLog::create([
                'entry_id' => 'REV-' . strtoupper(Str::random(6)),
                'source' => 'FX Spread & Fee',
                'node_path' => $pair,
                // Using live USD oracle instead of hardcoded 0.00065
                'amount_usd' => round($feeAmount * $usdRate->effective_rate, 4), 
            ]);

            // 8. BROADCAST TO GRID
            // This triggers notifications, webhooks, and UI updates
            event(new TransactionProcessed($tx));

            return $tx;
        });
    }
}