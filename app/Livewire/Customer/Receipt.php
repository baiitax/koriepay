<?php

namespace App\Livewire\Customer;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class Receipt extends Component
{
    public Transaction $transaction;

    public function mount($reference)
    {
        $this->transaction = Transaction::where('reference', $reference)
            ->where('user_id', Auth::id())
            ->firstOrFail();
    }

    public function render()
    {
        return view('livewire.customer.receipt');
    }
}