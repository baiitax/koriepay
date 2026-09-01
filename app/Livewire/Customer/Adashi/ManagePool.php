<?php

namespace App\Livewire\Customer\Adashi;

use App\Models\{AdashiGroup, AdashiMember};
use Illuminate\Support\Facades\{Auth, DB};
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class ManagePool extends Component
{
    public AdashiGroup $group;

    public function mount($groupId)
    {
        $this->group = AdashiGroup::with(['members.user'])->findOrFail($groupId);

        // 1. SECURITY: Only the Creator can manage the pool
        if ($this->group->creator_id !== Auth::id()) {
            abort(403, 'Unauthorized. Only the Adashi Admin can manage the roster.');
        }

        // 2. STATE CHECK: You cannot kick people once the ledger is active
        if ($this->group->status !== 'pending') {
            session()->flash('error', 'Pool is already active. Roster is locked.');
            $this->redirectRoute('customer.adashi.ledger', ['groupId' => $this->group->id], navigate: true);
        }
    }

    public function kickMember($memberId)
    {
        DB::transaction(function () use ($memberId) {
            $member = AdashiMember::where('id', $memberId)
                                  ->where('adashi_group_id', $this->group->id)
                                  ->first();

            // Cannot kick yourself (the admin)
            if ($member && $member->user_id !== $this->group->creator_id) {
                $member->delete();

                // Re-sequence the payout order for remaining members
                // The creator is always #1. Everyone else shifts up to fill the gap.
                $remainingMembers = AdashiMember::where('adashi_group_id', $this->group->id)
                                                ->where('user_id', '!=', $this->group->creator_id)
                                                ->orderBy('created_at', 'asc')
                                                ->get();

                $newOrder = 2; // Slot 1 belongs to the Creator
                foreach ($remainingMembers as $rem) {
                    $rem->update(['payout_order' => $newOrder]);
                    $newOrder++;
                }
            }
        });

        // Refresh the group data
        $this->group->load('members.user');
        session()->flash('success', 'Member removed and roster re-sequenced.');
    }

    public function render()
    {
        // Sort by payout order for the UI
        $roster = $this->group->members->sortBy('payout_order');
        
        return view('livewire.customer.adashi.manage-pool', [
            'roster' => $roster,
        ]);
    }
}