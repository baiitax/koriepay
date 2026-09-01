<?php

namespace App\Livewire\Customer;

use App\Models\User;
use Illuminate\Support\Facades\{Auth, Hash, Storage, DB};
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class Settings extends Component
{
    use WithFileUploads;

    // Profile State
    public $name;
    public $username;
    public $photo; 
    public $kyc_status;
    
    // Security Action State
    public $hasPin;
    public $pin;
    public $pin_confirmation;
    public $is_locked;
    public $showPinModal = false;

    // Security Toggle State
    public $enable_biometrics;
    public $enable_2fa;
    public $hide_balance;

    public function mount()
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->username = $user->username ?? strtolower(str_replace(' ', '', $user->name));
        $this->kyc_status = $user->kyc_status;
        $this->hasPin = !empty($user->transaction_pin);
        $this->is_locked = (bool) $user->account_locked;

        // Load Toggles
        $this->enable_biometrics = (bool) $user->enable_biometrics;
        $this->enable_2fa = (bool) $user->enable_2fa;
        $this->hide_balance = (bool) $user->hide_balance;
    }

    public function updateProfile()
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|alpha_dash|min:3|unique:users,username,' . Auth::id(),
            'photo' => 'nullable|image|max:2048', 
        ]);

        if ($this->name !== Auth::user()->name && $this->kyc_status === 'verified') {
            $this->addError('name', 'Locked by KYC: Name cannot be changed after ID verification.');
            return;
        }

        try {
            DB::transaction(function () {
                $user = User::find(Auth::id());

                if ($this->photo) {
                    if ($user->profile_photo_path) {
                        Storage::disk('public')->delete($user->profile_photo_path);
                    }
                    $user->profile_photo_path = $this->photo->store('avatars', 'public');
                }

                $user->name = $this->name;
                $user->username = $this->username;
                $user->save();
            });

            $this->reset('photo');
            session()->flash('success', 'Profile details updated successfully.');

        } catch (\Exception $e) {
            session()->flash('error', 'Update failed. System error.');
        }
    }

    // --- TOGGLE METHODS ---
    public function togglePreference($preference)
    {
        $user = User::find(Auth::id());
        
        if (in_array($preference, ['enable_biometrics', 'enable_2fa', 'hide_balance'])) {
            $user->$preference = !$user->$preference;
            $user->save();
            $this->$preference = $user->$preference;
        }
    }

    // --- ACTION METHODS ---
    public function setPin()
    {
        $this->validate([
            'pin' => 'required|digits:4|confirmed',
        ]);

        $user = User::find(Auth::id());
        $user->transaction_pin = Hash::make($this->pin);
        $user->save();

        $this->hasPin = true;
        $this->showPinModal = false;
        $this->reset(['pin', 'pin_confirmation']);
        session()->flash('success', 'Transaction PIN activated.');
    }

    public function toggleAccountLock()
    {
        $user = User::find(Auth::id());
        $user->account_locked = !$user->account_locked;
        $user->save();
        
        if($user->account_locked) {
            Auth::logout();
            session()->invalidate();
            session()->regenerateToken();
            return redirect()->route('login'); 
        }

        $this->is_locked = false;
        session()->flash('success', "Account successfully unfrozen.");
    }

    public function render()
    {
        return view('livewire.customer.settings');
    }
}