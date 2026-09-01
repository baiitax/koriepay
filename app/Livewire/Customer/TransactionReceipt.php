<?php

namespace App\Livewire\Customer;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class TransactionReceipt extends Component
{
    public Transaction $transaction;

    public function mount($reference)
    {
        // Fetch the transaction and ENSURE it belongs to the logged-in user
        $this->transaction = Transaction::where('reference', $reference)
                                        ->where('user_id', Auth::id())
                                        ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.customer.transaction-receipt');
    }
}