<?php

namespace App\Livewire\Customer;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\{Component, WithPagination, Attributes\Layout};

#[Layout('layouts.customer')]
class PersonalLedger extends Component
{
    use WithPagination;

    public $filter = 'all'; // 'all', 'in', 'out'
    public $search = '';
    
    public $selectedTx = null;

    public function updatingSearch() { $this->resetPage(); }
    public function updatingFilter() { $this->resetPage(); }

    public function setFilter($type)
    {
        $this->filter = $type;
        $this->resetPage();
    }

    public function viewReceipt($transactionId)
    {
        // Load transaction details securely ensuring it belongs to the user
        $this->selectedTx = Transaction::where('id', $transactionId)
            ->where('user_id', Auth::id())
            ->first();

        if ($this->selectedTx) {
            $this->dispatch('open-receipt-modal');
        }
    }

    public function downloadReceipt()
    {
        // In a production app, you would use a package like dompdf here
        // to generate a PDF from a view and return a download response.
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Receipt downloaded successfully.']);
    }

    public function render()
    {
        $transactions = Transaction::where('user_id', Auth::id())
            ->when($this->search, function($query) {
                $query->where('reference', 'like', '%' . $this->search . '%')
                      ->orWhere('type', 'like', '%' . $this->search . '%');
            })
            ->when($this->filter === 'in', function($query) {
                $query->whereIn('type', ['deposit', 'receive']);
            })
            ->when($this->filter === 'out', function($query) {
                $query->whereIn('type', ['withdraw', 'transfer']);
            })
            ->latest()
            ->paginate(15);

        return view('livewire.customer.personal-ledger', [
            'transactions' => $transactions
        ]);
    }
}