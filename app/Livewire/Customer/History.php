<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithPagination;

#[Layout('layouts.customer')]
class History extends Component
{
    use WithPagination;

    public $filter = 'all'; // 'all', 'credit', 'debit'
    public $selectedTx = null; // Holds the transaction for the Receipt

    public function setFilter($type)
    {
        $this->filter = $type;
        $this->resetPage(); // Reset pagination when switching tabs
    }

    // Opens the Digital Receipt
    public function viewReceipt($transactionId)
    {
        $userId = Auth::id();
        
        // FIXED: Check if the user is EITHER the sender OR the receiver
        $this->selectedTx = Transaction::where(function ($query) use ($userId) {
                                          $query->where('sender_id', $userId)
                                                ->orWhere('receiver_id', $userId);
                                      })
                                      ->where('id', $transactionId)
                                      ->first();
                                     
        if ($this->selectedTx) {
            $this->dispatch('open-receipt-modal');
        }
    }

    public function render()
    {
        $userId = Auth::id();
        
        $query = Transaction::query();

        // FIXED: Utilize Tier-1 Schema logic for pristine filtering
        if ($this->filter === 'credit') {
            // Money entering the wallet: User is the receiver
            $query->where('receiver_id', $userId);
        } elseif ($this->filter === 'debit') {
            // Money leaving the wallet: User is the sender
            $query->where('sender_id', $userId);
        } else {
            // ALL: User is either the sender or receiver
            $query->where(function ($q) use ($userId) {
                $q->where('sender_id', $userId)
                  ->orWhere('receiver_id', $userId);
            });
        }

        return view('livewire.customer.history', [
            'transactions' => $query->latest()->paginate(15)
        ]);
    }
}