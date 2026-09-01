<?php

namespace App\Livewire\admin;

use App\Models\BankNode; // Import the new model
use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class LiquidityWallets extends Component
{
    public $isLocked = true;

    public function toggleSecurity()
    {
        $this->isLocked = !$this->isLocked;
    }

    public function syncNode($id)
    {
        $node = BankNode::find($id);
        $node->update(['last_sync' => now()]);
        session()->flash('notify', 'Node Handshake Successful: ' . $node->bank_name);
    }

    public function render()
    {
        return view('livewire.admin.liquidity-wallets', [
            // Fetch real nodes from the database
            'bankNodes' => BankNode::all() 
        ]);
    }
}