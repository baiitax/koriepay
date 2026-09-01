<?php

namespace App\Livewire\Customer\Adashi;

use App\Models\{AdashiGroup, AdashiMember};
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class GroupLedger extends Component
{
    public AdashiGroup $group;
    public $currentCycle = 1;

    public function mount($groupId)
    {
        $user = Auth::user();

        // 1. Fetch group with members and user details
        $this->group = AdashiGroup::with(['members.user'])->findOrFail($groupId);

        // 2. SECURITY CHECK: Ensure the auth user is actually in this pool
        $isMember = $this->group->members->where('user_id', $user->id)->count() > 0;
        
        if (!$isMember) {
            abort(403, 'Unauthorized access to Community Ledger.');
        }

        // 3. Calculate current cycle mathematically
        $completedCycles = $this->group->members->where('has_received_payout', true)->count();
        $this->currentCycle = $completedCycles + 1;

        // Cap cycle number if completed
        if ($this->group->status === 'completed') {
            $this->currentCycle = $this->group->max_members;
        }
    }

    public function render()
    {
        return view('livewire.customer.adashi.group-ledger', [
            // Sort members strictly by payout order so the queue is visually accurate
            'roster' => $this->group->members->sortBy('payout_order')
        ]);
    }
}