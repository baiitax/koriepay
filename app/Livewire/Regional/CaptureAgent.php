<?php

namespace App\Livewire\Regional;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

#[Layout('layouts.regional')]
class CaptureAgent extends Component
{
    #[Validate('required|string|max:255')]
    public $first_name = '';

    #[Validate('required|string|max:255')]
    public $last_name = '';

    #[Validate('required|email|unique:users,email')]
    public $email = '';

    #[Validate('required|string|max:20|unique:users,phone')]
    public $phone = '';

    #[Validate('required|string|max:255')]
    public $business_name = '';

    #[Validate('required|string')]
    public $state_location = '';

    #[Validate('required|in:tier_1,tier_2,tier_3')]
    public $agent_tier = 'tier_1';

    public function capture()
    {
        $this->validate();

        // Create the new Agent in the database
        User::create([
            'name' => trim($this->first_name . ' ' . $this->last_name),
            'email' => strtolower($this->email),
            'phone' => $this->phone,
            'password' => Hash::make('KoriePay2026!'), // Secure default passkey
            'role' => 'agent',
            'region_id' => auth()->user()->region_id ?? 0, 
            'status' => 'active', 
            'kyc_status' => 'pending', 
        ]);

        // Clear form state
        $this->reset();

        // Flash success and route to the KYC Pipeline
        session()->flash('status', 'Agent captured successfully. Awaiting KYC document upload.');
        return $this->redirectRoute('regional.kyc', navigate: true);
    }

    public function render()
    {
        return view('livewire.regional.capture-agent');
    }
}