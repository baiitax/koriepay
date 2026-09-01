<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class Cards extends Component
{
    public $isOnWaitlist = false;

    public function mount()
    {
        $this->isOnWaitlist = Auth::user()->is_on_card_waitlist;
    }

    public function joinWaitlist()
    {
        $user = Auth::user();
        $user->update(['is_on_card_waitlist' => true]);
        $this->isOnWaitlist = true;
        
        session()->flash('success', 'You are on the list! We will notify you when KoriePay Cards drop.');
    }

    public function render()
    {
        return view('livewire.customer.cards');
    }
}