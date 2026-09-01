<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\Transaction;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class Referrals extends Component
{
    public $referralCode;
    public $totalReferred = 0;
    public $totalEarned = 0;

    public function mount()
    {
        $user = Auth::user();
        $this->referralCode = $user->referral_code;
        
        $this->totalReferred = User::where('referred_by', $user->id)->count();
        $this->totalEarned = Transaction::where('receiver_id', $user->id)
                                        ->where('type', 'referral_bonus')
                                        ->sum('destination_amount');
    }

    public function render()
    {
        return view('livewire.customer.referrals');
    }
}