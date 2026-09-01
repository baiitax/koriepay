<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class SecuritySettings extends Component
{
    // Password Properties
    public $current_password = '';
    public $password = '';
    public $password_confirmation = '';

    // PIN Properties
    public $pin = '';
    public $pin_confirmation = '';
    public $hasPin = false;

    public function mount()
    {
        // Check if the user already has a PIN set in the database
        $this->hasPin = !empty(Auth::user()->transaction_pin);
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // FIX: Direct assignment bypasses the $fillable array requirement
        $user->password = Hash::make($this->password);
        $user->save();

        $this->reset(['current_password', 'password', 'password_confirmation']);
        session()->flash('success', 'Security credentials updated successfully.');
    }

    public function updatePin()
    {
        $this->validate([
            'pin' => 'required|numeric|digits:4',
            'pin_confirmation' => 'required|same:pin',
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();
        
        // FIX: Direct assignment saves the PIN regardless of the $fillable array
        $user->transaction_pin = Hash::make($this->pin);
        $user->save();

        $this->hasPin = true;
        $this->reset(['pin', 'pin_confirmation']);
        
        session()->flash('pin_success', '4-Digit Transaction PIN secured successfully.');
    }

    public function render()
    {
        return view('livewire.customer.security-settings');
    }
}