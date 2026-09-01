<?php

namespace App\Livewire\admin;

use App\Models\Transaction;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class TransactionLedger extends Component
{
    use WithPagination;

    // Search and Filters
    public $search = '';
    public $statusFilter = '';
    public $pairFilter = '';

    // Inspector Panel State
    public $showInspector = false;
    public $selectedTx = null;

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function inspectTransaction($id)
    {
        $this->selectedTx = Transaction::find($id);
        $this->showInspector = true;
    }

    public function closeInspector()
    {
        $this->showInspector = false;
        $this->selectedTx = null;
    }

    public function render()
    {
        $transactions = Transaction::query()
            ->when($this->search, function ($query) {
                $query->where('reference', 'like', '%' . $this->search . '%')
                      ->orWhere('receiver_name', 'like', '%' . $this->search . '%');
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->when($this->pairFilter, function ($query) {
                // E.g. pairFilter = "NGN/XOF"
                $pairs = explode('/', $this->pairFilter);
                if (count($pairs) == 2) {
                    $query->where('source_currency', $pairs[0])
                          ->where('destination_currency', $pairs[1]);
                }
            })
            ->latest()
            ->paginate(15);

        return view('livewire.admin.transaction-ledger', [
            'transactions' => $transactions
        ]);
    }
}