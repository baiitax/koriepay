<?php

namespace App\Livewire\Manager;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Kyc extends Component
{
    use WithPagination;

    public $countryCode;

    public function mount()
    {
        // Lock the component to the manager's specific territory
        $this->countryCode = Auth::user()->country_code;
    }

    public function approve($userId)
    {
        $agent = User::where('id', $userId)
            ->where('country_code', $this->countryCode)
            ->where('kyc_status', 'pending')
            ->first();

        if ($agent) {
            $agent->update(['kyc_status' => 'verified']);
            session()->flash('success', "Agent {$agent->name} has been successfully verified and activated.");
        }
    }

    public function reject($userId)
    {
        $agent = User::where('id', $userId)
            ->where('country_code', $this->countryCode)
            ->where('kyc_status', 'pending')
            ->first();

        if ($agent) {
            $agent->update(['kyc_status' => 'rejected']);
            session()->flash('error', "Application for {$agent->name} has been rejected.");
        }
    }

    public function render()
    {
        $pendingAgents = User::where('role', 'agent')
            ->where('country_code', $this->countryCode)
            ->where('kyc_status', 'pending')
            ->latest()
            ->paginate(10);

        return view('livewire.manager.kyc', [
            'pendingAgents' => $pendingAgents
        ]);
    }
}