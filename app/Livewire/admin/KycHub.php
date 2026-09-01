<?php

namespace App\Livewire\Admin;

use App\Models\User;
use App\Services\KycWorkflow;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class KycHub extends Component
{
    use WithPagination;

    public $search = '';

    // Holds the entity currently being reviewed in the slide-over
    public ?User $selectedEntity = null;

    // Action: Open the Dossier Slide-Over
    public function reviewEntity($id)
    {
        $this->selectedEntity = User::findOrFail($id);
    }

    // Action: Close the Dossier
    public function closeReview()
    {
        $this->selectedEntity = null;
    }

    // Phase 4 — decisions go through the canonical KYC workflow which keeps
    // kyc_submissions + user.kyc_status in sync and writes a proper audit
    // entry (the previous inline forceCreate wrote non-existent columns).
    public function approve($id)
    {
        $user = User::findOrFail($id);

        KycWorkflow::approve($user, auth()->user(), ['tier' => 'tier2']);

        $this->closeReview();
        session()->flash('success', "Compliance cleared: {$user->name} is now verified.");
    }

    public function reject($id)
    {
        $user = User::findOrFail($id);

        KycWorkflow::reject($user, auth()->user(), 'Rejected by reviewer.');

        $this->closeReview();
        session()->flash('warning', "Entity Rejected: {$user->name} has been blacklisted.");
    }

    public function render()
    {
        return view('livewire.admin.kyc-hub', [
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
