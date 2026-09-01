<?php

namespace App\Livewire\admin;

use App\Models\FxRate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class FxRates extends Component
{
    use WithPagination;

    public $search = '';

    // Enterprise Action: Adjust the spread/rate manually
    public function updateRate($id, $newRate)
    {
        $rate = FxRate::findOrFail($id);
        
        // Save the new rate. 
        // Note: The FxRateObserver we built earlier will automatically detect 
        // this and write it to the AuditLog! No extra code needed here.
        $rate->update(['effective_rate' => $newRate]);

        session()->flash('success', "Oracle Updated: {$rate->pair} locked at {$newRate}.");
    }

    // Action: Pause a corridor if volatility is too high
    public function toggleStatus($id)
    {
        $rate = FxRate::findOrFail($id);
        $newStatus = $rate->status === 'active' ? 'halted' : 'active';
        $rate->update(['status' => $newStatus]);
        
        session()->flash('warning', "Corridor {$rate->pair} is now {$newStatus}.");
    }

    public function render()
    {
        return view('livewire.admin.fx-rates', [
            // Passing $rates to the view, fixing your undefined variable error
            'rates' => FxRate::query()
                ->when($this->search, fn($q) => $q->where('pair', 'like', "%{$this->search}%"))
                ->orderBy('pair')
                ->paginate(10)
        ]);
    }
}