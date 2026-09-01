<?php

namespace App\Livewire\Manager;

use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Ledger extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $countryCode;
    public $currency;

    public function mount()
    {
        $this->countryCode = Auth::user()->country_code;
        $this->currency = $this->countryCode === 'NER' ? 'XOF' : 'NGN';
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Transaction::with('user')
            ->whereHas('user', function($q) {
                $q->where('country_code', $this->countryCode);
            });

        if ($this->search) {
            $query->where('reference', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function($q) {
                      $q->where('name', 'like', '%' . $this->search . '%')
                        ->where('country_code', $this->countryCode);
                  });
        }

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        $transactions = $query->latest()->paginate(15);

        return view('livewire.manager.ledger', compact('transactions'));
    }
}