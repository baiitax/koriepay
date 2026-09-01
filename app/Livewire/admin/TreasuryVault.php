<?php

namespace App\Livewire\Admin;

use Livewire\Component;
use Livewire\Attributes\Layout;

#[Layout('layouts.admin')]
class TreasuryVault extends Component
{
    public function render()
    {
        return view('livewire.admin.treasury-vault');
    }
}
