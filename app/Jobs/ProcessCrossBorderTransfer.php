<?php

namespace App\Jobs;

use App\Services\SettlementEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessCrossBorderTransfer implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $senderId;
    public $receiverName;
    public $sourceBankId;
    public $destCurrency;
    public $amount;

    public function __construct($senderId, $receiverName, $sourceBankId, $destCurrency, $amount)
    {
        $this->senderId = $senderId;
        $this->receiverName = $receiverName;
        $this->sourceBankId = $sourceBankId;
        $this->destCurrency = $destCurrency;
        $this->amount = $amount;
    }

    public function handle(SettlementEngine $engine): void
    {
        try {
            // Engage the Reactor
            $engine->execute(
                $this->senderId,
                $this->receiverName,
                $this->sourceBankId,
                $this->destCurrency,
                $this->amount
            );
        } catch (\Exception $e) {
            // If it fails, log the disaster for the SuperAdmin to review
            Log::error('Settlement Failure: ' . $e->getMessage(), [
                'sender' => $this->senderId,
                'amount' => $this->amount
            ]);
            
            // Here you would also update a "Pending" transaction to "Failed"
            // and refund the user's local wallet if applicable.
        }
    }
}