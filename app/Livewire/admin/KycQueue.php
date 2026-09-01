<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\KycWorkflow;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class KycQueue extends Component
{
    use WithPagination;

    public $search = '';

    public function approve($id)
    {
        $user = User::findOrFail($id);

        KycWorkflow::approve($user, auth()->user(), ['tier' => 'tier2']);

        session()->flash('success', "Compliance cleared: {$user->name} is now verified.");
    }

    public function reject($id)
    {
        $user = User::findOrFail($id);

        KycWorkflow::reject($user, auth()->user(), 'Rejected by reviewer.');

        session()->flash('warning', "Entity Rejected: {$user->name} has been blacklisted from the grid.");
    }

    public function render()
    {
        return view('livewire.admin.kyc-queue', [
            'pendingEntities' => User::where('role', 'customer')
                ->where('kyc_status', 'pending')
                ->when($this->search, fn ($q) => $q->where('name', 'like', "%{$this->search}%")->orWhere('email', 'like', "%{$this->search}%"))
                ->latest()
                ->paginate(10),

            'totalPending' => User::where('role', 'customer')->where('kyc_status', 'pending')->count(),
            'totalVerified' => User::where('role', 'customer')->where('kyc_status', 'verified')->count(),
            'totalRejected' => User::where('role', 'customer')->where('kyc_status', 'rejected')->count(),
        ]);
    }
}
