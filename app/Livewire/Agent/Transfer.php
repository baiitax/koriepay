<?php

namespace App\Livewire\Agent;

use App\Models\{User, Wallet, Transaction};
use Livewire\{Component, Attributes\Layout};
use Illuminate\Support\Facades\DB;
use App\Services\FxService;

#[Layout('layouts.app')]
class Transfer extends Component
{
    public $amount;
    public $payoutAmount;
    public $displayRate;

    public function updatedAmount()
    {
        if ($this->amount > 0) {
            $fxService = new FxService();
            $details = $fxService->getTransferDetails('NGN', 'XOF', $this->amount);

            $this->payoutAmount = $details['payout_amount'];
            $this->displayRate = $details['effective_rate'];
        }
    }
}