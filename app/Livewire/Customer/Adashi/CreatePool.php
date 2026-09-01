<?php

namespace App\Livewire\Customer\Adashi;

use App\Models\{AdashiGroup, AdashiMember};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.customer')]
class CreatePool extends Component
{
    public $step = 1;
    
    public $name = '';
    public $currency = 'NGN';
    public $contribution_amount = '';
    public $max_members = 5;
    public $frequency = 'weekly'; // Strictly tied to DB Enum
    public $start_date = '';

    public $invite_code = '';
    public $expected_payout = 0;

    protected function rules()
    {
        $minContribution = $this->currency === 'NGN' ? 1000 : 500;

        return [
            'name' => 'required|string|min:5|max:50',
            'contribution_amount' => "required|numeric|min:{$minContribution}",
            'max_members' => 'required|integer|min:2|max:20',
            'frequency' => 'required|in:daily,weekly,monthly', // STRICT ENUM MATCH
            'start_date' => 'required|date|after:today',
        ];
    }

    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
        $this->calculatePayout();
    }

    public function updatedCurrency()
    {
        if ($this->contribution_amount) {
            $this->validateOnly('contribution_amount');
        }
        $this->calculatePayout();
    }

    public function calculatePayout()
    {
        $amount = (float) $this->contribution_amount;
        $members = (int) $this->max_members;
        $this->expected_payout = $amount * $members;
    }

    public function validateStepOne()
    {
        $this->validate();
        $this->calculatePayout(); 
        $this->step = 2;
    }

    public function deployPool()
    {
        $user = Auth::user();

        try {
            DB::transaction(function () use ($user) {
                
                $this->invite_code = strtoupper(Str::random(6));

                $group = AdashiGroup::create([
                    'creator_id' => $user->id,
                    'name' => $this->name,
                    'currency' => $this->currency,
                    'contribution_amount' => $this->contribution_amount, 
                    'max_members' => $this->max_members,
                    'frequency' => $this->frequency, 
                    'start_date' => $this->start_date, 
                    'invite_code' => $this->invite_code, 
                    'status' => 'pending', 
                ]);

                // FIX: Removed 'total_contributed' to respect the database schema
                AdashiMember::create([
                    'adashi_group_id' => $group->id,
                    'user_id' => $user->id,
                    'payout_order' => 1,
                    'status' => 'active',
                ]);
            });

            $this->step = 3;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Critical Ledger Error: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.customer.adashi.create-pool');
    }
}