<?php

namespace App\Livewire\Manager;

use App\Models\User;
use App\Models\Transaction;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AgentDetail extends Component
{
    use WithPagination;

    public User $agent;
    public $countryCode;
    public $currency;

    public function mount(User $user)
    {
        $this->countryCode = Auth::user()->country_code;

        // Security: Ensure Manager can only view agents in their own country
        if ($user->country_code !== $this->countryCode || $user->role !== 'agent') {
            abort(403, 'Unauthorized access to regional terminal data.');
        }

        $this->agent = $user;
        $this->currency = $this->countryCode === 'NGA' ? 'NGN' : 'XOF';
    }

    /**
     * Executive Security Override: Freeze/Unfreeze Terminal
     */
    public function toggleFreeze()
    {
        $this->agent->is_active = !$this->agent->is_active;
        $this->agent->save();

        $status = $this->agent->is_active ? 'restored' : 'suspended';
        $color = $this->agent->is_active ? 'success' : 'error';

        session()->flash($color, "Terminal SP-{$this->agent->id} access has been {$status}.");
    }

    public function render()
    {
        // Fetch specific history for this agent only
        $history = Transaction::where('user_id', $this->agent->id)
            ->latest()
            ->paginate(10);

        return view('livewire.manager.agent-detail', [
            'history' => $history,
            'wallets' => $this->agent->wallets
        ]);
    }
}