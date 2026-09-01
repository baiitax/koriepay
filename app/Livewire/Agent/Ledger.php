<?php

namespace App\Livewire\Agent;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.agent')]
class Ledger extends Component
{
    use WithPagination;

    public $search = '';
    public $filterType = ''; // 'cash_in' or 'cash_out'

    // Reset pagination when searching or filtering
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingFilterType()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::where('sender_id', Auth::id());

        // Text Search (Reference or Customer Name)
        if ($this->search) {
            $query->where(function ($q) {
                $q->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhere('receiver_name', 'like', '%' . $this->search . '%');
            });
        }

        // Operation Type Filter
        if ($this->filterType) {
            $query->where('type', $this->filterType);
        }

        return view('livewire.agent.ledger', [
            'transactions' => $query->latest()->paginate(15)
        ]);
    }
}