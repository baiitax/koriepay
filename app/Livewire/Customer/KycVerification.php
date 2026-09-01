<?php

namespace App\Livewire\Customer;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class KycVerification extends Component
{
    use WithFileUploads;

    public $document_type = 'nin';
    public $document_number = '';
    public $id_image;
    public $passport_photo; // Requested by QA

    public function submitKyc()
    {
        $this->validate([
            'document_type' => 'required|in:nin,bvn,passport,drivers_license',
            'document_number' => 'required|string|min:8|max:15',
            'id_image' => 'required|image|max:5120', // 5MB Max
            'passport_photo' => 'required|image|max:5120', // 5MB Max
        ]);

        $user = Auth::user();

        // Securely store the files in the 'kyc-documents' private disk/folder
        $idPath = $this->id_image->store('kyc-documents', 'local');
        $photoPath = $this->passport_photo->store('kyc-documents', 'local');

        // Update User Profile
        $user->update([
            'kyc_status' => 'pending',
            // Assuming you add these columns to your users table:
            // 'document_type' => $this->document_type,
            // 'document_number' => $this->document_number,
            // 'id_image_path' => $idPath,
            // 'passport_photo_path' => $photoPath,
        ]);

        // Route them to the dashboard now that they've completed onboarding
        session()->flash('success', 'Documents submitted successfully. Verification takes 1-2 hours.');
        return $this->redirect(route('customer.dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customer.kyc-verification');
    }
}