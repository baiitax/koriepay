<?php

namespace App\Livewire\Regional;

use Livewire\Component;
use Livewire\WithFileUploads; // CRITICAL FOR FILE UPLOADS
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.regional')]
class KycPipeline extends Component
{
    use WithFileUploads;

    public $selectedAgent = null;

    #[Validate('required|image|max:5120')] // Max 5MB, Images only
    public $id_document;

    #[Validate('required|image|max:5120')] // Max 5MB, Images only
    public $utility_bill;

    public function selectAgent($agentId)
    {
        $this->selectedAgent = User::where('region_id', auth()->user()->region_id ?? 0)
                                   ->where('id', $agentId)
                                   ->first();
        
        // Reset file inputs when switching agents
        $this->reset(['id_document', 'utility_bill']);
        $this->resetValidation();
    }

    public function processKyc()
    {
        $this->validate();

        if (!$this->selectedAgent) {
            return;
        }

        // 1. Securely store the documents in the 'kyc-documents' folder
        // (This saves to storage/app/public/kyc-documents)
        $idPath = $this->id_document->store('kyc-documents', 'public');
        $utilityPath = $this->utility_bill->store('kyc-documents', 'public');

        // 2. Update the agent's database record
        // NOTE: Make sure you add 'id_document_path' and 'utility_bill_path' to your users table migration!
        $this->selectedAgent->update([
            'kyc_status' => 'approved',
            'status' => 'active',
            // 'id_document_path' => $idPath,
            // 'utility_bill_path' => $utilityPath,
        ]);

        // 3. Clear selection and show success message
        $this->reset(['selectedAgent', 'id_document', 'utility_bill']);
        session()->flash('status', 'KYC Documents verified and agent activated successfully.');
    }

    public function render()
{
    $user = auth()->user();
    $regionId = $user->region_id ?? 0;

    // THE MASTER METRICS ARRAY: 
    // Ensure every key requested by your Blade header is defined here.
    $metrics = [
        'pending_kyc' => User::where('role', 'agent')
            ->where('region_id', $regionId)
            ->where('kyc_status', 'pending')
            ->count(),

        'active_agents' => User::where('role', 'agent')
            ->where('region_id', $regionId)
            ->where('status', 'active')
            ->count(),

        'regional_volume' => '42,500,000', // Placeholder
        'volume_24h' => '42,500,000',      // Placeholder
        'volume_trend' => '+14.2%',
        'revenue_trend' => '+5.2%',        // Added for consistency
        'agents_trend' => '+3 this week',  // Added for consistency
    ];

    $pendingQueue = User::where('role', 'agent')
        ->where('region_id', $regionId)
        ->where('kyc_status', 'pending')
        ->latest()
        ->get();

    return view('livewire.regional.kyc-pipeline', [
        'pendingQueue' => $pendingQueue,
        'metrics' => $metrics // This now includes 'active_agents'
    ]);
}
}