<?php

namespace App\Livewire\Agent;

use App\Models\User;
use Illuminate\Support\Facades\{Auth, Hash};
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.agent')]
class Settings extends Component
{
    // Profile State
    public $name;
    public $email;
    public $username;
    public $phone;

    // Security State
    public $current_password;
    public $new_password;
    public $new_password_confirmation;

    public function mount()
    {
        $agent = Auth::user();
        $this->name = $agent->name;
        $this->email = $agent->email;
        $this->username = $agent->username;
        $this->phone = $agent->phone;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'nullable|string|unique:users,username,' . Auth::id(),
            'phone' => 'nullable|string|unique:users,phone,' . Auth::id(),
        ]);

        Auth::user()->update([
            'name' => $this->name,
            'username' => $this->username,
            'phone' => $this->phone,
        ]);

        session()->flash('success', 'Terminal identity updated successfully.');
    }

    public function updatePassword()
    {
        $this->validate([
            'current_password' => 'required|current_password',
            'new_password' => 'required|min:8|confirmed',
        ]);

        Auth::user()->update([
            'password' => Hash::make($this->new_password),
        ]);

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        session()->flash('success', 'Security credentials rotated successfully.');
    }

    public function render()
    {
        return view('livewire.agent.settings');
    }
}