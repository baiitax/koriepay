<?php

namespace App\Livewire\Customer\Adashi;

use App\Models\{AdashiGroup, AdashiMember};
use Illuminate\Support\Facades\{Auth, DB};
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.app')]
class JoinPool extends Component
{
    public $step = 1;
    public $invite_code = '';
    public $group;
    public $expected_payout = 0;
    public $assigned_order = 0;

    public function verifyCode()
    {
        $this->validate([
            'invite_code' => 'required|string|size:6'
        ]);

        $this->invite_code = strtoupper($this->invite_code);
        $user = Auth::user();

        // 1. Find the pending group
        $this->group = AdashiGroup::where('invite_code', $this->invite_code)
                                  ->where('status', 'pending')
                                  ->withCount('members')
                                  ->first();

        if (!$this->group) {
            $this->addError('invite_code', 'Invalid or expired Adashi invite code.');
            return;
        }

        // 2. Check if the user is already inside
        $alreadyMember = AdashiMember::where('adashi_group_id', $this->group->id)
                                     ->where('user_id', $user->id)
                                     ->exists();

        if ($alreadyMember) {
            $this->addError('invite_code', 'You are already locked into this liquidity ring.');
            return;
        }

        // 3. Check if the pool is full
        if ($this->group->members_count >= $this->group->max_members) {
            $this->addError('invite_code', 'This Adashi pool has reached maximum capacity.');
            return;
        }

        // 4. Calculate their specific math
        $this->expected_payout = $this->group->contribution_amount * $this->group->max_members;
        $this->assigned_order = $this->group->members_count + 1;

        $this->step = 2; // Move to Review
    }

    public function lockIn()
    {
        $user = Auth::user();

        DB::transaction(function () use ($user) {
            // Lock the group row to prevent race conditions (two people joining the last slot at the exact same millisecond)
            $lockedGroup = AdashiGroup::where('id', $this->group->id)->lockForUpdate()->first();
            $currentMembers = AdashiMember::where('adashi_group_id', $lockedGroup->id)->count();

            if ($currentMembers >= $lockedGroup->max_members) {
                throw new \Exception("Pool filled up while you were reviewing.");
            }

            // Join the user
            AdashiMember::create([
                'adashi_group_id' => $lockedGroup->id,
                'user_id' => $user->id,
                'payout_order' => $currentMembers + 1,
                'status' => 'active',
            ]);

            // If this user fills the final slot, activate the pool!
            if (($currentMembers + 1) == $lockedGroup->max_members) {
                $lockedGroup->update(['status' => 'active']);
            }
        });

        $this->step = 3; // Success
    }

    public function render()
    {
        return view('livewire.customer.adashi.join-pool');
    }
}