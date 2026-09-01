<?php

namespace App\Livewire\Customer;

use App\Models\{User, Beneficiary}; 
use Illuminate\Support\Facades\Auth;
use Livewire\{Component, WithPagination, Attributes\Layout};

#[Layout('layouts.customer')]
class Beneficiaries extends Component
{
    use WithPagination;

    public $search = '';
    
    // Add Contact Modal State
    public $newContactTag = '';
    public $newContactNickname = '';
    public $errorMessage = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function resolveAndAddContact()
    {
        $this->errorMessage = null;

        $this->validate([
            'newContactTag' => 'required|string|min:4',
            'newContactNickname' => 'nullable|string|max:50',
        ]);

        // Find the user by Email, Phone, or Tag
        $targetUser = User::where('email', $this->newContactTag)
                          ->orWhere('phone_number', $this->newContactTag)
                          ->first();

        if (!$targetUser || $targetUser->id === Auth::id()) {
            $this->errorMessage = "We couldn't find a valid KoriePay user with that detail.";
            return;
        }

        // Check if already saved
        /* Example logic assuming a Beneficiary model exists:
        $exists = Beneficiary::where('user_id', Auth::id())->where('beneficiary_id', $targetUser->id)->exists();
        if ($exists) {
            $this->errorMessage = "This user is already in your contacts.";
            return;
        }
        
        Beneficiary::create([
            'user_id' => Auth::id(),
            'beneficiary_id' => $targetUser->id,
            'nickname' => $this->newContactNickname ?: $targetUser->name,
        ]);
        */

        $this->reset(['newContactTag', 'newContactNickname']);
        $this->dispatch('close-contact-modal');
        $this->dispatch('notify', ['type' => 'success', 'message' => "{$targetUser->name} added to your contacts."]);
    }

    public function removeContact($beneficiaryId)
    {
        // Beneficiary::where('id', $beneficiaryId)->where('user_id', Auth::id())->delete();
        $this->dispatch('notify', ['type' => 'success', 'message' => 'Contact removed.']);
    }

    public function render()
    {
        // Dummy data structure for the frontend if you don't have the model ready yet.
        // Replace this with your actual Beneficiary::where('user_id', Auth::id())->get() query.
        $contacts = collect([
            (object)['id' => 1, 'name' => 'Aisha Bello', 'tag' => '@aisha_b', 'country_code' => 'NGA', 'currency' => 'NGN'],
            (object)['id' => 2, 'name' => 'Moussa Oumarou', 'tag' => '+22790123456', 'country_code' => 'NER', 'currency' => 'XOF'],
            (object)['id' => 3, 'name' => 'Chinedu Eze', 'tag' => 'chinedu@mail.com', 'country_code' => 'NGA', 'currency' => 'NGN'],
        ])->filter(function($contact) {
            return empty($this->search) || stripos(strtolower($contact->name), strtolower($this->search)) !== false;
        });

        return view('livewire.customer.beneficiaries', [
            'contacts' => $contacts
        ]);
    }
}